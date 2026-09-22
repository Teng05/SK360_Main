<?php

namespace App\Http\Controllers\public_portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeadershipController extends Controller
{
    public function index(Request $request): View
    {
        $currentTerm=$this->currentAdministrationTerm();

        $barangays=DB::table('barangays')
            ->select('barangay_id','barangay_name')
            ->orderBy('barangay_name')
            ->get();

        $selectedBarangayId=(int)$request->query('barangay',0);

        $selectedBarangay=null;
        $chairman=null;
        $secretary=null;
        $treasurer=null;
        $councilors=collect();

        $activeTab=in_array(
            (string)$request->query('tab','current'),
            ['current','history'],
            true
        )
            ? (string)$request->query('tab','current')
            : 'current';

        /*
        |--------------------------------------------------------------------------
        | SELECTED BARANGAY
        |--------------------------------------------------------------------------
        */
        if($selectedBarangayId>0){
            $selectedBarangay=$barangays->firstWhere(
                'barangay_id',
                $selectedBarangayId
            );

            if(!$selectedBarangay){
                $selectedBarangayId=0;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CURRENT LEADERSHIP
        |--------------------------------------------------------------------------
        */
        if(
            $currentTerm &&
            $selectedBarangayId>0 &&
            $selectedBarangay
        ){
            $officials=DB::table('official_terms as ot')
                ->join('users as u','ot.user_id','=','u.user_id')
                ->where('ot.term_id',$currentTerm->term_id)
                ->where('ot.barangay_id',$selectedBarangayId)
                ->where('ot.status','current')
                ->whereIn('ot.role',[
                    'sk_chairman',
                    'sk_secretary',
                ])
                ->select(
                    'u.user_id',
                    'u.first_name',
                    'u.last_name',
                    'u.profile_pic',
                    'ot.role',
                    'ot.started_at'
                )
                ->orderByDesc('ot.official_term_id')
                ->get();

            $chairman=$officials
                ->firstWhere('role','sk_chairman');

            $secretary=$officials
                ->firstWhere('role','sk_secretary');

            $councilMembers=DB::table('sk_council')
                ->where('barangay_id',$selectedBarangayId)
                ->where('term_id',$currentTerm->term_id)
                ->where('status','current')
                ->orderBy('position')
                ->orderBy('name')
                ->get();

            $treasurer=$councilMembers->first(function($member){
                return str_contains(
                    strtolower((string)$member->position),
                    'treasurer'
                );
            });

            $councilors=$councilMembers
                ->filter(function($member){
                    $position=strtolower(
                        trim((string)$member->position)
                    );

                    return str_contains($position,'councilor')
                        || str_contains($position,'kagawad');
                })
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | LEADERSHIP HISTORY
        |--------------------------------------------------------------------------
        */
        $historyTerms=$selectedBarangayId>0
            ? $this->historyTermsForBarangay($selectedBarangayId)
            : collect();

        $requestedHistoryTermId=(int)$request->query(
            'history_term',
            0
        );

        $selectedHistoryTermId=0;

        if(
            $requestedHistoryTermId>0 &&
            $historyTerms->contains(
                fn($term)=>
                    (int)$term->term_id === $requestedHistoryTermId
            )
        ){
            $selectedHistoryTermId=$requestedHistoryTermId;
        }elseif($historyTerms->isNotEmpty()){
            $selectedHistoryTermId=(int)$historyTerms
                ->first()
                ->term_id;
        }

        $history=$this->leadershipHistoryForTerm(
            $selectedBarangayId,
            $selectedHistoryTermId
        );

        return view('public_portal.leadership',[
            'currentTerm'=>$currentTerm,
            'barangays'=>$barangays,
            'selectedBarangayId'=>$selectedBarangayId,
            'selectedBarangay'=>$selectedBarangay,

            'activeTab'=>$activeTab,

            'chairman'=>$chairman,
            'secretary'=>$secretary,
            'treasurer'=>$treasurer,
            'councilors'=>$councilors,

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
    | COMPLETED ADMINISTRATIONS FOR SELECTED BARANGAY
    |--------------------------------------------------------------------------
    */
    protected function historyTermsForBarangay(int $barangayId): Collection
    {
        if($barangayId<=0){
            return collect();
        }

        /*
        | Chairman / Secretary historical terms
        */
        $officialTermIds=DB::table('official_terms')
            ->where('barangay_id',$barangayId)
            ->whereIn('role',[
                'sk_chairman',
                'sk_secretary',
            ])
            ->pluck('term_id');

        /*
        | Treasurer / Councilor historical terms
        */
        $councilTermIds=DB::table('sk_council')
            ->where('barangay_id',$barangayId)
            ->pluck('term_id');

        /*
        | Combine all administration IDs that contain leadership records.
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
        | Only completed administrations belong to Leadership History.
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
    | LEADERSHIP HISTORY FOR SELECTED ADMINISTRATION
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
        |
        | Public-safe fields only.
        |
        */
        $officials=DB::table('official_terms as ot')
            ->join('users as u','ot.user_id','=','u.user_id')
            ->where('ot.term_id',$termId)
            ->where('ot.barangay_id',$barangayId)
            ->whereIn('ot.role',[
                'sk_chairman',
                'sk_secretary',
            ])
            ->whereIn('ot.status',[
                'completed',
                'current',
            ])
            ->select(
                'ot.official_term_id',
                'ot.role',
                'ot.started_at',
                'ot.completed_at',
                'u.first_name',
                'u.last_name',
                'u.profile_pic'
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
                    'profile_pic'=>$row->profile_pic,

                    'term_id'=>$administration->term_id,

                    'term'=>$administration->start_year.
                        ' - '.
                        $administration->end_year,

                    'started_at'=>$row->started_at,
                    'completed_at'=>$row->completed_at,

                    'sort_order'=>$row->role==='sk_chairman'
                        ? 1
                        : 2,
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | HISTORICAL TREASURER / COUNCILORS
        |--------------------------------------------------------------------------
        |
        | Public-safe fields only.
        |
        */
        $council=DB::table('sk_council')
            ->where('term_id',$termId)
            ->where('barangay_id',$barangayId)
            ->whereIn('status',[
                'completed',
                'current',
            ])
            ->select(
                'council_id',
                'name',
                'position',
                'profile_img',
                'created_at',
                'completed_at'
            )
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(function($row) use($administration){

                $normalizedPosition=strtolower(
                    trim((string)$row->position)
                );

                if(str_contains(
                    $normalizedPosition,
                    'treasurer'
                )){
                    $position='SK Treasurer';
                    $sortOrder=3;
                }elseif(
                    str_contains(
                        $normalizedPosition,
                        'councilor'
                    ) ||
                    str_contains(
                        $normalizedPosition,
                        'kagawad'
                    )
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
                    'profile_pic'=>$row->profile_img,

                    'term_id'=>$administration->term_id,

                    'term'=>$administration->start_year.
                        ' - '.
                        $administration->end_year,

                    'started_at'=>$row->created_at,
                    'completed_at'=>$row->completed_at,
                    'sort_order'=>$sortOrder,
                ];
            })
            ->filter()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | COMBINE HISTORICAL LEADERSHIP
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
            ->where(
                'position',
                'SK Councilor'
            )
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
}