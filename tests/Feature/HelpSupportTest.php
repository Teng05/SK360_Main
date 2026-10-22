<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\HelpSupport;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class HelpSupportTest extends TestCase
{
    public function test_role_guides_only_include_available_management_workflows(): void
    {
        $president=HelpSupport::topics('sk_president');
        $chairman=HelpSupport::topics('sk_chairman');
        $secretary=HelpSupport::topics('sk_secretary');
        foreach (['dashboard','finances','expenses','publication','consolidation','module','archive','user-management'] as $key) $this->assertArrayHasKey($key,$president);
        foreach (['finances','expenses','budget','reports','archive'] as $key) $this->assertArrayHasKey($key,$chairman);
        foreach (['budget','reports','finance-access'] as $key) $this->assertArrayHasKey($key,$secretary);
        foreach (['finances','expenses','publication','archive','user-management','module'] as $key) $this->assertArrayNotHasKey($key,$secretary);
        foreach (['publication','user-management','module','consolidation'] as $key) $this->assertArrayNotHasKey($key,$chairman);
        $this->assertSame([],HelpSupport::topics('public'));
    }

    public function test_context_matches_finance_sections_archives_and_existing_submission_urls(): void
    {
        foreach ([
            ['sk_pres.finances','','finances'],
            ['sk_pres.finances','expenses','expenses'],
            ['sk_pres.finances','transparency','publication'],
            ['sk_chairman.finances','submissions','budget'],
            ['sk_chairman.budget','','budget'],
            ['sk_secretary.budget.template.create','','budget'],
            ['sk_pres.archive.finances','','archive'],
            ['sk_chairman.archive.finances','','archive'],
            ['sk_pres.user-management','','user-management'],
            ['sk_pres.module.live','','module'],
            ['sk_secretary.profile','','profile'],
        ] as [$route,$section,$expected]) $this->assertSame($expected,HelpSupport::currentTopic($route,$section));
    }

    public function test_drawer_renders_role_specific_help_and_highlights_the_current_page(): void
    {
        foreach ([['sk_president','sk_pres.finances','transparency','Publishing annual allocations'],['sk_chairman','sk_chairman.finances','expenses','Recording & correcting expenses'],['sk_secretary','sk_secretary.budget','','Budget Submissions & financial documents']] as [$role,$name,$section,$title]) {
            $this->actingAs((new User())->forceFill(['user_id'=>99,'role'=>$role]));
            $request=Request::create('/help-test', 'GET', ['section'=>$section]);
            $route=new Route('GET','help-test',fn()=>null);
            $route->name($name);
            $request->setRouteResolver(fn()=>$route);
            $this->app->instance('request',$request);
            $html=view('partials.app.help')->render();
            $this->assertStringContainsString('<style>', $html);
            $this->assertStringContainsString('Help for this page</p><details', $html);
            $this->assertStringContainsString(e($title),$html);
            $this->assertStringContainsString('tabindex="-1" inert',$html);
            $this->assertStringNotContainsString('@endphp',$html);
            if ($role==='sk_secretary') $this->assertStringNotContainsString('Resend Setup Links appears',$html);
        }
    }
}
