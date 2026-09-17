<?php

namespace App\Http\Controllers\public_portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(Request $request): View
    {
        $barangays = DB::table('barangays')
            ->orderBy('barangay_name')
            ->get([
                'barangay_id',
                'barangay_name',
            ]);

        $availableYears = DB::table('budget_reports')
            ->where('budget_category', 'annual_budget')
            ->whereNotNull('fiscal_year')
            ->whereIn('status', [
                'recorded',
                'submitted',
                'archived',
            ])
            ->distinct()
            ->orderByDesc('fiscal_year')
            ->pluck('fiscal_year')
            ->map(fn ($year) => (int) $year)
            ->values();

        $selectedYear = $request->filled('year')
            ? (int) $request->query('year')
            : (int) ($availableYears->first() ?? now()->year);

        $selectedBarangay = $request->filled('barangay_id')
            ? (int) $request->query('barangay_id')
            : null;

        $budgetQuery = DB::table('budget_reports as br')
            ->join(
                'barangays as b',
                'b.barangay_id',
                '=',
                'br.barangay_id'
            )
            ->where(
                'br.budget_category',
                'annual_budget'
            )
            ->where(
                'br.fiscal_year',
                $selectedYear
            )
            ->whereIn(
                'br.status',
                [
                    'recorded',
                    'submitted',
                    'archived',
                ]
            );

        if ($selectedBarangay) {
            $budgetQuery->where(
                'br.barangay_id',
                $selectedBarangay
            );
        }

        $budgetRecords = $budgetQuery
            ->orderByDesc('br.submitted_at')
            ->orderByDesc('br.budget_report_id')
            ->get([
                'br.budget_report_id',
                'br.barangay_id',
                'br.fiscal_year',
                'br.title',
                'br.total_amount',
                'br.uploaded_file_name',
                'br.uploaded_file_path',
                'br.status',
                'br.submitted_at',
                'b.barangay_name',
            ])
            ->unique('barangay_id')
            ->keyBy('barangay_id');

        $displayBarangays = $barangays;

        if ($selectedBarangay) {
            $displayBarangays = $barangays
                ->where(
                    'barangay_id',
                    $selectedBarangay
                )
                ->values();
        }

        $budgetItems = $displayBarangays
            ->map(function ($barangay) use ($budgetRecords) {
                $budget = $budgetRecords->get(
                    $barangay->barangay_id
                );

                return (object) [
                    'barangay_id' =>
                        $barangay->barangay_id,

                    'barangay_name' =>
                        $barangay->barangay_name,

                    'budget_report_id' =>
                        $budget?->budget_report_id,

                    'fiscal_year' =>
                        $budget?->fiscal_year,

                    'title' =>
                        $budget?->title,

                    'total_amount' =>
                        $budget
                            ? (float) $budget->total_amount
                            : null,

                    'uploaded_file_name' =>
                        $budget?->uploaded_file_name,

                    'uploaded_file_path' =>
                        $budget?->uploaded_file_path,

                    'status' =>
                        $budget?->status,

                    'submitted_at' =>
                        $budget?->submitted_at,

                    'has_budget' =>
                        (bool) $budget,
                ];
            });

        $publishedCount = $budgetItems
            ->where(
                'has_budget',
                true
            )
            ->count();

        $totalBudget = $budgetItems
            ->where(
                'has_budget',
                true
            )
            ->sum(
                fn ($item) =>
                    (float) ($item->total_amount ?? 0)
            );

        return view(
            'public_portal.budgets',
            [
                'barangays' =>
                    $barangays,

                'availableYears' =>
                    $availableYears,

                'selectedYear' =>
                    $selectedYear,

                'selectedBarangay' =>
                    $selectedBarangay,

                'budgetItems' =>
                    $budgetItems,

                'publishedCount' =>
                    $publishedCount,

                'totalBudget' =>
                    $totalBudget,
            ]
        );
    }
}