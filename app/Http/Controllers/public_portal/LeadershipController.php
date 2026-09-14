<?php

namespace App\Http\Controllers\public_portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeadershipController extends Controller
{
    public function index(Request $request): View
    {
        $currentTerm=DB::table('administration_terms')
            ->where('status','current')
            ->orderByDesc('term_id')
            ->first();

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

        if($selectedBarangayId>0){
            $selectedBarangay=$barangays->firstWhere(
                'barangay_id',
                $selectedBarangayId
            );

            if(!$selectedBarangay){
                $selectedBarangayId=0;
            }
        }

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
                ->reject(function($member){
                    return str_contains(
                        strtolower((string)$member->position),
                        'treasurer'
                    );
                })
                ->values();
        }

        return view('public_portal.leadership',[
            'currentTerm'=>$currentTerm,
            'barangays'=>$barangays,
            'selectedBarangayId'=>$selectedBarangayId,
            'selectedBarangay'=>$selectedBarangay,
            'chairman'=>$chairman,
            'secretary'=>$secretary,
            'treasurer'=>$treasurer,
            'councilors'=>$councilors,
        ]);
    }
}