<?php

namespace Tests\Feature;

use App\Http\Controllers\sk_pres\AnnouncementController;
use App\Http\Controllers\sk_pres\ConsolidationController;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PresidentModuleActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        $user=new User();
        $user->forceFill(['user_id'=>1,'role'=>'sk_president']);
        $this->actingAs($user);
        Schema::create('administration_terms',function(Blueprint $table){
            $table->integer('term_id');
            $table->string('status');
        });
        DB::table('administration_terms')->insert(['term_id'=>2,'status'=>'current']);
        Schema::create('announcements',function(Blueprint $table){
            $table->integer('announcement_id');
            $table->integer('term_id');
        });
        foreach(['announcement_feedback','announcement_views','public_wall_post_likes','wall_post_likes'] as $name){
            Schema::create($name,function(Blueprint $table){
                $table->integer('announcement_id');
                $table->integer('feedback_id')->nullable();
            });
            DB::table($name)->insert([
                ['announcement_id'=>10,'feedback_id'=>100],
                ['announcement_id'=>20,'feedback_id'=>200],
            ]);
        }
        Schema::create('feedback_verifications',fn(Blueprint $table)=>$table->integer('feedback_id'));
        DB::table('feedback_verifications')->insert([['feedback_id'=>100],['feedback_id'=>200]]);
        DB::table('announcements')->insert([
            ['announcement_id'=>10,'term_id'=>2],['announcement_id'=>20,'term_id'=>1],
        ]);
    }

    public function test_delete_removes_only_selected_current_term_announcement_and_its_interactions(): void
    {
        $response=(new AnnouncementController())->destroy(10);
        $this->assertSame(route('sk_pres.announcements'),$response->getTargetUrl());
        foreach(['announcements','announcement_feedback','announcement_views','public_wall_post_likes','wall_post_likes'] as $table){
            $this->assertDatabaseMissing($table,['announcement_id'=>10]);
            $this->assertDatabaseHas($table,['announcement_id'=>20]);
        }
        $this->assertDatabaseMissing('feedback_verifications',['feedback_id'=>100]);
        $this->assertDatabaseHas('feedback_verifications',['feedback_id'=>200]);
        $route=app('router')->getRoutes()->getByName('sk_pres.announcements.destroy');
        $this->assertSame(['DELETE'],$route->methods());
    }

    public function test_delete_rejects_archived_term_and_other_roles(): void
    {
        foreach([[20,'sk_president',404],[10,'sk_chairman',403],[10,'sk_secretary',403]] as [$id,$role,$status]){
            auth()->user()->role=$role;
            try{
                (new AnnouncementController())->destroy($id);
                $this->fail('Unauthorized deletion was allowed.');
            }catch(HttpException $exception){
                $this->assertSame($status,$exception->getStatusCode());
            }
            $this->assertDatabaseHas('announcements',['announcement_id'=>$id]);
        }
    }

    public function test_pdf_uses_barangay_year_period_and_current_term_filters(): void
    {
        Schema::create('barangays',function(Blueprint $table){
            $table->integer('barangay_id'); $table->string('barangay_name');
        });
        DB::table('barangays')->insert([
            ['barangay_id'=>1,'barangay_name'=>'Antipolo'],['barangay_id'=>2,'barangay_name'=>'Balintawak'],
        ]);
        foreach(['accomplishment_reports','budget_reports'] as $name){
            Schema::create($name,function(Blueprint $table) use($name){
                $budget=$name==='budget_reports';
                $table->integer('barangay_id'); $table->integer('term_id');
                $table->integer($budget?'fiscal_year':'reporting_year');
                $table->string($budget?'budget_period_type':'report_type');
                $table->integer($budget?'fiscal_month':'reporting_month')->nullable();
                $table->string($budget?'fiscal_quarter':'reporting_quarter')->nullable();
                $table->timestamp('submitted_at')->nullable();
            });
            $budget=$name==='budget_reports';
            foreach([[2,2026,'monthly',3,'Q1'],[2,2026,'monthly',4,'Q2'],[2,2026,'quarterly',null,'Q2'],[2,2026,'annual',null,null],[1,2026,'monthly',3,'Q1'],[2,2025,'monthly',3,'Q1']] as [$term,$year,$period,$month,$quarter]){
                DB::table($name)->insert([
                    'barangay_id'=>1,'term_id'=>$term,($budget?'fiscal_year':'reporting_year')=>$year,
                    ($budget?'budget_period_type':'report_type')=>$period,
                    ($budget?'fiscal_month':'reporting_month')=>$month,
                    ($budget?'fiscal_quarter':'reporting_quarter')=>$quarter,
                    'submitted_at'=>'2026-04-01 12:00:00',
                ]);
            }
        }
        foreach(['monthly','quarterly','annual'] as $period){
            $pdf=\Mockery::mock(\Barryvdh\DomPDF\PDF::class);
            Pdf::shouldReceive('loadView')->once()->with('sk_pres.consolidation-download',\Mockery::on(function($data) use($period){
                $this->assertSame('ANTI',$data['filters']['barangay']);
                $this->assertCount(1,$data['submissions']);
                $row=$data['submissions']->first();
                $this->assertSame('Antipolo',$row['barangay']);
                foreach(['monthly','quarterly','annual'] as $type){
                    $this->assertSame($type===$period?2:0,$row[$type.'_count']);
                }
                $html=view('sk_pres.consolidation-download',$data)->render();
                $this->assertStringContainsString('Barangay filter: ANTI',$html);
                $this->assertStringNotContainsString('Balintawak',$html);
                return true;
            }))->andReturn($pdf);
            $pdf->shouldReceive('setPaper')->once()->with('a4','landscape')->andReturnSelf();
            $pdf->shouldReceive('download')->once()->with("consolidated-reports-2026-$period.pdf")->andReturn(new Response('pdf'));
            (new ConsolidationController())->download(Request::create('/','GET',[
                'year'=>2026,'period'=>$period,'month'=>3,'quarter'=>'Q2','barangay'=>' ANTI ',
            ]));
        }
    }
}
