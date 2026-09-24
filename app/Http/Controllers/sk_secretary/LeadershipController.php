<?php

namespace App\Http\Controllers\sk_secretary;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeadershipController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary',403);

        $user=auth()->user();
        $fullName=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $barangayId=(int)($user->barangay_id ?? 0);
        $barangayName=$user->barangay->barangay_name ?? 'Barangay';
        $currentAdministration=$this->currentAdministrationTerm();

        $activeTab=in_array(
            (string)$request->query('tab','current'),
            ['current','history'],
            true
        )
            ? (string)$request->query('tab','current')
            : 'current';

        /*
        |--------------------------------------------------------------------------
        | CURRENT LEADERSHIP
        |--------------------------------------------------------------------------
        */
        $leadership=$this->barangayLeadership(
            $barangayId,
            $currentAdministration?->term_id
        );

        $executives=collect([
            $leadership['chairman'],
            $leadership['secretary'],
            $leadership['treasurer'],
        ])->filter()->values();

        $councilors=$leadership['councilors'];

        $councilMembers=$executives
            ->concat($councilors)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | LEADERSHIP HISTORY
        |--------------------------------------------------------------------------
        */
        $historyTerms=$this->historyTermsForBarangay($barangayId);

        $requestedHistoryTermId=(int)$request->query(
            'history_term',
            0
        );

        $selectedHistoryTermId=0;

        if(
            $requestedHistoryTermId>0 &&
            $historyTerms->contains(
                fn($term)=>(int)$term->term_id === $requestedHistoryTermId
            )
        ){
            $selectedHistoryTermId=$requestedHistoryTermId;
        }elseif($historyTerms->isNotEmpty()){
            $selectedHistoryTermId=(int)$historyTerms->first()->term_id;
        }

        $history=$this->leadershipHistoryForTerm(
            $barangayId,
            $selectedHistoryTermId
        );

        return view('sk_secretary.leadership',[
            'fullName'=>$fullName,
            'userName'=>$fullName,
            'roleLabel'=>'SK Secretary',
            'profileRoute'=>route('sk_secretary.profile'),
            'menuItems'=>$this->menuItems(),
            'currentUrl'=>url()->current(),
            'barangayName'=>$barangayName,
            'activeTab'=>$activeTab,

            'currentAdministration'=>$currentAdministration,
            'chairman'=>$leadership['chairman'],
            'secretary'=>$leadership['secretary'],
            'treasurer'=>$leadership['treasurer'],
            'executives'=>$executives,
            'councilors'=>$councilors,
            'councilMembers'=>$councilMembers,

            'historyTerms'=>$historyTerms,
            'selectedHistoryTermId'=>$selectedHistoryTermId,
            'historyAdministration'=>$history['administration'],
            'historyExecutives'=>$history['executives'],
            'historyCouncilors'=>$history['councilors'],
            'historyMembers'=>$history['members'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CURRENT BARANGAY LEADERSHIP
    |--------------------------------------------------------------------------
    */
    protected function barangayLeadership(int $barangayId,?int $termId): array
    {
        if($barangayId<=0 || !$termId){
            return [
                'chairman'=>null,
                'secretary'=>null,
                'treasurer'=>null,
                'councilors'=>collect(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | CHAIRMAN / SECRETARY
        |--------------------------------------------------------------------------
        */
        $officials=DB::table('official_terms as ot')
            ->join('users as u','ot.user_id','=','u.user_id')
            ->where('ot.term_id',$termId)
            ->where('ot.barangay_id',$barangayId)
            ->whereIn('ot.role',['sk_chairman','sk_secretary'])
            ->whereIn('ot.status',['pending','current'])
            ->whereNull('u.archived_at')
            ->select(
                'ot.official_term_id',
                'ot.role',
                'ot.status as assignment_status',
                'ot.started_at',
                'u.user_id',
                'u.first_name',
                'u.last_name',
                'u.profile_pic',
                'u.email',
                'u.phone_number',
                'u.status as account_status',
                'u.is_verified'
            )
            ->orderByRaw("CASE WHEN ot.status='current' THEN 0 ELSE 1 END")
            ->orderByDesc('ot.official_term_id')
            ->get();

        $chairmanRow=$officials->firstWhere('role','sk_chairman');
        $secretaryRow=$officials->firstWhere('role','sk_secretary');

        /*
        |--------------------------------------------------------------------------
        | TREASURER / COUNCILORS
        |--------------------------------------------------------------------------
        */
        $council=DB::table('sk_council')
            ->where('term_id',$termId)
            ->where('barangay_id',$barangayId)
            ->where('status','current')
            ->select(
                'council_id',
                'name',
                'position',
                'email',
                'phone',
                'created_at'
            )
            ->orderBy('name')
            ->get();

        $treasurerRow=$council->first(function($row){
            return str_contains(
                strtolower(trim((string)$row->position)),
                'treasurer'
            );
        });

        $councilors=$council
            ->filter(function($row){
                $position=strtolower(trim((string)$row->position));

                return str_contains($position,'councilor')
                    || str_contains($position,'kagawad');
            })
            ->map(fn($row)=>[
                'id'=>$row->council_id,
                'name'=>$row->name,
                'position'=>'SK Councilor',
                'email'=>$row->email,
                'phone'=>$row->phone,
                'assignment_status'=>'current',
                'account_status'=>null,
                'is_verified'=>1,
                'started_at'=>$row->created_at,
            ])
            ->values();

        return [
            'chairman'=>$chairmanRow
                ? $this->mapOfficial($chairmanRow,'SK Chairman')
                : null,

            'secretary'=>$secretaryRow
                ? $this->mapOfficial($secretaryRow,'SK Secretary')
                : null,

            'treasurer'=>$treasurerRow
                ? [
                    'id'=>$treasurerRow->council_id,
                    'name'=>$treasurerRow->name,
                    'position'=>'SK Treasurer',
                    'email'=>$treasurerRow->email,
                    'phone'=>$treasurerRow->phone,
                    'assignment_status'=>'current',
                    'account_status'=>null,
                    'is_verified'=>1,
                    'started_at'=>$treasurerRow->created_at,
                ]
                : null,

            'councilors'=>$councilors,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETED ADMINISTRATIONS FOR THIS BARANGAY
    |--------------------------------------------------------------------------
    */
    protected function historyTermsForBarangay(int $barangayId): Collection
    {
        if($barangayId<=0){
            return collect();
        }

        /*
        | Get historical term IDs from Chairman / Secretary records.
        */
        $officialTermIds=DB::table('official_terms')
            ->where('barangay_id',$barangayId)
            ->whereIn('role',[
                'sk_chairman',
                'sk_secretary',
            ])
            ->where('status','completed')
            ->pluck('term_id');

        /*
        | Get historical term IDs from Treasurer / Councilor records.
        */
        $councilTermIds=DB::table('sk_council')
            ->where('barangay_id',$barangayId)
            ->where('status','completed')
            ->pluck('term_id');

        /*
        | Combine all historical term IDs.
        */
        $termIds=$officialTermIds
            ->merge($councilTermIds)
            ->filter()
            ->map(fn($termId)=>(int)$termId)
            ->unique()
            ->values();

        if($termIds->isEmpty()){
            return collect();
        }

        /*
        | Only show administrations that are actually completed.
        */
        return DB::table('administration_terms')
            ->whereIn('term_id',$termIds)
            ->where('status','completed')
            ->select(
                'term_id',
                'start_year',
                'end_year',
                'status',
                'completed_at'
            )
            ->orderByDesc('start_year')
            ->orderByDesc('term_id')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | LEADERSHIP HISTORY FOR SELECTED TERM
    |--------------------------------------------------------------------------
    */
    protected function leadershipHistoryForTerm(
        int $barangayId,
        int $termId
    ): array {
        if($barangayId<=0 || $termId<=0){
            return [
                'administration'=>null,
                'executives'=>collect(),
                'councilors'=>collect(),
                'members'=>collect(),
            ];
        }

        $administration=DB::table('administration_terms')
            ->where('term_id',$termId)
            ->where('status','completed')
            ->first();

        if(!$administration){
            return [
                'administration'=>null,
                'executives'=>collect(),
                'councilors'=>collect(),
                'members'=>collect(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | HISTORICAL CHAIRMAN / SECRETARY
        |--------------------------------------------------------------------------
        */
        $officials=DB::table('official_terms as ot')
            ->join('users as u','ot.user_id','=','u.user_id')
            ->where('ot.term_id',$termId)
            ->where('ot.barangay_id',$barangayId)
            ->whereIn('ot.role',[
                'sk_chairman',
                'sk_secretary',
            ])
            ->where('ot.status','completed')
            ->select(
                'ot.official_term_id',
                'ot.role',
                'ot.status',
                'ot.started_at',
                'ot.completed_at',
                'u.user_id',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.phone_number'
            )
            ->orderByRaw("
                CASE
                    WHEN ot.role='sk_chairman' THEN 1
                    WHEN ot.role='sk_secretary' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('ot.started_at')
            ->orderBy('ot.official_term_id')
            ->get()
            ->map(function($row) use($administration){

                $position=$row->role==='sk_chairman'
                    ? 'SK Chairman'
                    : 'SK Secretary';

                return [
                    'id'=>$row->official_term_id,
                    'source'=>'official_term',
                    'name'=>trim(
                        ($row->first_name ?? '').
                        ' '.
                        ($row->last_name ?? '')
                    ),
                    'position'=>$position,
                    'email'=>$row->email,
                    'phone'=>$row->phone_number,
                    'term_id'=>$administration->term_id,
                    'term'=>$administration->start_year.
                        ' - '.
                        $administration->end_year,
                    'started_at'=>$row->started_at,
                    'completed_at'=>$row->completed_at,
                    'status'=>$row->status,
                    'sort_order'=>$row->role==='sk_chairman'
                        ? 1
                        : 2,
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | HISTORICAL TREASURER / COUNCILORS
        |--------------------------------------------------------------------------
        */
        $council=DB::table('sk_council')
            ->where('term_id',$termId)
            ->where('barangay_id',$barangayId)
            ->where('status','completed')
            ->select(
                'council_id',
                'name',
                'position',
                'email',
                'phone',
                'created_at',
                'completed_at',
                'status'
            )
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(function($row) use($administration){

                $normalizedPosition=strtolower(
                    trim((string)$row->position)
                );

                if(str_contains($normalizedPosition,'treasurer')){
                    $position='SK Treasurer';
                    $sortOrder=3;
                }elseif(
                    str_contains($normalizedPosition,'councilor') ||
                    str_contains($normalizedPosition,'kagawad')
                ){
                    $position='SK Councilor';
                    $sortOrder=4;
                }else{
                    return null;
                }

                return [
                    'id'=>$row->council_id,
                    'source'=>'sk_council',
                    'name'=>$row->name,
                    'position'=>$position,
                    'email'=>$row->email,
                    'phone'=>$row->phone,
                    'term_id'=>$administration->term_id,
                    'term'=>$administration->start_year.
                        ' - '.
                        $administration->end_year,
                    'started_at'=>$row->created_at,
                    'completed_at'=>$row->completed_at,
                    'status'=>$row->status,
                    'sort_order'=>$sortOrder,
                ];
            })
            ->filter()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | COMBINE HISTORY
        |--------------------------------------------------------------------------
        */
        $members=$officials
            ->concat($council)
            ->sortBy(function($member){
                return sprintf(
                    '%02d-%s',
                    $member['sort_order'] ?? 99,
                    strtolower($member['name'] ?? '')
                );
            })
            ->values();

        $executives=$members
            ->filter(function($member){
                return in_array(
                    $member['position'],
                    [
                        'SK Chairman',
                        'SK Secretary',
                        'SK Treasurer',
                    ],
                    true
                );
            })
            ->values();

        $councilors=$members
            ->where('position','SK Councilor')
            ->values();

        return [
            'administration'=>$administration,
            'executives'=>$executives,
            'councilors'=>$councilors,
            'members'=>$members,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | MAP USER OFFICIAL
    |--------------------------------------------------------------------------
    */
    protected function mapOfficial(object $row,string $position): array
    {
        return [
            'id'=>$row->user_id,
            'name'=>trim(
                ($row->first_name ?? '').
                ' '.
                ($row->last_name ?? '')
            ),
            'position'=>$position,
            'email'=>$row->email,
            'phone'=>$row->phone_number,
            'assignment_status'=>$row->assignment_status,
            'account_status'=>$row->account_status,
            'is_verified'=>(int)$row->is_verified,
            'profile_pic_url'=>$row->profile_pic
                ? asset(str_starts_with($row->profile_pic, 'uploads/') ? $row->profile_pic : 'uploads/profile_pics/'.$row->profile_pic)
                : null,
            'started_at'=>$row->started_at,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CURRENT ADMINISTRATION
    |--------------------------------------------------------------------------
    */
    protected function currentAdministrationTerm()
    {
        return DB::table('administration_terms')
            ->where('status','current')
            ->orderByDesc('term_id')
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | MENU ITEMS
    |--------------------------------------------------------------------------
    */
    protected function menuItems(): array
    {
        return [
            ['link'=>route('sk_secretary.home'),'icon'=>'🏠','label'=>'Home'],
            ['link'=>route('sk_secretary.reports'),'icon'=>'📊','label'=>'Reports'],
            ['link'=>route('sk_secretary.budget'),'icon'=>'💰','label'=>'Budget'],
            ['link'=>route('sk_secretary.announcements'),'icon'=>'📢','label'=>'Announcements'],
            ['link'=>route('sk_secretary.calendar'),'icon'=>'📅','label'=>'Calendar'],
            ['link'=>route('sk_secretary.chat'),'icon'=>'💬','label'=>'Chat'],
            ['link'=>route('sk_secretary.meetings'),'icon'=>'📞','label'=>'Meetings'],
            ['link'=>route('sk_secretary.rankings'),'icon'=>'🏆','label'=>'Rankings'],
            ['link'=>route('sk_secretary.leadership'),'icon'=>'👥','label'=>'Leadership'],
        ];
    }
}
