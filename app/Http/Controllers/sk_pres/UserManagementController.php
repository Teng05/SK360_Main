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
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $user=auth()->user();
        $fullName=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $status=trim((string)request('status',''));
        $activeTab=request('tab') === 'history' ? 'history' : 'current';
        $currentAdministration=$this->currentAdministrationTerm();
        $pendingPresident=$this->pendingPresidentForTerm($currentAdministration?->term_id);

        $historyTerms=$this->historyTerms();
        $selectedHistoryTerm=trim((string)request('history_term',''));

        if($selectedHistoryTerm === '' && $historyTerms->isNotEmpty()){
            $selectedHistoryTerm=(string)$historyTerms->first()['value'];
        }

        $selectedHistoryBarangay=(int)request('history_barangay',0);
        $selectedHistoryRole=trim((string)request('history_role',''));

        if(!in_array($selectedHistoryRole,['','sk_president','sk_chairman','sk_secretary'],true)){
            $selectedHistoryRole='';
        }

        $historyUsers=$this->officialHistory($selectedHistoryTerm,$selectedHistoryBarangay,$selectedHistoryRole);

        return view('sk_pres.user-management',[
            'fullName'=>$fullName,
            'menuItems'=>$this->menuItems(),
            'currentUrl'=>url()->current(),
            'stats'=>$this->stats(),
            'userGroups'=>$this->userGroups($status),
            'barangays'=>Barangay::query()->orderBy('barangay_name')->get(['barangay_id','barangay_name']),
            'currentAdministration'=>$currentAdministration,
            'pendingPresident'=>$pendingPresident,
            'activeTab'=>$activeTab,
            'historyTerms'=>$historyTerms,
            'selectedHistoryTerm'=>$selectedHistoryTerm,
            'selectedHistoryBarangay'=>$selectedHistoryBarangay,
            'selectedHistoryRole'=>$selectedHistoryRole,
            'historyStats'=>$this->historyStats($historyUsers),
            'historyGroups'=>$this->historyGroups($historyUsers),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SINGLE ADD SK CHAIRMAN
    |--------------------------------------------------------------------------
    */
    public function storeOfficial(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $currentTerm=$this->currentAdministrationTerm();

        if(!$currentTerm){
            return back()->withInput()->with('warning','There is no active administration term. Start a new administration term first.');
        }

        $validated=$request->validateWithBag('singleAdd',[
            'first_name'=>['required','string','max:100'],
            'last_name'=>['required','string','max:100'],
            'email'=>['required','email','max:100','unique:users,email'],
            'phone_number'=>['nullable','string','max:20','unique:users,phone_number'],
            'barangay_id'=>['required','integer','exists:barangays,barangay_id'],
        ]);

        $existingChairman=User::query()
            ->where('barangay_id',$validated['barangay_id'])
            ->where('role','sk_chairman')
            ->whereNull('archived_at')
            ->exists();

        if($existingChairman){
            return back()->withInput()->withErrors([
                'barangay_id'=>'This barangay already has a current or pending SK Chairman.',
            ],'singleAdd');
        }

        $token=Str::random(64);
        $user=null;

        DB::transaction(function() use($validated,$currentTerm,$token,&$user){
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
                'archived_at'=>null,
            ]);

            DB::table('official_terms')->insert([
                'user_id'=>$user->user_id,
                'term_id'=>$currentTerm->term_id,
                'barangay_id'=>$user->barangay_id,
                'role'=>'sk_chairman',
                'status'=>'pending',
                'started_at'=>null,
                'completed_at'=>null,
            ]);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email'=>$user->email],
                ['token'=>Hash::make($token),'created_at'=>now()]
            );
        });

        $setupLink=route('password.setup',[
            'token'=>$token,
            'email'=>$user->email,
        ]);

        try{
            Mail::send('email.account-setup',[
                'user'=>$user,
                'setupLink'=>$setupLink,
            ],function($message) use($user){
                $message->to($user->email,trim($user->first_name.' '.$user->last_name))
                    ->subject('Set Up Your SK360 Account');
            });
        }catch(\Throwable $e){
            \Log::error('Chairman setup email failed: '.$e->getMessage());

            return redirect()
                ->route('sk_pres.user-management')
                ->with('warning','Account created, but the password setup email could not be sent.');
        }

        return redirect()
            ->route('sk_pres.user-management')
            ->with('success','SK Chairman account created for the '.$currentTerm->start_year.' - '.$currentTerm->end_year.' administration. A password setup link was sent to '.$user->email.'.');
    }

    /*
    |--------------------------------------------------------------------------
    | BULK ADD SK CHAIRMEN
    |--------------------------------------------------------------------------
    */
    public function storeBulkOfficials(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $currentTerm=$this->currentAdministrationTerm();

        if(!$currentTerm){
            return back()->withInput()->with('warning','There is no active administration term. Start a new administration term first.');
        }

        $validated=$request->validateWithBag('bulkAdd',[
            'officials'=>['required','array','min:1'],
            'officials.*.full_name'=>['required','string','max:120'],
            'officials.*.email'=>['required','email','max:100','distinct'],
            'officials.*.phone_number'=>['nullable','string','max:20'],
            'officials.*.barangay_id'=>['required','integer','exists:barangays,barangay_id','distinct'],
        ]);

        $phones=collect($validated['officials'])->pluck('phone_number')->filter()->values();

        if($phones->duplicates()->isNotEmpty()){
            return back()->withInput()->withErrors([
                'officials'=>'The same phone number cannot be used more than once.',
            ],'bulkAdd');
        }

        $prepared=[];

        foreach($validated['officials'] as $official){
            if(User::where('email',$official['email'])->exists()){
                return back()->withInput()->withErrors([
                    'officials'=>'Email '.$official['email'].' is already registered.',
                ],'bulkAdd');
            }

            if(!empty($official['phone_number']) && User::where('phone_number',$official['phone_number'])->exists()){
                return back()->withInput()->withErrors([
                    'officials'=>'Phone number '.$official['phone_number'].' is already registered.',
                ],'bulkAdd');
            }

            $existingChairman=User::query()
                ->where('barangay_id',$official['barangay_id'])
                ->where('role','sk_chairman')
                ->whereNull('archived_at')
                ->exists();

            if($existingChairman){
                $barangay=Barangay::find($official['barangay_id']);

                return back()->withInput()->withErrors([
                    'officials'=>'Barangay '.($barangay->barangay_name ?? '').' already has a current or pending SK Chairman.',
                ],'bulkAdd');
            }

            [$firstName,$lastName]=$this->splitFullName($official['full_name']);

            $prepared[]=[
                'first_name'=>$firstName,
                'last_name'=>$lastName,
                'email'=>$official['email'],
                'phone_number'=>$official['phone_number'] ?: null,
                'barangay_id'=>$official['barangay_id'],
            ];
        }

        $createdUsers=[];

        DB::transaction(function() use($prepared,$currentTerm,&$createdUsers){
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
                    'term_start'=>null,
                    'term_end'=>null,
                    'archived_at'=>null,
                ]);

                DB::table('official_terms')->insert([
                    'user_id'=>$user->user_id,
                    'term_id'=>$currentTerm->term_id,
                    'barangay_id'=>$user->barangay_id,
                    'role'=>'sk_chairman',
                    'status'=>'pending',
                    'started_at'=>null,
                    'completed_at'=>null,
                ]);

                $token=Str::random(64);

                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email'=>$user->email],
                    ['token'=>Hash::make($token),'created_at'=>now()]
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

            $setupLink=route('password.setup',[
                'token'=>$created['token'],
                'email'=>$user->email,
            ]);

            try{
                Mail::send('email.account-setup',[
                    'user'=>$user,
                    'setupLink'=>$setupLink,
                ],function($message) use($user){
                    $message->to($user->email,trim($user->first_name.' '.$user->last_name))
                        ->subject('Set Up Your SK360 Account');
                });
            }catch(\Throwable $e){
                $mailFailed++;
                \Log::error('Bulk chairman setup email failed for '.$user->email.': '.$e->getMessage());
            }
        }

        if($mailFailed > 0){
            return redirect()
                ->route('sk_pres.user-management')
                ->with('warning',count($createdUsers).' account(s) were created for the '.$currentTerm->start_year.' - '.$currentTerm->end_year.' administration, but '.$mailFailed.' setup email(s) failed to send.');
        }

        return redirect()
            ->route('sk_pres.user-management')
            ->with('success',count($createdUsers).' SK Chairman account(s) created for the '.$currentTerm->start_year.' - '.$currentTerm->end_year.' administration. Password setup links were sent successfully.');
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
            ]);

            fputcsv($file,[
                'Juan',
                'Dela Cruz',
                'juan@example.com',
                '09123456789',
                'Adya',
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

        $currentTerm=$this->currentAdministrationTerm();

        if(!$currentTerm){
            return back()->with('warning','There is no active administration term. Start a new administration term first.');
        }

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
                    ->whereNull('archived_at')
                    ->exists();

                if($existingChairman){
                    $errors[]="Row {$line}: Barangay '{$barangay->barangay_name}' already has a current or pending SK Chairman.";
                    $rowHasError=true;
                }
            }

            if(!$rowHasError && $barangay){
                $prepared[]=[
                    'first_name'=>$row['first_name'],
                    'last_name'=>$row['last_name'],
                    'email'=>$row['email'],
                    'phone_number'=>$row['phone_number'] ?: null,
                    'barangay_id'=>$barangay->barangay_id,
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

        DB::transaction(function() use($prepared,$currentTerm,&$createdUsers){
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
                    'term_start'=>null,
                    'term_end'=>null,
                    'archived_at'=>null,
                ]);

                DB::table('official_terms')->insert([
                    'user_id'=>$user->user_id,
                    'term_id'=>$currentTerm->term_id,
                    'barangay_id'=>$user->barangay_id,
                    'role'=>'sk_chairman',
                    'status'=>'pending',
                    'started_at'=>null,
                    'completed_at'=>null,
                ]);

                $token=Str::random(64);

                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email'=>$user->email],
                    ['token'=>Hash::make($token),'created_at'=>now()]
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

            $setupLink=route('password.setup',[
                'token'=>$created['token'],
                'email'=>$user->email,
            ]);

            try{
                Mail::send('email.account-setup',[
                    'user'=>$user,
                    'setupLink'=>$setupLink,
                ],function($message) use($user){
                    $message->to($user->email,trim($user->first_name.' '.$user->last_name))
                        ->subject('Set Up Your SK360 Account');
                });
            }catch(\Throwable $e){
                $mailFailed++;
                \Log::error('CSV setup email failed for '.$user->email.': '.$e->getMessage());
            }
        }

        if($mailFailed > 0){
            return redirect()
                ->route('sk_pres.user-management')
                ->with('warning',count($createdUsers).' account(s) were imported for the '.$currentTerm->start_year.' - '.$currentTerm->end_year.' administration, but '.$mailFailed.' setup email(s) failed to send.');
        }

        return redirect()
            ->route('sk_pres.user-management')
            ->with('success',count($createdUsers).' SK Chairman account(s) imported for the '.$currentTerm->start_year.' - '.$currentTerm->end_year.' administration. Password setup links were sent successfully.');
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

        if($user->archived_at){
            return back()->with('warning','Archived accounts cannot receive password setup links.');
        }

        if(!in_array($user->role,['sk_chairman','sk_president'],true)){
            return back()->with('warning','Setup links can only be sent to pending SK Chairman or SK President accounts from this screen.');
        }

        if((int)$user->is_verified === 1){
            return back()->with('warning','This account has already been activated.');
        }

        $pendingTerm=DB::table('official_terms')
            ->where('user_id',$user->user_id)
            ->where('role',$user->role)
            ->where('status','pending')
            ->orderByDesc('official_term_id')
            ->first();

        if(!$pendingTerm){
            return back()->with('warning','This account is not connected to a pending administration assignment.');
        }

        $token=Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email'=>$user->email],
            ['token'=>Hash::make($token),'created_at'=>now()]
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
                $message->to($user->email,trim($user->first_name.' '.$user->last_name))
                    ->subject('New SK360 Password Setup Link');
            });
        }catch(\Throwable $e){
            \Log::error('Setup link resend failed for '.$user->email.': '.$e->getMessage());
            return back()->with('warning','The new setup link could not be sent. Please try again.');
        }

        return back()->with('success','A new password setup link was sent to '.$user->email.'.');
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

        if($user->archived_at){
            return back()->with('warning','Official History records are read-only.');
        }

        $oldEmail=$user->email;

        $validated=$request->validateWithBag('editUser',[
            'edit_user_id'=>['nullable','integer'],
            'first_name'=>['required','string','max:100'],
            'last_name'=>['required','string','max:100'],
            'email'=>['required','email','max:100','unique:users,email,'.$userId.',user_id'],
            'phone_number'=>['nullable','string','max:20','unique:users,phone_number,'.$userId.',user_id'],
            'barangay_id'=>['nullable','integer','exists:barangays,barangay_id'],
            'role'=>['required','string'],
            'status'=>['required','in:active,inactive'],
        ]);

        unset($validated['edit_user_id']);

        if($user->role === 'sk_president'){
            if($validated['role'] !== 'sk_president'){
                return back()->withInput()->withErrors([
                    'role'=>'The SK President role can only be changed through the President succession process.',
                ],'editUser');
            }

            $validated['role']='sk_president';
            $validated['barangay_id']=null;
            $validated['status']=(int)$user->is_verified === 0 ? 'inactive' : 'active';
        }else{
            if(!in_array($validated['role'],['sk_chairman','sk_secretary'],true)){
                return back()->withInput()->withErrors([
                    'role'=>'SK President can only be assigned through the President succession process.',
                ],'editUser');
            }

            if(empty($validated['barangay_id'])){
                return back()->withInput()->withErrors([
                    'barangay_id'=>'Barangay is required for Chairman and Secretary accounts.',
                ],'editUser');
            }

            $duplicate=User::query()
                ->where('user_id','!=',$userId)
                ->where('barangay_id',$validated['barangay_id'])
                ->where('role',$validated['role'])
                ->whereNull('archived_at')
                ->exists();

            if($duplicate){
                return back()->withInput()->withErrors([
                    'barangay_id'=>'This barangay already has a current '.$this->roleName($validated['role']).'.',
                ],'editUser');
            }

            if((int)$user->is_verified === 0){
                $validated['status']='inactive';
            }
        }

        $emailChanged=strtolower($oldEmail) !== strtolower($validated['email']);
        $newToken=null;

        DB::transaction(function() use($user,$validated,$oldEmail,$emailChanged,&$newToken){
            $user->update($validated);

            DB::table('official_terms')
                ->where('user_id',$user->user_id)
                ->whereIn('status',['pending','current'])
                ->update([
                    'role'=>$user->role,
                    'barangay_id'=>$user->barangay_id,
                ]);

            if((int)$user->is_verified === 0 && $emailChanged){
                DB::table('password_reset_tokens')->where('email',$oldEmail)->delete();
                $newToken=Str::random(64);

                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email'=>$user->email],
                    ['token'=>Hash::make($newToken),'created_at'=>now()]
                );
            }
        });

        if($newToken){
            $setupLink=route('password.setup',['token'=>$newToken,'email'=>$user->email]);

            try{
                Mail::send('email.account-setup',[
                    'user'=>$user,
                    'setupLink'=>$setupLink,
                ],function($message) use($user){
                    $message->to($user->email,trim($user->first_name.' '.$user->last_name))
                        ->subject('New SK360 Password Setup Link');
                });
            }catch(\Throwable $e){
                \Log::error('Updated setup email failed for '.$user->email.': '.$e->getMessage());
                return back()->with('warning','User details were updated, but the new setup email could not be sent. Use Resend Setup Link.');
            }

            return back()->with('success','User details updated. A new setup link was sent to the new email address.');
        }

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

        if($user->archived_at){
            return back()->with('warning','Archived accounts cannot be activated or deactivated.');
        }

        if($user->role === 'sk_president'){
            return back()->withErrors([
                'status'=>'The SK President account cannot be deactivated.',
            ]);
        }

        if((int)$user->is_verified === 0){
            return back()->with('warning','This official has not completed account setup yet.');
        }

        if($user->status !== 'active'){
            $duplicate=User::query()
                ->where('user_id','!=',$userId)
                ->where('barangay_id',$user->barangay_id)
                ->where('role',$user->role)
                ->whereNull('archived_at')
                ->exists();

            if($duplicate){
                return back()->with(
                    'warning',
                    'Another current '.$this->roleName($user->role).' already exists in this barangay.'
                );
            }
        }

        $user->status=$user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return back()->with('success','User status updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | START NEW ADMINISTRATION TERM
    |--------------------------------------------------------------------------
    */
    public function startNewTerm(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $validated=$request->validateWithBag('newTerm',[
            'start_year'=>['required','integer','digits:4','min:2000','max:2100'],
            'end_year'=>['required','integer','digits:4','min:2000','max:2100'],
            'president_mode'=>['required','in:continue,assign_new'],
            'new_president_first_name'=>['nullable','required_if:president_mode,assign_new','string','max:100'],
            'new_president_last_name'=>['nullable','required_if:president_mode,assign_new','string','max:100'],
            'new_president_email'=>['nullable','required_if:president_mode,assign_new','email','max:100','unique:users,email'],
            'new_president_phone'=>['nullable','string','max:20','unique:users,phone_number'],
        ]);

        if((int)$validated['end_year'] <= (int)$validated['start_year']){
            return back()->withInput()->withErrors([
                'end_year'=>'End year must be after the start year.',
            ],'newTerm');
        }

        $existingTerm=DB::table('administration_terms')
            ->where('start_year',$validated['start_year'])
            ->where('end_year',$validated['end_year'])
            ->exists();

        if($existingTerm){
            return back()->withInput()->withErrors([
                'start_year'=>'This administration term already exists.',
            ],'newTerm');
        }

        $currentTerm=$this->currentAdministrationTerm();
        $president=auth()->user();

        if($currentTerm){
            $pendingCount=DB::table('official_terms')
                ->where('term_id',$currentTerm->term_id)
                ->where('status','pending')
                ->count();

            if($pendingCount > 0){
                return back()->withInput()->withErrors([
                    'start_year'=>'Resolve or delete all pending official accounts before starting a new administration term.',
                ],'newTerm');
            }

            $unlinkedOfficials=DB::table('users as u')
                ->whereIn('u.role',['sk_chairman','sk_secretary'])
                ->whereNull('u.archived_at')
                ->whereNotExists(function($query) use($currentTerm){
                    $query->select(DB::raw(1))
                        ->from('official_terms as ot')
                        ->whereColumn('ot.user_id','u.user_id')
                        ->where('ot.term_id',$currentTerm->term_id)
                        ->whereIn('ot.status',['pending','current']);
                })
                ->count();

            if($unlinkedOfficials > 0){
                return back()->withInput()->withErrors([
                    'start_year'=>$unlinkedOfficials.' current official account(s) are not connected to the current administration term yet.',
                ],'newTerm');
            }

            $unlinkedCouncil=DB::table('sk_council')
                ->where('status','current')
                ->where(function($query) use($currentTerm){
                    $query->whereNull('term_id')
                        ->orWhere('term_id','!=',$currentTerm->term_id);
                })
                ->count();

            if($unlinkedCouncil > 0){
                return back()->withInput()->withErrors([
                    'start_year'=>$unlinkedCouncil.' current Treasurer/Councilor record(s) are not connected to the current administration term yet.',
                ],'newTerm');
            }
        }

        $existingPendingPresident=DB::table('official_terms')
            ->where('role','sk_president')
            ->where('status','pending')
            ->exists();

        if($existingPendingPresident){
            return back()->withInput()->withErrors([
                'president_mode'=>'Resolve the existing pending President succession before starting another administration.',
            ],'newTerm');
        }

        $successor=null;
        $successorToken=null;
        $newTermId=null;

        DB::transaction(function() use($validated,$currentTerm,$president,&$successor,&$successorToken,&$newTermId){
            if($currentTerm){
                $endingOfficials=DB::table('official_terms')
                    ->where('term_id',$currentTerm->term_id)
                    ->where('status','current')
                    ->whereIn('role',['sk_chairman','sk_secretary'])
                    ->get(['official_term_id','user_id']);

                $officialTermIds=$endingOfficials->pluck('official_term_id')->all();
                $userIds=$endingOfficials->pluck('user_id')->unique()->all();

                if(!empty($officialTermIds)){
                    DB::table('official_terms')
                        ->whereIn('official_term_id',$officialTermIds)
                        ->update([
                            'status'=>'completed',
                            'completed_at'=>now(),
                        ]);
                }

                if(!empty($userIds)){
                    DB::table('users')
                        ->whereIn('user_id',$userIds)
                        ->whereIn('role',['sk_chairman','sk_secretary'])
                        ->update([
                            'status'=>'inactive',
                            'archived_at'=>now(),
                        ]);
                }

                DB::table('sk_council')
                    ->where('term_id',$currentTerm->term_id)
                    ->where('status','current')
                    ->update([
                        'status'=>'completed',
                        'completed_at'=>now(),
                    ]);

                DB::table('official_terms')
                    ->where('user_id',$president->user_id)
                    ->where('term_id',$currentTerm->term_id)
                    ->where('role','sk_president')
                    ->where('status','current')
                    ->update([
                        'status'=>'completed',
                        'completed_at'=>now(),
                    ]);

                DB::table('administration_terms')
                    ->where('term_id',$currentTerm->term_id)
                    ->update([
                        'status'=>'completed',
                        'completed_at'=>now(),
                    ]);
            }

            $newTermId=DB::table('administration_terms')->insertGetId([
                'start_year'=>$validated['start_year'],
                'end_year'=>$validated['end_year'],
                'status'=>'current',
                'created_at'=>now(),
                'completed_at'=>null,
            ]);

            if($validated['president_mode'] === 'continue'){
                $president->status='active';
                $president->archived_at=null;
                $president->save();

                DB::table('official_terms')->insert([
                    'user_id'=>$president->user_id,
                    'term_id'=>$newTermId,
                    'barangay_id'=>null,
                    'role'=>'sk_president',
                    'status'=>'current',
                    'started_at'=>now(),
                    'completed_at'=>null,
                ]);
            }else{
                $successor=User::create([
                    'first_name'=>$validated['new_president_first_name'],
                    'last_name'=>$validated['new_president_last_name'],
                    'email'=>$validated['new_president_email'],
                    'phone_number'=>$validated['new_president_phone'] ?: null,
                    'barangay_id'=>null,
                    'role'=>'sk_president',
                    'password'=>Hash::make(Str::random(64)),
                    'is_verified'=>0,
                    'status'=>'inactive',
                    'term_start'=>null,
                    'term_end'=>null,
                    'archived_at'=>null,
                ]);

                DB::table('official_terms')->insert([
                    'user_id'=>$successor->user_id,
                    'term_id'=>$newTermId,
                    'barangay_id'=>null,
                    'role'=>'sk_president',
                    'status'=>'pending',
                    'started_at'=>null,
                    'completed_at'=>null,
                ]);

                $successorToken=Str::random(64);

                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email'=>$successor->email],
                    ['token'=>Hash::make($successorToken),'created_at'=>now()]
                );

                DB::table('official_terms')->insert([
                    'user_id'=>$president->user_id,
                    'term_id'=>$newTermId,
                    'barangay_id'=>null,
                    'role'=>'sk_president',
                    'status'=>'current',
                    'started_at'=>now(),
                    'completed_at'=>null,
                ]);

                $president->status='active';
                $president->archived_at=null;
                $president->save();
            }
        });

        if($validated['president_mode'] === 'assign_new' && $successor && $successorToken){
            $setupLink=route('password.setup',[
                'token'=>$successorToken,
                'email'=>$successor->email,
            ]);

            try{
                Mail::send('email.account-setup',[
                    'user'=>$successor,
                    'setupLink'=>$setupLink,
                ],function($message) use($successor){
                    $message->to($successor->email,trim($successor->first_name.' '.$successor->last_name))
                        ->subject('Set Up Your SK360 President Account');
                });
            }catch(\Throwable $e){
                \Log::error('President succession setup email failed: '.$e->getMessage());

                return redirect()
                    ->route('sk_pres.user-management',['tab'=>'current'])
                    ->with('warning','The '.$validated['start_year'].' - '.$validated['end_year'].' administration is active and the new President account was created, but the setup email failed. Your current President account remains active. Use Resend Setup Link.');
            }

            return redirect()
                ->route('sk_pres.user-management',['tab'=>'current'])
                ->with('success','The '.$validated['start_year'].' - '.$validated['end_year'].' administration is active. The new President is pending account setup, and your current President account will remain active until the handover is completed.');
        }

        return redirect()
            ->route('sk_pres.user-management',['tab'=>'current'])
            ->with('success','The '.$validated['start_year'].' - '.$validated['end_year'].' administration is now active and the current SK President will continue for the new term.');
    }

    /*
    |--------------------------------------------------------------------------
    | REAPPOINT SK CHAIRMAN
    |--------------------------------------------------------------------------
    */
    public function reappoint(int $officialTermId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $previousTerm=DB::table('official_terms')
            ->where('official_term_id',$officialTermId)
            ->where('status','completed')
            ->first();

        if(!$previousTerm){
            return back()->with('warning','The selected historical official record was not found.');
        }

        if($previousTerm->role !== 'sk_chairman'){
            return back()->with('warning','Only SK Chairmen can be reappointed from this screen.');
        }

        $currentTerm=$this->currentAdministrationTerm();

        if(!$currentTerm){
            return back()->with('warning','There is no active administration term.');
        }

        if((int)$previousTerm->term_id === (int)$currentTerm->term_id){
            return back()->with('warning','This record already belongs to the current administration.');
        }

        $user=User::where('user_id',$previousTerm->user_id)->firstOrFail();

        if((int)$user->is_verified !== 1){
            return back()->with('warning','This account must be verified before it can be reappointed.');
        }

        $existingChairmanRecord=DB::table('official_terms')
            ->where('user_id',$user->user_id)
            ->where('term_id',$currentTerm->term_id)
            ->where('role','sk_chairman')
            ->exists();

        if($existingChairmanRecord){
            return back()->with('warning','This official already has an SK Chairman record in the current administration.');
        }

        $existingAssignment=DB::table('official_terms')
            ->where('user_id',$user->user_id)
            ->where('term_id',$currentTerm->term_id)
            ->whereIn('status',['pending','current'])
            ->exists();

        if($existingAssignment){
            return back()->with('warning','This official already has another active assignment in the current administration.');
        }

        $barangayOccupied=DB::table('official_terms')
            ->where('term_id',$currentTerm->term_id)
            ->where('barangay_id',$previousTerm->barangay_id)
            ->where('role','sk_chairman')
            ->whereIn('status',['pending','current'])
            ->exists();

        if($barangayOccupied){
            return back()->with('warning','This barangay already has a current or pending SK Chairman.');
        }

        DB::transaction(function() use($user,$previousTerm,$currentTerm){
            $user->update([
                'role'=>'sk_chairman',
                'barangay_id'=>$previousTerm->barangay_id,
                'status'=>'active',
                'archived_at'=>null,
            ]);

            DB::table('official_terms')->insert([
                'user_id'=>$user->user_id,
                'term_id'=>$currentTerm->term_id,
                'barangay_id'=>$previousTerm->barangay_id,
                'role'=>'sk_chairman',
                'status'=>'current',
                'started_at'=>now(),
                'completed_at'=>null,
            ]);
        });

        return redirect()
            ->route('sk_pres.user-management',['tab'=>'current'])
            ->with('success',trim($user->first_name.' '.$user->last_name).' was reappointed as SK Chairman for the '.$currentTerm->start_year.' - '.$currentTerm->end_year.' administration.');
    }

    /*
    |--------------------------------------------------------------------------
    | END TERM / ARCHIVE
    |--------------------------------------------------------------------------
    */
    public function archive(int $userId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $user=User::where('user_id',$userId)->firstOrFail();

        if($user->role === 'sk_president'){
            return back()->with('warning','The current SK President cannot be archived from this screen yet.');
        }

        if($user->archived_at){
            return back()->with('warning','This official is already archived.');
        }

        if((int)$user->is_verified === 0){
            return back()->with('warning','Pending accounts should be deleted instead of archived.');
        }

        $officialTerm=DB::table('official_terms')
            ->where('user_id',$user->user_id)
            ->where('role',$user->role)
            ->where('status','current')
            ->orderByDesc('official_term_id')
            ->first();

        if(!$officialTerm){
            return back()->with('warning','This official is not connected to a current administration term yet.');
        }

        $administration=DB::table('administration_terms')
            ->where('term_id',$officialTerm->term_id)
            ->first();

        DB::transaction(function() use($user,$officialTerm){
            DB::table('official_terms')
                ->where('official_term_id',$officialTerm->official_term_id)
                ->update([
                    'status'=>'completed',
                    'completed_at'=>now(),
                ]);

            $user->status='inactive';
            $user->archived_at=now();
            $user->save();

            DB::table('password_reset_tokens')
                ->where('email',$user->email)
                ->delete();
        });

        $historyTerm=$administration
            ? $administration->start_year.'-'.$administration->end_year
            : '';

        return redirect()
            ->route('sk_pres.user-management',[
                'tab'=>'history',
                'history_term'=>$historyTerm,
            ])
            ->with('success',trim($user->first_name.' '.$user->last_name).' was moved to Official History.');
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE PENDING ACCOUNT
    |--------------------------------------------------------------------------
    */
    public function destroy(int $userId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $user=User::where('user_id',$userId)->firstOrFail();

        if($user->archived_at){
            return back()->with('warning','Archived officials cannot be permanently deleted from User Management.');
        }

        if((int)$user->is_verified === 1){
            return back()->with('warning','Activated officials must use End Term / Archive instead of Delete.');
        }

        if($user->role === 'sk_president'){
            $pendingTerm=DB::table('official_terms')
                ->where('user_id',$user->user_id)
                ->where('role','sk_president')
                ->where('status','pending')
                ->orderByDesc('official_term_id')
                ->first();

            if(!$pendingTerm){
                return back()->with('warning','The active SK President account cannot be deleted.');
            }

            $outgoingPresident=auth()->user();

            DB::transaction(function() use($user,$pendingTerm,$outgoingPresident){
                DB::table('password_reset_tokens')->where('email',$user->email)->delete();
                DB::table('official_terms')->where('official_term_id',$pendingTerm->official_term_id)->delete();
                $user->delete();

                $existingContinuation=DB::table('official_terms')
                    ->where('user_id',$outgoingPresident->user_id)
                    ->where('term_id',$pendingTerm->term_id)
                    ->where('role','sk_president')
                    ->exists();

                if(!$existingContinuation){
                    DB::table('official_terms')->insert([
                        'user_id'=>$outgoingPresident->user_id,
                        'term_id'=>$pendingTerm->term_id,
                        'barangay_id'=>null,
                        'role'=>'sk_president',
                        'status'=>'current',
                        'started_at'=>now(),
                        'completed_at'=>null,
                    ]);
                }

                $outgoingPresident->status='active';
                $outgoingPresident->archived_at=null;
                $outgoingPresident->save();
            });

            return back()->with('success','Pending President succession cancelled. Your current President account will continue for this administration.');
        }

        DB::transaction(function() use($user,$userId){
            DB::table('email_verifications')->where('user_id',$userId)->delete();
            DB::table('password_reset_tokens')->where('email',$user->email)->delete();
            DB::table('official_terms')
                ->where('user_id',$userId)
                ->whereIn('status',['pending','current'])
                ->delete();
            $user->delete();
        });

        return back()->with('success','Pending account deleted successfully.');
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
                'label'=>'Current Officials',
                'value'=>DB::table('users')
                    ->whereIn('role',['sk_president','sk_chairman','sk_secretary'])
                    ->whereNull('archived_at')
                    ->count(),
                'border'=>'border-red-300',
                'iconBg'=>'bg-red-50',
                'icon'=>'&#128101;',
                'iconColor'=>'text-red-500',
            ],
            [
                'label'=>'SK Chairmen',
                'value'=>DB::table('users')
                    ->where('role','sk_chairman')
                    ->whereNull('archived_at')
                    ->count(),
                'border'=>'border-green-300',
                'iconBg'=>'bg-green-50',
                'icon'=>'&#128737;',
                'iconColor'=>'text-green-500',
            ],
            [
                'label'=>'SK Secretaries',
                'value'=>DB::table('users')
                    ->where('role','sk_secretary')
                    ->whereNull('archived_at')
                    ->count(),
                'border'=>'border-blue-300',
                'iconBg'=>'bg-blue-50',
                'icon'=>'&#128196;',
                'iconColor'=>'text-blue-500',
            ],
            [
                'label'=>'Archived Officials',
                'value'=>DB::table('users')
                    ->whereIn('role',['sk_president','sk_chairman','sk_secretary'])
                    ->whereNotNull('archived_at')
                    ->count(),
                'border'=>'border-gray-300',
                'iconBg'=>'bg-gray-100',
                'icon'=>'&#128451;',
                'iconColor'=>'text-gray-500',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CURRENT USER GROUPS
    |--------------------------------------------------------------------------
    */
    protected function userGroups(string $status=''): array
    {
        return [
            [
                'label'=>'SK Federation President / System Admin',
                'description'=>'Current/caretaker President and any pending successor',
                'count'=>$this->currentRoleCount('sk_president'),
                'badgeColor'=>'bg-red-500',
                'iconColor'=>'text-red-400',
                'icon'=>'&#128198;',
                'users'=>$this->usersByRole(['sk_president'],$status),
            ],
            [
                'label'=>'SK Chairmen',
                'description'=>'Current and pending barangay youth leaders',
                'count'=>$this->currentRoleCount('sk_chairman'),
                'badgeColor'=>'bg-green-500',
                'iconColor'=>'text-green-400',
                'icon'=>'&#128737;',
                'users'=>$this->usersByRole(['sk_chairman'],$status),
            ],
            [
                'label'=>'SK Secretaries',
                'description'=>'Current and pending documentation officers',
                'count'=>$this->currentRoleCount('sk_secretary'),
                'badgeColor'=>'bg-blue-500',
                'iconColor'=>'text-blue-400',
                'icon'=>'&#128196;',
                'users'=>$this->usersByRole(['sk_secretary'],$status),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | GET CURRENT USERS BY ROLE
    |--------------------------------------------------------------------------
    */
    protected function usersByRole(array $roles,string $status='')
    {
        $query=DB::table('users as u')
            ->leftJoin('barangays as b','u.barangay_id','=','b.barangay_id')
            ->leftJoin('official_terms as ot',function($join){
                $join->on('ot.user_id','=','u.user_id')
                    ->whereIn('ot.status',['pending','current']);
            })
            ->leftJoin('administration_terms as t','ot.term_id','=','t.term_id')
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
                'u.archived_at',
                'u.created_at',
                'b.barangay_name',
                't.start_year as admin_start_year',
                't.end_year as admin_end_year'
            )
            ->whereIn('u.role',$roles)
            ->whereNull('u.archived_at');

        if(in_array($status,['active','inactive'],true)){
            $query->where('u.status',$status);
        }

        if(in_array('sk_chairman',$roles,true) || in_array('sk_secretary',$roles,true)){
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

    /*
    |--------------------------------------------------------------------------
    | HISTORY TERMS
    |--------------------------------------------------------------------------
    */
    protected function historyTerms()
    {
        return DB::table('administration_terms as t')
            ->join('official_terms as ot','t.term_id','=','ot.term_id')
            ->where('ot.status','completed')
            ->select('t.term_id','t.start_year','t.end_year')
            ->distinct()
            ->orderByDesc('t.start_year')
            ->orderByDesc('t.end_year')
            ->get()
            ->map(fn($term)=>[
                'value'=>$term->start_year.'-'.$term->end_year,
                'label'=>$term->start_year.' - '.$term->end_year,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | OFFICIAL HISTORY
    |--------------------------------------------------------------------------
    */
    protected function officialHistory(string $term,int $barangayId=0,string $role='')
    {
        $query=DB::table('official_terms as ot')
            ->join('administration_terms as t','ot.term_id','=','t.term_id')
            ->join('users as u','ot.user_id','=','u.user_id')
            ->leftJoin('barangays as b','ot.barangay_id','=','b.barangay_id')
            ->select(
                'ot.official_term_id',
                'ot.user_id',
                'ot.barangay_id',
                'ot.role',
                'ot.status as term_status',
                'ot.started_at',
                'ot.completed_at',
                't.term_id',
                't.start_year',
                't.end_year',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.phone_number',
                'b.barangay_name'
            )
            ->where('ot.status','completed');

        if(preg_match('/^(\d{4})-(\d{4})$/',$term,$matches)){
            $query->where('t.start_year',(int)$matches[1])
                ->where('t.end_year',(int)$matches[2]);
        }else{
            $query->whereRaw('1=0');
        }

        if($barangayId > 0){
            $query->where('ot.barangay_id',$barangayId);
        }

        if(in_array($role,['sk_president','sk_chairman','sk_secretary'],true)){
            $query->where('ot.role',$role);
        }

        return $query
            ->orderByRaw("CASE WHEN ot.role='sk_president' THEN 0 ELSE 1 END")
            ->orderByRaw('b.barangay_name IS NULL')
            ->orderBy('b.barangay_name')
            ->orderByRaw("CASE ot.role WHEN 'sk_chairman' THEN 1 WHEN 'sk_secretary' THEN 2 ELSE 3 END")
            ->orderBy('u.first_name')
            ->orderBy('u.last_name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORY STATS
    |--------------------------------------------------------------------------
    */
    protected function historyStats($users): array
    {
        return [
            [
                'label'=>'Barangays',
                'value'=>$users->whereNotNull('barangay_id')->pluck('barangay_id')->unique()->count(),
                'icon'=>'&#128205;',
            ],
            [
                'label'=>'SK Chairmen',
                'value'=>$users->where('role','sk_chairman')->count(),
                'icon'=>'&#128737;',
            ],
            [
                'label'=>'SK Secretaries',
                'value'=>$users->where('role','sk_secretary')->count(),
                'icon'=>'&#128196;',
            ],
            [
                'label'=>'Total Officials',
                'value'=>$users->count(),
                'icon'=>'&#128101;',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORY GROUPS
    |--------------------------------------------------------------------------
    */
    protected function historyGroups($users): array
    {
        $groups=[];

        $presidents=$users->where('role','sk_president')->values();

        if($presidents->isNotEmpty()){
            $groups[]=[
                'key'=>'federation',
                'label'=>'SK Federation / System Administration',
                'users'=>$presidents,
            ];
        }

        $barangayUsers=$users
            ->where('role','!=','sk_president')
            ->groupBy(fn($user)=>$user->barangay_id ?: 'unassigned');

        foreach($barangayUsers as $barangayId=>$members){
            $name=$members->first()->barangay_name ?? 'Unassigned';

            $groups[]=[
                'key'=>'barangay_'.$barangayId,
                'label'=>'Barangay '.$name,
                'users'=>$members->values(),
            ];
        }

        return $groups;
    }

    protected function pendingPresidentForTerm(?int $termId)
    {
        if(!$termId){
            return null;
        }

        return DB::table('official_terms as ot')
            ->join('users as u','ot.user_id','=','u.user_id')
            ->where('ot.term_id',$termId)
            ->where('ot.role','sk_president')
            ->where('ot.status','pending')
            ->select('u.user_id','u.first_name','u.last_name','u.email','u.phone_number','ot.official_term_id')
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | CURRENT ADMINISTRATION TERM
    |--------------------------------------------------------------------------
    */
    protected function currentAdministrationTerm()
    {
        return DB::table('administration_terms')
            ->where('status','current')
            ->orderByDesc('term_id')
            ->first();
    }

    protected function currentRoleCount(string $role): int
    {
        return DB::table('users')
            ->where('role',$role)
            ->whereNull('archived_at')
            ->count();
    }

    protected function roleName(string $role): string
    {
        return match($role){
            'sk_chairman'=>'SK Chairman',
            'sk_secretary'=>'SK Secretary',
            'sk_president'=>'SK President',
            default=>'official',
        };
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