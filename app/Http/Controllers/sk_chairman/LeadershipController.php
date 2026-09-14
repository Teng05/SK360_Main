<?php

namespace App\Http\Controllers\sk_chairman;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LeadershipController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $user=auth()->user();
        $fullName=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $barangayId=(int)($user->barangay_id ?? 0);
        $barangayName=$user->barangay->barangay_name ?? 'Barangay';
        $currentAdministration=$this->currentAdministrationTerm();

        $secretaryModel=User::query()
            ->where('barangay_id',$barangayId)
            ->where('role','sk_secretary')
            ->whereNull('archived_at')
            ->orderByDesc('created_at')
            ->first();

        $hasCurrentSecretary=User::query()
            ->where('barangay_id',$barangayId)
            ->where('role','sk_secretary')
            ->whereNull('archived_at')
            ->exists();

        $secretaryIsReappointment=false;

        if($secretaryModel && (int)$secretaryModel->is_verified === 0){
            $secretaryIsReappointment=DB::table('official_terms')
                ->where('user_id',$secretaryModel->user_id)
                ->where('role','sk_secretary')
                ->where('status','completed')
                ->exists();
        }

        $secretary=$secretaryModel ? [
            'user_id'=>$secretaryModel->user_id,
            'name'=>trim(($secretaryModel->first_name ?? '').' '.($secretaryModel->last_name ?? '')),
            'first_name'=>$secretaryModel->first_name,
            'last_name'=>$secretaryModel->last_name,
            'position'=>'SK Secretary',
            'email'=>$secretaryModel->email,
            'phone'=>$secretaryModel->phone_number,
            'term'=>$this->officialTermLabel($secretaryModel),
            'status'=>$secretaryModel->status,
            'is_verified'=>(int)$secretaryModel->is_verified,
            'is_reappointment'=>$secretaryIsReappointment,
            'source'=>'user',
        ] : null;

        $treasurerRow=null;
        $kagawads=collect();

        if($currentAdministration){
            $treasurerRow=DB::table('sk_council')
                ->where('barangay_id',$barangayId)
                ->where('term_id',$currentAdministration->term_id)
                ->where('status','current')
                ->whereRaw('LOWER(position)=?',['sk treasurer'])
                ->orderByDesc('created_at')
                ->first();

            $kagawads=DB::table('sk_council')
                ->where('barangay_id',$barangayId)
                ->where('term_id',$currentAdministration->term_id)
                ->where('status','current')
                ->where(function($query){
                    $query->whereRaw('LOWER(position) LIKE ?',['%councilor%'])
                        ->orWhereRaw('LOWER(position) LIKE ?',['%kagawad%']);
                })
                ->orderBy('name')
                ->get()
                ->map(fn($row)=>[
                    'council_id'=>$row->council_id,
                    'name'=>$row->name,
                    'position'=>$row->position,
                    'email'=>$row->email,
                    'phone'=>$row->phone,
                    'term'=>$row->term,
                    'source'=>'council',
                ]);
        }

        $treasurer=$treasurerRow ? [
            'council_id'=>$treasurerRow->council_id,
            'name'=>$treasurerRow->name,
            'position'=>'SK Treasurer',
            'email'=>$treasurerRow->email,
            'phone'=>$treasurerRow->phone,
            'term'=>$treasurerRow->term,
            'source'=>'council',
        ] : null;

        $chairman=[
            'user_id'=>$user->user_id,
            'name'=>$fullName,
            'position'=>'SK Chairman',
            'email'=>$user->email,
            'phone'=>$user->phone_number,
            'term'=>$this->officialTermLabel($user),
            'status'=>$user->status,
            'is_verified'=>(int)$user->is_verified,
            'source'=>'user',
        ];

        $executives=collect([$chairman]);

        if($secretary){
            $executives->push($secretary);
        }

        if($treasurer){
            $executives->push($treasurer);
        }

        $councilMembers=$executives->merge($kagawads)->values();
        $reappointment=$this->formerLeadershipMembers($barangayId,$currentAdministration);

        return view('sk_chairman.leadership',[
            'fullName'=>$fullName,
            'barangayName'=>$barangayName,
            'initials'=>strtoupper(substr($user->first_name ?? 'S',0,1).substr($user->last_name ?? 'K',0,1)),
            'menuItems'=>$this->menuItems(),
            'currentUrl'=>url()->current(),
            'councilMembers'=>$councilMembers,
            'executives'=>$executives,
            'kagawads'=>$kagawads,
            'secretary'=>$secretary,
            'treasurer'=>$treasurer,
            'canAddSecretary'=>!$hasCurrentSecretary,
            'currentAdministration'=>$currentAdministration,
            'formerOfficials'=>$reappointment['officials'],
            'reappointmentTerms'=>$reappointment['terms'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SINGLE ADD COUNCILOR
    |--------------------------------------------------------------------------
    */
    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $currentTerm=$this->currentAdministrationTerm();

        if(!$currentTerm){
            return back()->withInput()->with('warning','There is no active administration term.');
        }

        $validated=$request->validateWithBag('councilorAdd',[
            'name'=>['required','string','max:255'],
            'email'=>['nullable','email','max:255'],
            'phone'=>['nullable','string','max:20'],
        ]);

        $normalizedName=strtolower(trim(preg_replace('/\s+/',' ',$validated['name'])));

        $existing=DB::table('sk_council')
            ->where('barangay_id',auth()->user()->barangay_id)
            ->where('term_id',$currentTerm->term_id)
            ->where('status','current')
            ->where(function($query){
                $query->whereRaw('LOWER(position) LIKE ?',['%councilor%'])
                    ->orWhereRaw('LOWER(position) LIKE ?',['%kagawad%']);
            })
            ->whereRaw('LOWER(TRIM(name))=?',[$normalizedName])
            ->exists();

        if($existing){
            return back()
                ->withInput()
                ->withErrors([
                    'name'=>'This person is already listed as an SK Councilor for the current administration.',
                ],'councilorAdd');
        }

        DB::table('sk_council')->insert([
            'barangay_id'=>auth()->user()->barangay_id,
            'term_id'=>$currentTerm->term_id,
            'name'=>trim($validated['name']),
            'position'=>'SK Councilor',
            'email'=>$validated['email'] ?? null,
            'phone'=>$validated['phone'] ?? null,
            'term'=>$currentTerm->start_year.'-'.$currentTerm->end_year,
            'status'=>'current',
            'profile_img'=>'default.png',
            'created_at'=>now(),
            'completed_at'=>null,
        ]);

        return redirect()
            ->route('sk_chairman.leadership')
            ->with('success','SK Councilor added for the '.$currentTerm->start_year.' - '.$currentTerm->end_year.' administration.');
    }

    /*
    |--------------------------------------------------------------------------
    | BULK ADD COUNCILORS
    |--------------------------------------------------------------------------
    */
    public function storeBulkCouncilors(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $currentTerm=$this->currentAdministrationTerm();

        if(!$currentTerm){
            return back()->withInput()->with('warning','There is no active administration term.');
        }

        $validated=$request->validateWithBag('bulkCouncilors',[
            'councilors'=>['required','array','min:1'],
            'councilors.*.name'=>['required','string','max:255'],
            'councilors.*.email'=>['nullable','email','max:255'],
            'councilors.*.phone'=>['nullable','string','max:20'],
        ]);

        $barangayId=auth()->user()->barangay_id;
        $names=[];
        $emails=[];
        $phones=[];

        foreach($validated['councilors'] as $index=>$councilor){
            $rowNumber=$index+1;
            $normalizedName=strtolower(trim(preg_replace('/\s+/',' ',$councilor['name'])));
            $email=strtolower(trim($councilor['email'] ?? ''));
            $phone=preg_replace('/\D+/','',$councilor['phone'] ?? '');

            if(in_array($normalizedName,$names,true)){
                return back()->withInput()->withErrors([
                    'councilors'=>"Councilor #{$rowNumber} has the same name as another councilor in this bulk form.",
                ],'bulkCouncilors');
            }

            $names[]=$normalizedName;

            if($email !== ''){
                if(in_array($email,$emails,true)){
                    return back()->withInput()->withErrors([
                        'councilors'=>"Councilor #{$rowNumber} has a duplicate email in this bulk form.",
                    ],'bulkCouncilors');
                }

                $emails[]=$email;
            }

            if($phone !== ''){
                if(in_array($phone,$phones,true)){
                    return back()->withInput()->withErrors([
                        'councilors'=>"Councilor #{$rowNumber} has a duplicate phone number in this bulk form.",
                    ],'bulkCouncilors');
                }

                $phones[]=$phone;
            }

            $existing=DB::table('sk_council')
                ->where('barangay_id',$barangayId)
                ->where('term_id',$currentTerm->term_id)
                ->where('status','current')
                ->where(function($query){
                    $query->whereRaw('LOWER(position) LIKE ?',['%councilor%'])
                        ->orWhereRaw('LOWER(position) LIKE ?',['%kagawad%']);
                })
                ->whereRaw('LOWER(TRIM(name))=?',[$normalizedName])
                ->exists();

            if($existing){
                return back()->withInput()->withErrors([
                    'councilors'=>"{$councilor['name']} is already listed as an SK Councilor for the current administration.",
                ],'bulkCouncilors');
            }
        }

        DB::transaction(function() use($validated,$barangayId,$currentTerm){
            foreach($validated['councilors'] as $councilor){
                DB::table('sk_council')->insert([
                    'barangay_id'=>$barangayId,
                    'term_id'=>$currentTerm->term_id,
                    'name'=>trim($councilor['name']),
                    'position'=>'SK Councilor',
                    'email'=>!empty($councilor['email']) ? trim($councilor['email']) : null,
                    'phone'=>!empty($councilor['phone']) ? trim($councilor['phone']) : null,
                    'term'=>$currentTerm->start_year.'-'.$currentTerm->end_year,
                    'status'=>'current',
                    'profile_img'=>'default.png',
                    'created_at'=>now(),
                    'completed_at'=>null,
                ]);
            }
        });

        return redirect()
            ->route('sk_chairman.leadership')
            ->with('success',count($validated['councilors']).' SK Councilor(s) added for the '.$currentTerm->start_year.' - '.$currentTerm->end_year.' administration.');
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE COUNCILOR
    |--------------------------------------------------------------------------
    */
    public function updateCouncilor(Request $request,int $councilId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $currentTerm=$this->currentAdministrationTerm();

        abort_unless($currentTerm,404);

        $councilor=DB::table('sk_council')
            ->where('council_id',$councilId)
            ->where('barangay_id',auth()->user()->barangay_id)
            ->where('term_id',$currentTerm->term_id)
            ->where('status','current')
            ->where(function($query){
                $query->whereRaw('LOWER(position) LIKE ?',['%councilor%'])
                    ->orWhereRaw('LOWER(position) LIKE ?',['%kagawad%']);
            })
            ->first();

        abort_unless($councilor,404);

        $validated=$request->validateWithBag('councilorEdit',[
            'edit_councilor_name'=>['required','string','max:255'],
            'edit_councilor_email'=>['nullable','email','max:255'],
            'edit_councilor_phone'=>['nullable','string','max:20'],
        ]);

        DB::table('sk_council')
            ->where('council_id',$councilId)
            ->update([
                'name'=>$validated['edit_councilor_name'],
                'email'=>$validated['edit_councilor_email'] ?: null,
                'phone'=>$validated['edit_councilor_phone'] ?: null,
            ]);

        return redirect()
            ->route('sk_chairman.leadership')
            ->with('success','SK Councilor details updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | ADD TREASURER
    |--------------------------------------------------------------------------
    */
    public function storeTreasurer(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $currentTerm=$this->currentAdministrationTerm();

        if(!$currentTerm){
            return back()->withInput()->with('warning','There is no active administration term.');
        }

        $validated=$request->validateWithBag('treasurerAdd',[
            'treasurer_name'=>['required','string','max:255'],
            'treasurer_email'=>['nullable','email','max:255'],
            'treasurer_phone'=>['nullable','string','max:20'],
        ]);

        $exists=DB::table('sk_council')
            ->where('barangay_id',auth()->user()->barangay_id)
            ->where('term_id',$currentTerm->term_id)
            ->where('status','current')
            ->whereRaw('LOWER(position)=?',['sk treasurer'])
            ->exists();

        if($exists){
            return back()
                ->withInput()
                ->withErrors([
                    'treasurer'=>'This barangay already has an SK Treasurer for the current administration.',
                ],'treasurerAdd');
        }

        DB::table('sk_council')->insert([
            'barangay_id'=>auth()->user()->barangay_id,
            'term_id'=>$currentTerm->term_id,
            'name'=>$validated['treasurer_name'],
            'position'=>'SK Treasurer',
            'email'=>$validated['treasurer_email'] ?? null,
            'phone'=>$validated['treasurer_phone'] ?? null,
            'term'=>$currentTerm->start_year.'-'.$currentTerm->end_year,
            'status'=>'current',
            'profile_img'=>'default.png',
            'created_at'=>now(),
            'completed_at'=>null,
        ]);

        return redirect()
            ->route('sk_chairman.leadership')
            ->with('success','SK Treasurer added for the '.$currentTerm->start_year.' - '.$currentTerm->end_year.' administration.');
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE TREASURER
    |--------------------------------------------------------------------------
    */
    public function updateTreasurer(Request $request,int $councilId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $currentTerm=$this->currentAdministrationTerm();

        abort_unless($currentTerm,404);

        $treasurer=DB::table('sk_council')
            ->where('council_id',$councilId)
            ->where('barangay_id',auth()->user()->barangay_id)
            ->where('term_id',$currentTerm->term_id)
            ->where('status','current')
            ->whereRaw('LOWER(position)=?',['sk treasurer'])
            ->first();

        abort_unless($treasurer,404);

        $validated=$request->validateWithBag('treasurerEdit',[
            'edit_treasurer_name'=>['required','string','max:255'],
            'edit_treasurer_email'=>['nullable','email','max:255'],
            'edit_treasurer_phone'=>['nullable','string','max:20'],
        ]);

        DB::table('sk_council')
            ->where('council_id',$councilId)
            ->update([
                'name'=>$validated['edit_treasurer_name'],
                'email'=>$validated['edit_treasurer_email'] ?: null,
                'phone'=>$validated['edit_treasurer_phone'] ?: null,
            ]);

        return redirect()
            ->route('sk_chairman.leadership')
            ->with('success','SK Treasurer details updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | ADD SECRETARY ACCOUNT
    |--------------------------------------------------------------------------
    */
    public function storeSecretary(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $chairman=auth()->user();
        $currentTerm=$this->currentAdministrationTerm();

        if(!$currentTerm){
            return back()->withInput()->with('warning','There is no active administration term.');
        }

        $chairmanAssignment=DB::table('official_terms')
            ->where('user_id',$chairman->user_id)
            ->where('term_id',$currentTerm->term_id)
            ->where('role','sk_chairman')
            ->where('status','current')
            ->exists();

        if(!$chairmanAssignment){
            return back()->withInput()->with('warning','Your Chairman account is not connected to the current administration term.');
        }

        $validated=$request->validateWithBag('secretaryAdd',[
            'secretary_first_name'=>['required','string','max:100'],
            'secretary_last_name'=>['required','string','max:100'],
            'secretary_email'=>['required','email','max:100','unique:users,email'],
            'secretary_phone'=>['nullable','string','max:20','unique:users,phone_number'],
        ]);

        $existing=User::query()
            ->where('barangay_id',$chairman->barangay_id)
            ->where('role','sk_secretary')
            ->whereNull('archived_at')
            ->exists();

        if($existing){
            return back()
                ->withInput()
                ->withErrors([
                    'secretary'=>'Your barangay already has a current or pending SK Secretary.',
                ],'secretaryAdd');
        }

        $token=Str::random(64);

        $secretary=DB::transaction(function() use($validated,$chairman,$currentTerm,$token){
            $user=User::create([
                'first_name'=>$validated['secretary_first_name'],
                'last_name'=>$validated['secretary_last_name'],
                'email'=>$validated['secretary_email'],
                'phone_number'=>$validated['secretary_phone'] ?: null,
                'barangay_id'=>$chairman->barangay_id,
                'role'=>'sk_secretary',
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
                'barangay_id'=>$chairman->barangay_id,
                'role'=>'sk_secretary',
                'status'=>'pending',
                'started_at'=>null,
                'completed_at'=>null,
            ]);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email'=>$user->email],
                [
                    'token'=>Hash::make($token),
                    'created_at'=>now(),
                ]
            );

            return $user;
        });

        if(!$this->sendSetupEmail($secretary,$token)){
            return redirect()
                ->route('sk_chairman.leadership')
                ->with('warning','Secretary account created, but the setup email could not be sent. You can resend it from Leadership.');
        }

        return redirect()
            ->route('sk_chairman.leadership')
            ->with('success','SK Secretary account created for the '.$currentTerm->start_year.' - '.$currentTerm->end_year.' administration. A password setup link was sent to '.$secretary->email.'.');
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE SECRETARY
    |--------------------------------------------------------------------------
    */
    public function updateSecretary(Request $request,int $userId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $secretary=$this->secretaryForChairman($userId);
        $oldEmail=$secretary->email;

        $validated=$request->validateWithBag('secretaryEdit',[
            'edit_secretary_first_name'=>['required','string','max:100'],
            'edit_secretary_last_name'=>['required','string','max:100'],
            'edit_secretary_email'=>['required','email','max:100','unique:users,email,'.$userId.',user_id'],
            'edit_secretary_phone'=>['nullable','string','max:20','unique:users,phone_number,'.$userId.',user_id'],
        ]);

        $emailChanged=strtolower($oldEmail) !== strtolower($validated['edit_secretary_email']);

        $secretary->update([
            'first_name'=>$validated['edit_secretary_first_name'],
            'last_name'=>$validated['edit_secretary_last_name'],
            'email'=>$validated['edit_secretary_email'],
            'phone_number'=>$validated['edit_secretary_phone'] ?: null,
        ]);

        if((int)$secretary->is_verified === 0 && $emailChanged){
            DB::table('password_reset_tokens')
                ->where('email',$oldEmail)
                ->delete();

            $token=Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email'=>$secretary->email],
                [
                    'token'=>Hash::make($token),
                    'created_at'=>now(),
                ]
            );

            if(!$this->sendSetupEmail($secretary,$token)){
                return redirect()
                    ->route('sk_chairman.leadership')
                    ->with('warning','Secretary details updated, but the new setup email could not be sent.');
            }

            return redirect()
                ->route('sk_chairman.leadership')
                ->with('success','Secretary details updated. A new setup link was sent to the new email address.');
        }

        return redirect()
            ->route('sk_chairman.leadership')
            ->with('success','SK Secretary details updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | RESEND SECRETARY SETUP LINK
    |--------------------------------------------------------------------------
    */
    public function resendSecretarySetupLink(int $userId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $secretary=$this->secretaryForChairman($userId);

        if((int)$secretary->is_verified === 1){
            return back()->with(
                'warning',
                'This SK Secretary has already activated the account.'
            );
        }

        $pendingAssignment=DB::table('official_terms')
            ->where('user_id',$secretary->user_id)
            ->where('role','sk_secretary')
            ->where('status','pending')
            ->exists();

        if(!$pendingAssignment){
            return back()->with(
                'warning',
                'This Secretary does not have a pending account setup or reappointment.'
            );
        }

        $token=Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email'=>$secretary->email],
            [
                'token'=>Hash::make($token),
                'created_at'=>now(),
            ]
        );

        if(!$this->sendSetupEmail($secretary,$token)){
            return back()->with(
                'warning',
                'The new setup link could not be sent. Please try again.'
            );
        }

        return back()->with(
            'success',
            'A new password setup link was sent to '.$secretary->email.'.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SECRETARY ACTIVATE / DEACTIVATE
    |--------------------------------------------------------------------------
    */
    public function toggleSecretaryStatus(int $userId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $secretary=$this->secretaryForChairman($userId);

        if((int)$secretary->is_verified === 0){
            return back()->with(
                'warning',
                'This secretary has not completed account setup yet. Resend the setup link instead.'
            );
        }

        if($secretary->status === 'inactive'){
            $anotherSecretary=User::query()
                ->where('barangay_id',auth()->user()->barangay_id)
                ->where('role','sk_secretary')
                ->where('user_id','!=',$secretary->user_id)
                ->whereNull('archived_at')
                ->where(function($query){
                    $query->where('status','active')
                        ->orWhere('is_verified',0);
                })
                ->exists();

            if($anotherSecretary){
                return back()->with(
                    'warning',
                    'Another active or pending SK Secretary already exists for this barangay.'
                );
            }
        }

        $secretary->status=$secretary->status === 'active' ? 'inactive' : 'active';
        $secretary->save();

        return back()->with('success','SK Secretary status updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE NEW PENDING / CANCEL SECRETARY REAPPOINTMENT
    |--------------------------------------------------------------------------
    */
    public function destroySecretary(int $userId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $secretary=$this->secretaryForChairman($userId);
        $currentTerm=$this->currentAdministrationTerm();

        if(!$currentTerm){
            return back()->with('warning','There is no active administration term.');
        }

        if((int)$secretary->is_verified === 1){
            return back()->with('warning','Activated Secretary accounts cannot be permanently deleted.');
        }

        $pendingTerm=DB::table('official_terms')
            ->where('user_id',$userId)
            ->where('term_id',$currentTerm->term_id)
            ->where('barangay_id',auth()->user()->barangay_id)
            ->where('role','sk_secretary')
            ->where('status','pending')
            ->first();

        if(!$pendingTerm){
            return back()->with('warning','No pending Secretary appointment was found.');
        }

        $hasCompletedHistory=DB::table('official_terms')
            ->where('user_id',$userId)
            ->where('role','sk_secretary')
            ->where('status','completed')
            ->exists();

        if($hasCompletedHistory){
            DB::transaction(function() use($secretary,$pendingTerm){
                DB::table('password_reset_tokens')
                    ->where('email',$secretary->email)
                    ->delete();

                DB::table('official_terms')
                    ->where('official_term_id',$pendingTerm->official_term_id)
                    ->delete();

                $secretary->update([
                    'is_verified'=>1,
                    'status'=>'inactive',
                    'archived_at'=>now(),
                ]);
            });

            return redirect()
                ->route('sk_chairman.leadership')
                ->with('success','Secretary reappointment cancelled. The former official and historical records were preserved.');
        }

        DB::transaction(function() use($secretary,$pendingTerm,$userId){
            DB::table('password_reset_tokens')
                ->where('email',$secretary->email)
                ->delete();

            DB::table('official_terms')
                ->where('official_term_id',$pendingTerm->official_term_id)
                ->delete();

            $secretary->delete();
        });

        return redirect()
            ->route('sk_chairman.leadership')
            ->with('success','Pending SK Secretary account deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | REAPPOINT FORMER SECRETARY
    |--------------------------------------------------------------------------
    */
    public function reappointSecretary(int $officialTermId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $chairman=auth()->user();
        $currentTerm=$this->currentAdministrationTerm();

        if(!$currentTerm){
            return back()->with('warning','There is no active administration term.');
        }

        $chairmanAssignment=DB::table('official_terms')
            ->where('user_id',$chairman->user_id)
            ->where('term_id',$currentTerm->term_id)
            ->where('role','sk_chairman')
            ->where('status','current')
            ->exists();

        if(!$chairmanAssignment){
            return back()->with('warning','Your Chairman account is not connected to the current administration term.');
        }

        $previousTerm=DB::table('official_terms')
            ->where('official_term_id',$officialTermId)
            ->where('barangay_id',$chairman->barangay_id)
            ->where('role','sk_secretary')
            ->where('status','completed')
            ->where('term_id','!=',$currentTerm->term_id)
            ->first();

        if(!$previousTerm){
            return back()->with('warning','The selected former Secretary record was not found.');
        }

        $secretary=User::where('user_id',$previousTerm->user_id)->first();

        if(!$secretary){
            return back()->with('warning','The former Secretary account no longer exists.');
        }

        $currentSecretary=DB::table('official_terms')
            ->where('term_id',$currentTerm->term_id)
            ->where('barangay_id',$chairman->barangay_id)
            ->where('role','sk_secretary')
            ->whereIn('status',['pending','current'])
            ->exists();

        if($currentSecretary){
            return back()->with('warning','Your barangay already has a current or pending SK Secretary.');
        }

        $sameTermRecord=DB::table('official_terms')
            ->where('user_id',$secretary->user_id)
            ->where('term_id',$currentTerm->term_id)
            ->where('role','sk_secretary')
            ->exists();

        if($sameTermRecord){
            return back()->with('warning','This Secretary already has a record in the current administration.');
        }

        $anotherCurrentAssignment=DB::table('official_terms')
            ->where('user_id',$secretary->user_id)
            ->where('term_id',$currentTerm->term_id)
            ->whereIn('status',['pending','current'])
            ->exists();

        if($anotherCurrentAssignment){
            return back()->with('warning','This person already has another active assignment in the current administration.');
        }

        $token=Str::random(64);

        DB::transaction(function() use($secretary,$chairman,$currentTerm,$token){
            DB::table('password_reset_tokens')
                ->where('email',$secretary->email)
                ->delete();

            $secretary->update([
                'role'=>'sk_secretary',
                'barangay_id'=>$chairman->barangay_id,
                'password'=>Hash::make(Str::random(64)),
                'is_verified'=>0,
                'status'=>'inactive',
                'archived_at'=>null,
            ]);

            DB::table('official_terms')->insert([
                'user_id'=>$secretary->user_id,
                'term_id'=>$currentTerm->term_id,
                'barangay_id'=>$chairman->barangay_id,
                'role'=>'sk_secretary',
                'status'=>'pending',
                'started_at'=>null,
                'completed_at'=>null,
            ]);

            DB::table('password_reset_tokens')->insert([
                'email'=>$secretary->email,
                'token'=>Hash::make($token),
                'created_at'=>now(),
            ]);
        });

        if(!$this->sendSetupEmail($secretary,$token)){
            return redirect()
                ->route('sk_chairman.leadership')
                ->with(
                    'warning',
                    trim($secretary->first_name.' '.$secretary->last_name).
                    ' was selected for reappointment, but the password setup email could not be sent. Use Resend Setup Link from Leadership.'
                );
        }

        return redirect()
            ->route('sk_chairman.leadership')
            ->with(
                'success',
                trim($secretary->first_name.' '.$secretary->last_name).
                ' was selected for reappointment as SK Secretary for the '.
                $currentTerm->start_year.' - '.$currentTerm->end_year.
                ' administration. A new password setup link was sent to '.$secretary->email.'.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | REAPPOINT FORMER TREASURER / COUNCILOR
    |--------------------------------------------------------------------------
    */
    public function reappointCouncilMember(int $councilId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $currentTerm=$this->currentAdministrationTerm();

        if(!$currentTerm){
            return back()->with('warning','There is no active administration term.');
        }

        $former=DB::table('sk_council')
            ->where('council_id',$councilId)
            ->where('barangay_id',auth()->user()->barangay_id)
            ->where('status','completed')
            ->where('term_id','!=',$currentTerm->term_id)
            ->first();

        if(!$former){
            return back()->with('warning','The selected former council member record was not found.');
        }

        $position=strtolower(trim($former->position ?? ''));
        $isTreasurer=$position === 'sk treasurer';
        $isCouncilor=str_contains($position,'councilor') || str_contains($position,'kagawad');

        if(!$isTreasurer && !$isCouncilor){
            return back()->with('warning','Only former SK Treasurers and Councilors can be reappointed here.');
        }

        if($isTreasurer){
            $occupied=DB::table('sk_council')
                ->where('barangay_id',auth()->user()->barangay_id)
                ->where('term_id',$currentTerm->term_id)
                ->where('status','current')
                ->whereRaw('LOWER(position)=?',['sk treasurer'])
                ->exists();

            if($occupied){
                return back()->with('warning','Your barangay already has an SK Treasurer for the current administration.');
            }
        }else{
            $normalizedName=strtolower(trim(preg_replace('/\s+/',' ',$former->name)));

            $alreadyCurrent=DB::table('sk_council')
                ->where('barangay_id',auth()->user()->barangay_id)
                ->where('term_id',$currentTerm->term_id)
                ->where('status','current')
                ->where(function($query){
                    $query->whereRaw('LOWER(position) LIKE ?',['%councilor%'])
                        ->orWhereRaw('LOWER(position) LIKE ?',['%kagawad%']);
                })
                ->whereRaw('LOWER(TRIM(name))=?',[$normalizedName])
                ->exists();

            if($alreadyCurrent){
                return back()->with('warning',$former->name.' is already listed as an SK Councilor for the current administration.');
            }
        }

        DB::table('sk_council')->insert([
            'barangay_id'=>auth()->user()->barangay_id,
            'term_id'=>$currentTerm->term_id,
            'name'=>$former->name,
            'position'=>$isTreasurer ? 'SK Treasurer' : 'SK Councilor',
            'email'=>$former->email,
            'phone'=>$former->phone,
            'term'=>$currentTerm->start_year.'-'.$currentTerm->end_year,
            'status'=>'current',
            'profile_img'=>$former->profile_img ?: 'default.png',
            'created_at'=>now(),
            'completed_at'=>null,
        ]);

        $role=$isTreasurer ? 'SK Treasurer' : 'SK Councilor';

        return redirect()
            ->route('sk_chairman.leadership')
            ->with('success',$former->name.' was reappointed as '.$role.' for the '.$currentTerm->start_year.' - '.$currentTerm->end_year.' administration.');
    }

    /*
    |--------------------------------------------------------------------------
    | END TREASURER / COUNCILOR SERVICE
    |--------------------------------------------------------------------------
    */
    public function destroy(int $councilId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $currentTerm=$this->currentAdministrationTerm();

        if(!$currentTerm){
            return back()->with('warning','There is no active administration term.');
        }

        $member=DB::table('sk_council')
            ->where('council_id',$councilId)
            ->where('barangay_id',auth()->user()->barangay_id)
            ->where('term_id',$currentTerm->term_id)
            ->where('status','current')
            ->first();

        if(!$member){
            return back()->with('warning','Only current administration council members can have their service ended.');
        }

        $position=strtolower(trim($member->position ?? ''));

        $allowed=
            $position === 'sk treasurer' ||
            str_contains($position,'councilor') ||
            str_contains($position,'kagawad');

        if(!$allowed){
            return back()->with('warning','This council member cannot be ended using this action.');
        }

        DB::table('sk_council')
            ->where('council_id',$councilId)
            ->where('barangay_id',auth()->user()->barangay_id)
            ->where('term_id',$currentTerm->term_id)
            ->where('status','current')
            ->update([
                'status'=>'completed',
                'completed_at'=>now(),
            ]);

        $role=$position === 'sk treasurer'
            ? 'SK Treasurer'
            : 'SK Councilor';

        return redirect()
            ->route('sk_chairman.leadership')
            ->with(
                'success',
                $member->name."'s service as ".$role.' has been ended successfully. The historical record was preserved.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */
    protected function formerLeadershipMembers(int $barangayId,$currentAdministration): array
    {
        if(!$currentAdministration){
            return [
                'officials'=>collect(),
                'terms'=>collect(),
            ];
        }

        $grouped=[];

        $secretaryRows=DB::table('official_terms as ot')
            ->join('users as u','ot.user_id','=','u.user_id')
            ->join('administration_terms as t','ot.term_id','=','t.term_id')
            ->where('ot.barangay_id',$barangayId)
            ->where('ot.role','sk_secretary')
            ->where('ot.status','completed')
            ->where('ot.term_id','!=',$currentAdministration->term_id)
            ->where('u.is_verified',1)
            ->whereNotExists(function($query) use($currentAdministration){
                $query->select(DB::raw(1))
                    ->from('official_terms as current_ot')
                    ->whereColumn('current_ot.user_id','ot.user_id')
                    ->where('current_ot.term_id',$currentAdministration->term_id);
            })
            ->select(
                'ot.official_term_id',
                'ot.user_id',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.phone_number as phone',
                't.start_year',
                't.end_year',
                'ot.completed_at'
            )
            ->orderByDesc('t.start_year')
            ->orderByDesc('ot.completed_at')
            ->get();

        foreach($secretaryRows as $row){
            $key='secretary|'.$row->user_id;
            $term=$row->start_year.'-'.$row->end_year;

            if(!isset($grouped[$key])){
                $grouped[$key]=[
                    'type'=>'secretary',
                    'position'=>'SK Secretary',
                    'action_id'=>$row->official_term_id,
                    'name'=>trim(($row->first_name ?? '').' '.($row->last_name ?? '')),
                    'email'=>$row->email,
                    'phone'=>$row->phone,
                    'terms'=>[],
                    'latest_start_year'=>(int)$row->start_year,
                ];
            }

            $grouped[$key]['terms'][$term]=(int)$row->start_year;
        }

        $currentCouncilorNames=DB::table('sk_council')
            ->where('barangay_id',$barangayId)
            ->where('term_id',$currentAdministration->term_id)
            ->where('status','current')
            ->where(function($query){
                $query->whereRaw('LOWER(position) LIKE ?',['%councilor%'])
                    ->orWhereRaw('LOWER(position) LIKE ?',['%kagawad%']);
            })
            ->pluck('name')
            ->map(fn($name)=>strtolower(trim(preg_replace('/\s+/',' ',$name))))
            ->all();

        $councilRows=DB::table('sk_council as sc')
            ->join('administration_terms as t','sc.term_id','=','t.term_id')
            ->where('sc.barangay_id',$barangayId)
            ->where('sc.status','completed')
            ->where('sc.term_id','!=',$currentAdministration->term_id)
            ->where(function($query){
                $query->whereRaw('LOWER(sc.position)=?',['sk treasurer'])
                    ->orWhereRaw('LOWER(sc.position) LIKE ?',['%councilor%'])
                    ->orWhereRaw('LOWER(sc.position) LIKE ?',['%kagawad%']);
            })
            ->select(
                'sc.council_id',
                'sc.name',
                'sc.position',
                'sc.email',
                'sc.phone',
                't.start_year',
                't.end_year',
                'sc.completed_at'
            )
            ->orderByDesc('t.start_year')
            ->orderByDesc('sc.completed_at')
            ->get();

        foreach($councilRows as $row){
            $position=strtolower(trim($row->position ?? ''));
            $type=$position === 'sk treasurer' ? 'treasurer' : 'councilor';
            $normalizedName=strtolower(trim(preg_replace('/\s+/',' ',$row->name ?? '')));

            if($type === 'councilor' && in_array($normalizedName,$currentCouncilorNames,true)){
                continue;
            }

            $email=strtolower(trim($row->email ?? ''));
            $phone=preg_replace('/\D+/','',$row->phone ?? '');
            $key=$type.'|'.$normalizedName.'|'.$email.'|'.$phone;
            $term=$row->start_year.'-'.$row->end_year;

            if(!isset($grouped[$key])){
                $grouped[$key]=[
                    'type'=>$type,
                    'position'=>$type === 'treasurer' ? 'SK Treasurer' : 'SK Councilor',
                    'action_id'=>$row->council_id,
                    'name'=>$row->name,
                    'email'=>$row->email,
                    'phone'=>$row->phone,
                    'terms'=>[],
                    'latest_start_year'=>(int)$row->start_year,
                ];
            }

            $grouped[$key]['terms'][$term]=(int)$row->start_year;
        }

        $officials=collect(array_values($grouped))
            ->map(function($official){
                arsort($official['terms']);
                $official['terms']=array_keys($official['terms']);
                return $official;
            })
            ->sortByDesc('latest_start_year')
            ->values();

        $terms=$officials
            ->pluck('terms')
            ->flatten()
            ->unique()
            ->sortByDesc(fn($term)=>(int)explode('-',$term)[0])
            ->values();

        return [
            'officials'=>$officials,
            'terms'=>$terms,
        ];
    }

    protected function secretaryForChairman(int $userId): User
    {
        return User::query()
            ->where('user_id',$userId)
            ->where('barangay_id',auth()->user()->barangay_id)
            ->where('role','sk_secretary')
            ->whereNull('archived_at')
            ->firstOrFail();
    }

    protected function sendSetupEmail(User $user,string $token): bool
    {
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

            return true;
        }catch(\Throwable $e){
            \Log::error('Secretary setup email failed for '.$user->email.': '.$e->getMessage());
            return false;
        }
    }

    protected function officialTermLabel(User $user): string
    {
        $term=DB::table('official_terms as ot')
            ->join('administration_terms as t','ot.term_id','=','t.term_id')
            ->where('ot.user_id',$user->user_id)
            ->whereIn('ot.status',['pending','current'])
            ->orderByDesc('ot.official_term_id')
            ->select('t.start_year','t.end_year')
            ->first();

        return $term
            ? $term->start_year.'-'.$term->end_year
            : 'N/A';
    }

    protected function currentAdministrationTerm()
    {
        return DB::table('administration_terms')
            ->where('status','current')
            ->orderByDesc('term_id')
            ->first();
    }

    protected function menuItems(): array
    {
        return [
            ['link'=>route('sk_chairman.home'),'icon'=>'&#127968;','label'=>'Home'],
            ['link'=>route('sk_chairman.reports'),'icon'=>'&#128196;','label'=>'Reports'],
            ['link'=>route('sk_chairman.budget'),'icon'=>'&#128229;','label'=>'Budget'],
            ['link'=>route('sk_chairman.announcements'),'icon'=>'&#128226;','label'=>'Announcements'],
            ['link'=>route('sk_chairman.calendar'),'icon'=>'&#128197;','label'=>'Calendar'],
            ['link'=>route('sk_chairman.chat'),'icon'=>'&#128172;','label'=>'Chat'],
            ['link'=>route('sk_chairman.meetings'),'icon'=>'&#128222;','label'=>'Meetings'],
            ['link'=>route('sk_chairman.rankings'),'icon'=>'&#127942;','label'=>'Rankings'],
            ['link'=>route('sk_chairman.leadership'),'icon'=>'&#128101;','label'=>'Leadership'],
            ['link'=>route('sk_chairman.archive'),'icon'=>'&#128465;','label'=>'Archive'],
        ];
    }
}