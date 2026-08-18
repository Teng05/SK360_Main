<?php

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $user=auth()->user();
        $fullName=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $status=trim((string)request('status', ''));

        return view('sk_pres.user-management', [
            'fullName'=>$fullName,
            'menuItems'=>$this->menuItems(),
            'currentUrl'=>url()->current(),
            'stats'=>$this->stats(),
            'userGroups'=>$this->userGroups($status),
            'barangays'=>Barangay::query()->orderBy('barangay_name')->get(['barangay_id','barangay_name']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SINGLE ADD SK CHAIRMAN
    |--------------------------------------------------------------------------
    */

    public function storeOfficial(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $validated=$request->validate([
            'first_name'=>['required','string','max:100'],
            'last_name'=>['required','string','max:100'],
            'email'=>['required','email','max:100','unique:users,email'],
            'phone_number'=>['nullable','string','max:20','unique:users,phone_number'],
            'barangay_id'=>['required','integer','exists:barangays,barangay_id'],
        ]);

        $existingChairman=User::query()
            ->where('barangay_id',$validated['barangay_id'])
            ->where('role','sk_chairman')
            ->where(function($query){
                $query->where('status','active')
                    ->orWhere('is_verified',0);
            })
            ->exists();

        if($existingChairman){
            return back()->withInput()->withErrors([
                'barangay_id'=>'This barangay already has an active or pending SK Chairman.',
            ]);
        }

        $user=User::create([
            'first_name'=>$validated['first_name'],
            'last_name'=>$validated['last_name'],
            'email'=>$validated['email'],
            'phone_number'=>$validated['phone_number'] ?: null,
            'barangay_id'=>$validated['barangay_id'],
            'role'=>'sk_chairman',
            'password'=>Hash::make(Str::random(64)),
            'is_verified'=>0,
            'status'=>'inactive',
            'term_start'=>null,
            'term_end'=>null,
        ]);

        $token=Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email'=>$user->email],
            [
                'token'=>Hash::make($token),
                'created_at'=>now(),
            ]
        );

        $setupLink=route('password.setup',[
            'token'=>$token,
            'email'=>$user->email,
        ]);

        try{
            Mail::send('email.account-setup',[
                'user'=>$user,
                'setupLink'=>$setupLink,
            ],function($message) use($user){
                $message->to(
                    $user->email,
                    trim($user->first_name.' '.$user->last_name)
                )->subject('Set Up Your SK360 Account');
            });
        }catch(\Throwable $e){
            \Log::error('Chairman setup email failed: '.$e->getMessage());

            return redirect()
                ->route('sk_pres.user-management')
                ->with('warning','Account created, but the password setup email could not be sent.');
        }

        return redirect()
            ->route('sk_pres.user-management')
            ->with('success','SK Chairman account created. A password setup link was sent to '.$user->email.'.');
    }

    /*
    |--------------------------------------------------------------------------
    | BULK ADD SK CHAIRMEN
    |--------------------------------------------------------------------------
    */

    public function storeBulkOfficials(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $validated=$request->validateWithBag('bulkAdd',[
            'officials'=>['required','array','min:1'],
            'officials.*.full_name'=>['required','string','max:120'],
            'officials.*.email'=>['required','email','max:100','distinct'],
            'officials.*.phone_number'=>['nullable','string','max:20'],
            'officials.*.barangay_id'=>['required','integer','exists:barangays,barangay_id','distinct'],
            'officials.*.term_start'=>['required','date'],
            'officials.*.term_end'=>['required','date'],
        ]);

        $phones=collect($validated['officials'])
            ->pluck('phone_number')
            ->filter()
            ->values();

        if($phones->duplicates()->isNotEmpty()){
            return back()
                ->withInput()
                ->withErrors([
                    'officials'=>'The same phone number cannot be used more than once.',
                ],'bulkAdd');
        }

        $prepared=[];

        foreach($validated['officials'] as $official){
            if(User::where('email',$official['email'])->exists()){
                return back()
                    ->withInput()
                    ->withErrors([
                        'officials'=>'Email '.$official['email'].' is already registered.',
                    ],'bulkAdd');
            }

            if(!empty($official['phone_number']) &&
                User::where('phone_number',$official['phone_number'])->exists()){
                return back()
                    ->withInput()
                    ->withErrors([
                        'officials'=>'Phone number '.$official['phone_number'].' is already registered.',
                    ],'bulkAdd');
            }

            if(strtotime($official['term_end']) <= strtotime($official['term_start'])){
                return back()
                    ->withInput()
                    ->withErrors([
                        'officials'=>'Term end must be after term start for '.$official['full_name'].'.',
                    ],'bulkAdd');
            }

            $existingChairman=User::query()
                ->where('barangay_id',$official['barangay_id'])
                ->where('role','sk_chairman')
                ->where(function($query){
                    $query->where('status','active')
                        ->orWhere('is_verified',0);
                })
                ->exists();

            if($existingChairman){
                $barangay=Barangay::find($official['barangay_id']);

                return back()
                    ->withInput()
                    ->withErrors([
                        'officials'=>'Barangay '.($barangay->barangay_name ?? '').' already has an active or pending SK Chairman.',
                    ],'bulkAdd');
            }

            [$firstName,$lastName]=$this->splitFullName($official['full_name']);

            $prepared[]=[
                'first_name'=>$firstName,
                'last_name'=>$lastName,
                'email'=>$official['email'],
                'phone_number'=>$official['phone_number'] ?: null,
                'barangay_id'=>$official['barangay_id'],
                'term_start'=>$official['term_start'],
                'term_end'=>$official['term_end'],
            ];
        }

        $createdUsers=[];

        DB::transaction(function() use($prepared,&$createdUsers){
            foreach($prepared as $official){
                $user=User::create([
                    'first_name'=>$official['first_name'],
                    'last_name'=>$official['last_name'],
                    'email'=>$official['email'],
                    'phone_number'=>$official['phone_number'],
                    'barangay_id'=>$official['barangay_id'],
                    'role'=>'sk_chairman',
                    'password'=>Hash::make(Str::random(64)),
                    'is_verified'=>0,
                    'status'=>'inactive',
                    'term_start'=>$official['term_start'],
                    'term_end'=>$official['term_end'],
                ]);

                $token=Str::random(64);

                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email'=>$user->email],
                    [
                        'token'=>Hash::make($token),
                        'created_at'=>now(),
                    ]
                );

                $createdUsers[]=[
                    'user'=>$user,
                    'token'=>$token,
                ];
            }
        });

        $mailFailed=0;

        foreach($createdUsers as $created){
            $user=$created['user'];
            $token=$created['token'];

            $setupLink=route('password.setup',[
                'token'=>$token,
                'email'=>$user->email,
            ]);

            try{
                Mail::send('email.account-setup',[
                    'user'=>$user,
                    'setupLink'=>$setupLink,
                ],function($message) use($user){
                    $message->to(
                        $user->email,
                        trim($user->first_name.' '.$user->last_name)
                    )->subject('Set Up Your SK360 Account');
                });
            }catch(\Throwable $e){
                $mailFailed++;
                \Log::error('Bulk chairman setup email failed for '.$user->email.': '.$e->getMessage());
            }
        }

        if($mailFailed > 0){
            return redirect()
                ->route('sk_pres.user-management')
                ->with(
                    'warning',
                    count($createdUsers).' account(s) were created, but '.$mailFailed.' setup email(s) failed to send.'
                );
        }

        return redirect()
            ->route('sk_pres.user-management')
            ->with(
                'success',
                count($createdUsers).' SK Chairman account(s) created. Password setup links were sent successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | CSV TEMPLATE
    |--------------------------------------------------------------------------
    */

    public function downloadCsvTemplate()
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $fileName='sk_chairmen_import_template.csv';

        return response()->streamDownload(function(){
            $file=fopen('php://output','w');

            fputcsv($file,[
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'barangay',
                'term_start',
                'term_end',
            ]);

            fputcsv($file,[
                'Juan',
                'Dela Cruz',
                'juan@example.com',
                '09123456789',
                'Adya',
                '2025-06-30',
                '2028-06-30',
            ]);

            fclose($file);
        },$fileName,[
            'Content-Type'=>'text/csv',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CSV IMPORT
    |--------------------------------------------------------------------------
    */

    public function import(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $validated=$request->validateWithBag('csvImport',[
            'csv_file'=>['required','file','mimes:csv,txt','max:5120'],
        ]);

        $file=$validated['csv_file'];
        $handle=fopen($file->getRealPath(),'r');

        if(!$handle){
            return back()->withErrors([
                'file'=>'Unable to read the uploaded CSV file.',
            ],'csvImport');
        }

        $headers=fgetcsv($handle);

        if(!$headers){
            fclose($handle);

            return back()->withErrors([
                'file'=>'The CSV file is empty.',
            ],'csvImport');
        }

        $headers=array_map(function($header){
            $header=str_replace("\xEF\xBB\xBF",'',$header);
            return strtolower(trim($header));
        },$headers);

        $requiredHeaders=[
            'first_name',
            'last_name',
            'email',
            'phone_number',
            'barangay',
            'term_start',
            'term_end',
        ];

        $missingHeaders=array_diff($requiredHeaders,$headers);

        if(!empty($missingHeaders)){
            fclose($handle);

            return back()->withErrors([
                'headers'=>'Missing column(s): '.implode(', ',$missingHeaders),
            ],'csvImport');
        }

        $barangays=Barangay::query()
            ->get(['barangay_id','barangay_name'])
            ->keyBy(fn($barangay)=>strtolower(trim($barangay->barangay_name)));

        $rows=[];
        $lineNumber=1;

        while(($data=fgetcsv($handle)) !== false){
            $lineNumber++;

            if(count($data) === 1 && trim($data[0] ?? '') === ''){
                continue;
            }

            $row=[];

            foreach($headers as $index=>$header){
                $row[$header]=trim($data[$index] ?? '');
            }

            if(!collect($row)->filter()->isEmpty()){
                $rows[]=[
                    'line'=>$lineNumber,
                    'data'=>$row,
                ];
            }
        }

        fclose($handle);

        if(empty($rows)){
            return back()->withErrors([
                'rows'=>'The CSV file has no records to import.',
            ],'csvImport');
        }

        $errors=[];
        $prepared=[];
        $seenEmails=[];
        $seenPhones=[];
        $seenBarangays=[];

        foreach($rows as $csvRow){
            $line=$csvRow['line'];
            $row=$csvRow['data'];
            $rowHasError=false;

            if($row['first_name'] === ''){
                $errors[]="Row {$line}: First name is required.";
                $rowHasError=true;
            }

            if($row['last_name'] === ''){
                $errors[]="Row {$line}: Last name is required.";
                $rowHasError=true;
            }

            if(!filter_var($row['email'],FILTER_VALIDATE_EMAIL)){
                $errors[]="Row {$line}: '{$row['email']}' is not a valid email address.";
                $rowHasError=true;
            }else{
                $email=strtolower($row['email']);

                if(isset($seenEmails[$email])){
                    $errors[]="Row {$line}: Email '{$row['email']}' appears more than once in the CSV.";
                    $rowHasError=true;
                }else{
                    $seenEmails[$email]=true;
                }

                if(User::where('email',$row['email'])->exists()){
                    $errors[]="Row {$line}: Email '{$row['email']}' is already registered.";
                    $rowHasError=true;
                }
            }

            if($row['phone_number'] !== ''){
                if(isset($seenPhones[$row['phone_number']])){
                    $errors[]="Row {$line}: Phone number '{$row['phone_number']}' appears more than once in the CSV.";
                    $rowHasError=true;
                }else{
                    $seenPhones[$row['phone_number']]=true;
                }

                if(User::where('phone_number',$row['phone_number'])->exists()){
                    $errors[]="Row {$line}: Phone number '{$row['phone_number']}' is already registered.";
                    $rowHasError=true;
                }
            }

            $barangayName=strtolower(trim($row['barangay']));
            $barangay=$barangays->get($barangayName);

            if(!$barangay){
                $errors[]="Row {$line}: Barangay '{$row['barangay']}' was not found.";
                $rowHasError=true;
            }else{
                if(isset($seenBarangays[$barangay->barangay_id])){
                    $errors[]="Row {$line}: Barangay '{$barangay->barangay_name}' appears more than once in the CSV.";
                    $rowHasError=true;
                }else{
                    $seenBarangays[$barangay->barangay_id]=true;
                }

                $existingChairman=User::query()
                    ->where('barangay_id',$barangay->barangay_id)
                    ->where('role','sk_chairman')
                    ->where(function($query){
                        $query->where('status','active')
                            ->orWhere('is_verified',0);
                    })
                    ->exists();

                if($existingChairman){
                    $errors[]="Row {$line}: Barangay '{$barangay->barangay_name}' already has an active or pending SK Chairman.";
                    $rowHasError=true;
                }
            }

            $termStart=$this->parseCsvDate($row['term_start']);
            $termEnd=$this->parseCsvDate($row['term_end']);

            if(!$termStart){
                $errors[]="Row {$line}: Term start '{$row['term_start']}' is invalid.";
                $rowHasError=true;
            }

            if(!$termEnd){
                $errors[]="Row {$line}: Term end '{$row['term_end']}' is invalid.";
                $rowHasError=true;
            }

            if($termStart && $termEnd && $termEnd <= $termStart){
                $errors[]="Row {$line}: Term end must be after term start.";
                $rowHasError=true;
            }

            if(!$rowHasError && $barangay){
                $prepared[]=[
                    'first_name'=>$row['first_name'],
                    'last_name'=>$row['last_name'],
                    'email'=>$row['email'],
                    'phone_number'=>$row['phone_number'] ?: null,
                    'barangay_id'=>$barangay->barangay_id,
                    'term_start'=>$termStart,
                    'term_end'=>$termEnd,
                ];
            }
        }

        if(!empty($errors)){
            $errorBag=[];

            foreach($errors as $index=>$error){
                $errorBag['csv_'.$index]=$error;
            }

            return back()->withErrors($errorBag,'csvImport');
        }

        $createdUsers=[];

        DB::transaction(function() use($prepared,&$createdUsers){
            foreach($prepared as $official){
                $user=User::create([
                    'first_name'=>$official['first_name'],
                    'last_name'=>$official['last_name'],
                    'email'=>$official['email'],
                    'phone_number'=>$official['phone_number'],
                    'barangay_id'=>$official['barangay_id'],
                    'role'=>'sk_chairman',
                    'password'=>Hash::make(Str::random(64)),
                    'is_verified'=>0,
                    'status'=>'inactive',
                    'term_start'=>$official['term_start'],
                    'term_end'=>$official['term_end'],
                ]);

                $token=Str::random(64);

                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email'=>$user->email],
                    [
                        'token'=>Hash::make($token),
                        'created_at'=>now(),
                    ]
                );

                $createdUsers[]=[
                    'user'=>$user,
                    'token'=>$token,
                ];
            }
        });

        $mailFailed=0;

        foreach($createdUsers as $created){
            $user=$created['user'];
            $token=$created['token'];

            $setupLink=route('password.setup',[
                'token'=>$token,
                'email'=>$user->email,
            ]);

            try{
                Mail::send('email.account-setup',[
                    'user'=>$user,
                    'setupLink'=>$setupLink,
                ],function($message) use($user){
                    $message->to(
                        $user->email,
                        trim($user->first_name.' '.$user->last_name)
                    )->subject('Set Up Your SK360 Account');
                });
            }catch(\Throwable $e){
                $mailFailed++;
                \Log::error('CSV setup email failed for '.$user->email.': '.$e->getMessage());
            }
        }

        if($mailFailed > 0){
            return redirect()
                ->route('sk_pres.user-management')
                ->with(
                    'warning',
                    count($createdUsers).' account(s) were imported, but '.$mailFailed.' setup email(s) failed to send.'
                );
        }

        return redirect()
            ->route('sk_pres.user-management')
            ->with(
                'success',
                count($createdUsers).' SK Chairman account(s) imported. Password setup links were sent successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | RESEND SETUP LINK
    |--------------------------------------------------------------------------
    */

    public function resendSetupLink(int $userId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $user=User::where('user_id',$userId)->firstOrFail();

        if($user->role !== 'sk_chairman'){
            return back()->with(
                'warning',
                'Setup links can only be sent to SK Chairman accounts.'
            );
        }

        if((int)$user->is_verified === 1){
            return back()->with(
                'warning',
                'This SK Chairman has already activated their account.'
            );
        }

        $token=Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email'=>$user->email],
            [
                'token'=>Hash::make($token),
                'created_at'=>now(),
            ]
        );

        $setupLink=route('password.setup',[
            'token'=>$token,
            'email'=>$user->email,
        ]);

        try{
            Mail::send('email.account-setup',[
                'user'=>$user,
                'setupLink'=>$setupLink,
            ],function($message) use($user){
                $message->to(
                    $user->email,
                    trim($user->first_name.' '.$user->last_name)
                )->subject('New SK360 Password Setup Link');
            });
        }catch(\Throwable $e){
            \Log::error('Chairman setup link resend failed for '.$user->email.': '.$e->getMessage());

            return back()->with(
                'warning',
                'The new setup link could not be sent. Please try again.'
            );
        }

        return back()->with(
            'success',
            'A new password setup link was sent to '.$user->email.'.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE USER
    |--------------------------------------------------------------------------
    */

    public function update(Request $request,int $userId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $user=User::where('user_id',$userId)->firstOrFail();

        $validated=$request->validate([
            'first_name'=>['required','string','max:100'],
            'last_name'=>['required','string','max:100'],
            'email'=>['required','email','max:100','unique:users,email,'.$userId.',user_id'],
            'phone_number'=>['nullable','string','max:20','unique:users,phone_number,'.$userId.',user_id'],
            'barangay_id'=>['nullable','integer','exists:barangays,barangay_id'],
            'role'=>['required','in:sk_president,sk_chairman,sk_secretary'],
            'status'=>['required','in:active,inactive'],
        ]);

        if($user->role === 'sk_president'){
            $validated['role']='sk_president';
            $validated['status']='active';
        }

        if($user->role === 'sk_chairman' && (int)$user->is_verified === 0){
            $validated['status']='inactive';
        }

        $user->update($validated);

        return back()->with('success','User details updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVATE / DEACTIVATE
    |--------------------------------------------------------------------------
    */

    public function toggleStatus(int $userId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $user=User::where('user_id',$userId)->firstOrFail();

        if($user->role === 'sk_president'){
            return back()->withErrors([
                'status'=>'The SK President account cannot be deactivated.',
            ]);
        }

        if($user->role === 'sk_chairman' && (int)$user->is_verified === 0){
            return back()->with(
                'warning',
                'This chairman has not completed account setup yet. Resend the setup link instead.'
            );
        }

        $user->status=$user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return back()->with('success','User status updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE USER
    |--------------------------------------------------------------------------
    */

    public function destroy(int $userId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $user=User::where('user_id',$userId)->firstOrFail();

        if($user->role === 'sk_president'){
            return back()->withErrors([
                'user'=>'The SK President account cannot be deleted.',
            ]);
        }

        DB::transaction(function() use($user,$userId){
            DB::table('email_verifications')
                ->where('user_id',$userId)
                ->delete();

            DB::table('password_reset_tokens')
                ->where('email',$user->email)
                ->delete();

            $user->delete();
        });

        return back()->with('success','User deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD STATS
    |--------------------------------------------------------------------------
    */

    protected function stats(): array
    {
        return [
            [
                'label'=>'Total Officials',
                'value'=>DB::table('users')
                    ->whereIn('role',['sk_president','sk_chairman','sk_secretary'])
                    ->count(),
                'border'=>'border-red-300',
                'iconBg'=>'bg-red-50',
                'icon'=>'&#128101;',
                'iconColor'=>'text-red-500',
            ],
            [
                'label'=>'SK Chairmen',
                'value'=>DB::table('users')->where('role','sk_chairman')->count(),
                'border'=>'border-green-300',
                'iconBg'=>'bg-green-50',
                'icon'=>'&#128737;',
                'iconColor'=>'text-green-500',
            ],
            [
                'label'=>'SK Secretaries',
                'value'=>DB::table('users')->where('role','sk_secretary')->count(),
                'border'=>'border-blue-300',
                'iconBg'=>'bg-blue-50',
                'icon'=>'&#128196;',
                'iconColor'=>'text-blue-500',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | USER GROUPS
    |--------------------------------------------------------------------------
    */

    protected function userGroups(string $status=''): array
    {
        return [
            [
                'label'=>'SK Federation President / System Admin',
                'description'=>'Single unified administrative account',
                'count'=>DB::table('users')->where('role','sk_president')->count(),
                'badgeColor'=>'bg-red-500',
                'iconColor'=>'text-red-400',
                'icon'=>'&#128198;',
                'users'=>$this->usersByRole(['sk_president'],$status),
            ],
            [
                'label'=>'SK Chairmen',
                'description'=>'Barangay youth leaders with full access',
                'count'=>DB::table('users')->where('role','sk_chairman')->count(),
                'badgeColor'=>'bg-green-500',
                'iconColor'=>'text-green-400',
                'icon'=>'&#128737;',
                'users'=>$this->usersByRole(['sk_chairman'],$status),
            ],
            [
                'label'=>'SK Secretaries',
                'description'=>'Documentation and support officers',
                'count'=>DB::table('users')->where('role','sk_secretary')->count(),
                'badgeColor'=>'bg-blue-500',
                'iconColor'=>'text-blue-400',
                'icon'=>'&#128196;',
                'users'=>$this->usersByRole(['sk_secretary'],$status),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | GET USERS BY ROLE
    |--------------------------------------------------------------------------
    */

    protected function usersByRole(array $roles,string $status='')
    {
        $query=DB::table('users as u')
            ->leftJoin('barangays as b','u.barangay_id','=','b.barangay_id')
            ->select(
                'u.user_id',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.phone_number',
                'u.barangay_id',
                'u.role',
                'u.status',
                'u.is_verified',
                'u.term_start',
                'u.term_end',
                'u.created_at',
                'b.barangay_name'
            )
            ->whereIn('u.role',$roles);

        if(in_array($status,['active','inactive'],true)){
            $query->where('u.status',$status);
        }

        if(
            in_array('sk_chairman',$roles,true) ||
            in_array('sk_secretary',$roles,true)
        ){
            $query->orderByRaw('b.barangay_name IS NULL')
                ->orderBy('b.barangay_name')
                ->orderBy('u.first_name')
                ->orderBy('u.last_name');
        }else{
            $query->orderBy('u.first_name')
                ->orderBy('u.last_name');
        }

        return $query->get();
    }

    protected function menuItems(): array
    {
        return [
            ['link'=>route('sk_pres.home'),'icon'=>'&#127968;','label'=>'Home'],
            ['link'=>route('sk_pres.dashboard'),'icon'=>'&#128202;','label'=>'Dashboard'],
            ['link'=>route('sk_pres.consolidation'),'icon'=>'&#128193;','label'=>'Consolidation'],
            ['link'=>route('sk_pres.module'),'icon'=>'&#9881;&#65039;','label'=>'Module Management'],
            ['link'=>route('sk_pres.announcements'),'icon'=>'&#128226;','label'=>'Announcements'],
            ['link'=>route('sk_pres.calendar'),'icon'=>'&#128197;','label'=>'Calendar'],
            ['link'=>route('sk_pres.chat'),'icon'=>'&#128172;','label'=>'Chat'],
            ['link'=>route('sk_pres.meetings'),'icon'=>'&#128222;','label'=>'Meetings'],
            ['link'=>route('sk_pres.rankings'),'icon'=>'&#127942;','label'=>'Rankings'],
            ['link'=>route('sk_pres.leadership'),'icon'=>'&#128101;','label'=>'Leadership'],
            ['link'=>route('sk_pres.archive'),'icon'=>'&#128450;&#65039;','label'=>'Archive'],
            ['link'=>route('sk_pres.user-management'),'icon'=>'&#128100;','label'=>'User Management'],
        ];
    }

    protected function parseCsvDate(string $date): ?string
    {
        $date=trim($date);

        $formats=[
            'Y-m-d',
            'd/m/Y',
            'm/d/Y',
            'd-m-Y',
        ];

        foreach($formats as $format){
            $parsed=\DateTime::createFromFormat($format,$date);

            if($parsed && $parsed->format($format) === $date){
                return $parsed->format('Y-m-d');
            }
        }

        return null;
    }

    protected function splitFullName(string $fullName): array
    {
        $parts=preg_split('/\s+/',trim($fullName)) ?: [];

        if(count($parts) <= 1){
            return [$parts[0] ?? $fullName,''];
        }

        $firstName=array_shift($parts);
        $lastName=implode(' ',$parts);

        return [$firstName,$lastName];
    }
}