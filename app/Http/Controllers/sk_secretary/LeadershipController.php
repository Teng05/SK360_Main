<?php

namespace App\Http\Controllers\sk_secretary;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeadershipController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary',403);

        $user=auth()->user();
        $fullName=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $barangayId=(int)($user->barangay_id ?? 0);
        $barangayName=$user->barangay->barangay_name ?? 'Barangay';
        $currentAdministration=$this->currentAdministrationTerm();

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

        return view('sk_secretary.leadership',[
            'fullName'=>$fullName,
            'userName'=>$fullName,
            'roleLabel'=>'SK Secretary',
            'profileRoute'=>route('sk_secretary.profile'),
            'menuItems'=>$this->menuItems(),
            'currentUrl'=>url()->current(),
            'barangayName'=>$barangayName,
            'currentAdministration'=>$currentAdministration,
            'chairman'=>$leadership['chairman'],
            'secretary'=>$leadership['secretary'],
            'treasurer'=>$leadership['treasurer'],
            'executives'=>$executives,
            'councilors'=>$councilors,
            'councilMembers'=>$councilMembers,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CURRENT BARANGAY LEADERSHIP
    |--------------------------------------------------------------------------
    */
    protected function barangayLeadership(int $barangayId,?int $termId): array
    {
        if($barangayId <= 0 || !$termId){
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