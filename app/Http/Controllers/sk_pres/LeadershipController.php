<?php

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeadershipController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $user=auth()->user();
        $fullName=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $currentAdministration=$this->currentAdministrationTerm();

        $barangays=Barangay::query()
            ->orderBy('barangay_name')
            ->get(['barangay_id','barangay_name']);

        $selectedBarangayId=(int)request('barangay_id',0);

        if($selectedBarangayId > 0 && !$barangays->contains('barangay_id',$selectedBarangayId)){
            $selectedBarangayId=0;
        }

        $federationPresidents=$this->federationPresidents($currentAdministration?->term_id);

        $leadershipGroups=$this->leadershipGroups(
            $barangays,
            $currentAdministration?->term_id,
            $selectedBarangayId
        );

        $stats=[
            [
                'label'=>'Barangays',
                'value'=>$leadershipGroups->count(),
                'icon'=>'&#128205;',
            ],
            [
                'label'=>'Chairmen',
                'value'=>$leadershipGroups
                    ->filter(fn($group)=>!empty($group['chairman']))
                    ->count(),
                'icon'=>'&#128737;',
            ],
            [
                'label'=>'Secretaries',
                'value'=>$leadershipGroups
                    ->filter(fn($group)=>!empty($group['secretary']))
                    ->count(),
                'icon'=>'&#128196;',
            ],
            [
                'label'=>'Treasurers',
                'value'=>$leadershipGroups
                    ->filter(fn($group)=>!empty($group['treasurer']))
                    ->count(),
                'icon'=>'&#128176;',
            ],
            [
                'label'=>'Councilors',
                'value'=>$leadershipGroups
                    ->sum(fn($group)=>$group['councilors']->count()),
                'icon'=>'&#127775;',
            ],
        ];

        return view('sk_pres.leadership',[
            'fullName'=>$fullName,
            'menuItems'=>$this->menuItems(),
            'currentUrl'=>url()->current(),
            'barangays'=>$barangays,
            'selectedBarangayId'=>$selectedBarangayId,
            'currentAdministration'=>$currentAdministration,
            'federationPresidents'=>$federationPresidents,
            'leadershipGroups'=>$leadershipGroups,
            'stats'=>$stats,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | FEDERATION PRESIDENT
    |--------------------------------------------------------------------------
    */
    protected function federationPresidents(?int $termId): Collection
    {
        if(!$termId){
            return collect();
        }

        return DB::table('official_terms as ot')
            ->join('users as u','ot.user_id','=','u.user_id')
            ->where('ot.term_id',$termId)
            ->where('ot.role','sk_president')
            ->whereIn('ot.status',['pending','current'])
            ->whereNull('u.archived_at')
            ->select(
                'u.user_id',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.phone_number',
                'u.status as account_status',
                'u.is_verified',
                'ot.status as assignment_status',
                'ot.started_at'
            )
            ->orderByRaw("CASE WHEN ot.status='current' THEN 0 ELSE 1 END")
            ->orderByDesc('ot.official_term_id')
            ->get()
            ->map(fn($row)=>[
                'user_id'=>$row->user_id,
                'name'=>trim(($row->first_name ?? '').' '.($row->last_name ?? '')),
                'position'=>'SK Federation President',
                'email'=>$row->email,
                'phone'=>$row->phone_number,
                'assignment_status'=>$row->assignment_status,
                'account_status'=>$row->account_status,
                'is_verified'=>(int)$row->is_verified,
                'started_at'=>$row->started_at,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | BARANGAY LEADERSHIP GROUPS
    |--------------------------------------------------------------------------
    */
    protected function leadershipGroups(
        Collection $barangays,
        ?int $termId,
        int $selectedBarangayId=0
    ): Collection
    {
        $visibleBarangays=$selectedBarangayId > 0
            ? $barangays
                ->where('barangay_id',$selectedBarangayId)
                ->values()
            : $barangays->values();

        if(!$termId){
            return $visibleBarangays->map(fn($barangay)=>[
                'barangay_id'=>(int)$barangay->barangay_id,
                'barangay_name'=>$barangay->barangay_name,
                'chairman'=>null,
                'secretary'=>null,
                'treasurer'=>null,
                'councilors'=>collect(),
                'member_count'=>0,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CHAIRMAN / SECRETARY
        |--------------------------------------------------------------------------
        */
        $officialRows=DB::table('official_terms as ot')
            ->join('users as u','ot.user_id','=','u.user_id')
            ->where('ot.term_id',$termId)
            ->whereIn('ot.role',['sk_chairman','sk_secretary'])
            ->whereIn('ot.status',['pending','current'])
            ->whereNull('u.archived_at')
            ->when(
                $selectedBarangayId > 0,
                fn($query)=>$query->where(
                    'ot.barangay_id',
                    $selectedBarangayId
                )
            )
            ->select(
                'ot.official_term_id',
                'ot.barangay_id',
                'ot.role',
                'ot.status as assignment_status',
                'ot.started_at',
                'u.user_id',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.phone_number',
                'u.status as account_status',
                'u.is_verified'
            )
            ->orderByRaw("CASE WHEN ot.status='current' THEN 0 ELSE 1 END")
            ->orderByDesc('ot.official_term_id')
            ->get()
            ->groupBy('barangay_id');

        /*
        |--------------------------------------------------------------------------
        | TREASURER / COUNCILORS
        |--------------------------------------------------------------------------
        */
        $councilRows=DB::table('sk_council')
            ->where('term_id',$termId)
            ->where('status','current')
            ->when(
                $selectedBarangayId > 0,
                fn($query)=>$query->where(
                    'barangay_id',
                    $selectedBarangayId
                )
            )
            ->select(
                'council_id',
                'barangay_id',
                'name',
                'position',
                'email',
                'phone',
                'created_at'
            )
            ->orderBy('name')
            ->get()
            ->groupBy('barangay_id');

        /*
        |--------------------------------------------------------------------------
        | BUILD DIRECTORY PER BARANGAY
        |--------------------------------------------------------------------------
        */
        return $visibleBarangays
            ->map(function($barangay) use($officialRows,$councilRows){

                $barangayId=(int)$barangay->barangay_id;

                $officials=$officialRows
                    ->get($barangayId,collect());

                $council=$councilRows
                    ->get($barangayId,collect());

                $chairmanRow=$officials
                    ->firstWhere('role','sk_chairman');

                $secretaryRow=$officials
                    ->firstWhere('role','sk_secretary');

                $treasurerRow=$council
                    ->first(function($row){
                        return strtolower(
                            trim((string)$row->position)
                        ) === 'sk treasurer';
                    });

                $councilors=$council
                    ->filter(function($row){

                        $position=strtolower(
                            trim((string)$row->position)
                        );

                        return str_contains(
                            $position,
                            'councilor'
                        ) || str_contains(
                            $position,
                            'kagawad'
                        );
                    })
                    ->map(fn($row)=>[
                        'council_id'=>$row->council_id,
                        'name'=>$row->name,
                        'position'=>'SK Councilor',
                        'email'=>$row->email,
                        'phone'=>$row->phone,
                        'assignment_status'=>'current',
                        'started_at'=>$row->created_at,
                    ])
                    ->values();

                $chairman=$chairmanRow
                    ? $this->mapOfficial(
                        $chairmanRow,
                        'SK Chairman'
                    )
                    : null;

                $secretary=$secretaryRow
                    ? $this->mapOfficial(
                        $secretaryRow,
                        'SK Secretary'
                    )
                    : null;

                $treasurer=$treasurerRow
                    ? [
                        'council_id'=>$treasurerRow->council_id,
                        'name'=>$treasurerRow->name,
                        'position'=>'SK Treasurer',
                        'email'=>$treasurerRow->email,
                        'phone'=>$treasurerRow->phone,
                        'assignment_status'=>'current',
                        'started_at'=>$treasurerRow->created_at,
                    ]
                    : null;

                return [
                    'barangay_id'=>$barangayId,
                    'barangay_name'=>$barangay->barangay_name,
                    'chairman'=>$chairman,
                    'secretary'=>$secretary,
                    'treasurer'=>$treasurer,
                    'councilors'=>$councilors,
                    'member_count'=>
                        ($chairman ? 1 : 0)
                        +($secretary ? 1 : 0)
                        +($treasurer ? 1 : 0)
                        +$councilors->count(),
                ];
            })
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | MAP USER OFFICIAL
    |--------------------------------------------------------------------------
    */
    protected function mapOfficial(
        object $row,
        string $position
    ): array
    {
        return [
            'user_id'=>$row->user_id,
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
            [
                'link'=>route('sk_pres.home'),
                'icon'=>'&#127968;',
                'label'=>'Home'
            ],
            [
                'link'=>route('sk_pres.dashboard'),
                'icon'=>'&#128202;',
                'label'=>'Dashboard'
            ],
            [
                'link'=>route('sk_pres.consolidation'),
                'icon'=>'&#128193;',
                'label'=>'Consolidation'
            ],
            [
                'link'=>route('sk_pres.module'),
                'icon'=>'&#9881;&#65039;',
                'label'=>'Module Management'
            ],
            [
                'link'=>route('sk_pres.announcements'),
                'icon'=>'&#128226;',
                'label'=>'Announcements'
            ],
            [
                'link'=>route('sk_pres.calendar'),
                'icon'=>'&#128197;',
                'label'=>'Calendar'
            ],
            [
                'link'=>route('sk_pres.chat'),
                'icon'=>'&#128172;',
                'label'=>'Chat'
            ],
            [
                'link'=>route('sk_pres.meetings'),
                'icon'=>'&#128222;',
                'label'=>'Meetings'
            ],
            [
                'link'=>route('sk_pres.rankings'),
                'icon'=>'&#127942;',
                'label'=>'Rankings'
            ],
            [
                'link'=>route('sk_pres.leadership'),
                'icon'=>'&#128101;',
                'label'=>'Leadership'
            ],
            [
                'link'=>route('sk_pres.archive'),
                'icon'=>'&#128450;&#65039;',
                'label'=>'Archive'
            ],
            [
                'link'=>route('sk_pres.user-management'),
                'icon'=>'&#128100;',
                'label'=>'User Management'
            ],
        ];
    }
}