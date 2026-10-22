<?php
namespace App\Http\Controllers;

use App\Services\FinancialArchiveService;
use Illuminate\Http\Request;

class FinancialArchiveController extends Controller
{
    public function show(Request $request, int $budget, FinancialArchiveService $archive)
    {
        abort_unless(($request->routeIs('sk_pres.*') && $request->user()->role === 'sk_president')
            || ($request->routeIs('sk_chairman.*') && $request->user()->role === 'sk_chairman'), 403);
        $data = $archive->detail($request->user(), $budget);
        $prefix = $request->user()->role === 'sk_president' ? 'sk_pres' : 'sk_chairman';
        $links = $prefix === 'sk_pres'
            ? ['home'=>'Home','dashboard'=>'Dashboard','finances'=>'Budget & Finance','consolidation'=>'Consolidation','module'=>'Module Management','announcements'=>'Announcements','calendar'=>'Calendar','chat'=>'Chat','meetings'=>'Meetings','rankings'=>'Rankings','leadership'=>'Leadership','archive'=>'Archive','user-management'=>'User Management']
            : ['home'=>'Home','reports'=>'Reports','finances'=>'Budget','announcements'=>'Announcements','calendar'=>'Calendar','chat'=>'Chat','meetings'=>'Meetings','rankings'=>'Rankings','leadership'=>'Leadership','archive'=>'Archive'];
        $menuItems = collect($links)->map(fn($label,$key) => ['label'=>$label,'link'=>route($prefix.'.'.$key)])->values()->all();
        return view('shared.financial-archive-detail', $data + ['prefix'=>$prefix,'menuItems'=>$menuItems,'currentUrl'=>route($prefix.'.archive')]);
    }

    public function proof(Request $request, int $attachment, FinancialArchiveService $archive)
    {
        $response = $archive->proof($request->user(), $attachment);
        if ($request->routeIs('archive.financial-proof.download')) {
            $response->headers->set('Content-Disposition', preg_replace('/^inline/i', 'attachment', $response->headers->get('Content-Disposition')));
        }
        return $response;
    }
}
