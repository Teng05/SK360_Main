<?php

namespace App\Http\Controllers\public_portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(Request $request): View
    {
        $barangays = DB::table('barangays')->orderBy('barangay_name')->get(['barangay_id', 'barangay_name']);
        $publications = $this->publications()->get()->map(fn ($budget) => $this->snapshot($budget))->filter();
        $availableYears = $publications->pluck('fiscal_year')->unique()->sortDesc()->values();
        $selectedYear = filter_var($request->query('year'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]])
            ?: (int) ($availableYears->first() ?? now()->year);
        $availableYears = $availableYears->push($selectedYear)->unique()->sortDesc()->values();
        $selectedBarangay = (int) $request->query('barangay_id') ?: null;
        if (!$barangays->contains('barangay_id', $selectedBarangay)) $selectedBarangay = null;
        $summaries = $publications->where('fiscal_year', $selectedYear)->keyBy('barangay_id');
        $items = $barangays->when($selectedBarangay, fn ($rows) => $rows->where('barangay_id', $selectedBarangay))
            ->map(fn ($barangay) => (object) [
                'barangay_id' => $barangay->barangay_id, 'barangay_name' => $barangay->barangay_name,
                'fiscal_year' => $selectedYear, 'summary' => $summaries->get($barangay->barangay_id),
            ])->values();
        $published = $items->filter(fn ($item) => $item->summary !== null);
        return view('public_portal.budgets', [
            'barangays' => $barangays, 'availableYears' => $availableYears, 'selectedYear' => $selectedYear,
            'selectedBarangay' => $selectedBarangay, 'budgetItems' => $this->paginateCollection($items, 9, 'budget_page'),
            'publishedCount' => $published->count(), 'totalBudget' => $published->sum(fn ($item) => (float) $item->summary['allotted']),
        ]);
    }

    public function summary(int $budget): \Illuminate\Http\JsonResponse
    {
        $record = $this->publications()->where('a.annual_budget_id', $budget)->first();
        $summary = $record ? $this->snapshot($record) : null;
        abort_unless($summary, 404);
        return response()->json($summary)->header('Cache-Control', 'no-store');
    }

    private function publications(): \Illuminate\Database\Query\Builder
    {
        return DB::table('annual_budgets as a')->join('barangays as b', 'b.barangay_id', '=', 'a.barangay_id')
            ->where('a.status', 'verified')->whereNotNull('a.published_by')->whereNotNull('a.published_at')
            ->select('a.annual_budget_id', 'a.barangay_id', 'a.fiscal_year', 'a.published_at', 'a.published_by', 'b.barangay_name');
    }

    private function snapshot(object $budget): ?array
    {
        $event = DB::table('financial_events')->where('subject_type', 'annual_budget')->where('subject_id', $budget->annual_budget_id)
            ->where('event_type', 'summary_published')->orderByDesc('sequence_no')->first();
        if (!$event || (int) $event->actor_id !== (int) $budget->published_by || $event->created_at > $budget->published_at) return null;
        $data = json_decode($event->after_snapshot, true);
        if (!is_array($data)) return null;
        foreach (['allotted', 'verified_expenses', 'remaining_after_verified'] as $key) {
            if (!isset($data[$key]) || !is_scalar($data[$key]) || !preg_match('/^-?\d{1,13}(?:\.\d{1,2})?$/D', (string) $data[$key])) return null;
        }
        if (!isset($data['as_of']) || !is_string($data['as_of']) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $data['as_of']) || $data['as_of'] > $budget->published_at) return null;
        $cents = fn ($value) => (int) round((float) $value * 100);
        if ($cents($data['allotted']) < 0 || $cents($data['verified_expenses']) < 0
            || $cents($data['allotted']) - $cents($data['verified_expenses']) !== $cents($data['remaining_after_verified'])) return null;
        // Allowlist published aggregates; never return raw events or live expense records.
        return [
            'annual_budget_id' => (int) $budget->annual_budget_id, 'barangay_id' => (int) $budget->barangay_id,
            'barangay_name' => $budget->barangay_name, 'fiscal_year' => (int) $budget->fiscal_year,
            'published_at' => $budget->published_at, 'allotted' => $data['allotted'],
            'verified_expenses' => $data['verified_expenses'], 'remaining_after_verified' => $data['remaining_after_verified'],
            'itemized_available' => false,
        ];
    }

    protected function paginateCollection(
        Collection $items,
        int $perPage = 9,
        string $pageName = 'page'
    ): LengthAwarePaginator {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
                'query' => request()->query(),
            ]
        );
    }

}
