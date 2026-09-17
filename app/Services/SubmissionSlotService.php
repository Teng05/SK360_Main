<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubmissionSlotService
{
    protected function today(): string
    {
        return Carbon::now('Asia/Manila')->toDateString();
    }

    public function expireOldSlots(): void
    {
        $today = $this->today();

        DB::table('submission_slots')
            ->where('status', 'open')
            ->whereDate('end_date', '<', $today)
            ->update(['status' => 'closed']);
    }

    public function chairmanReportSlots(int $barangayId): Collection
    {
        return $this->buildSlots(
            $barangayId,
            'accomplishment_report',
            ['SK Chairman', 'Both'],
            'accomplishment_reports'
        );
    }

    public function secretaryReportSlots(int $barangayId): Collection
    {
        return $this->buildSlots(
            $barangayId,
            'accomplishment_report',
            ['SK Secretary', 'Both'],
            'accomplishment_reports'
        );
    }

    public function chairmanBudgetSlots(int $barangayId): Collection
    {
        return $this->buildSlots(
            $barangayId,
            'budget_report',
            ['SK Chairman', 'Both'],
            'budget_reports'
        );
    }

    public function secretaryBudgetSlots(int $barangayId): Collection
    {
        return $this->buildSlots(
            $barangayId,
            'budget_report',
            ['SK Secretary', 'Both'],
            'budget_reports'
        );
    }

    public function resolveOpenSlot(
        int $slotId,
        string $submissionType,
        array $roles
    ): ?object {
        $this->expireOldSlots();

        $today = $this->today();

        return DB::table('submission_slots')
            ->where('slot_id', $slotId)
            ->where('status', 'open')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where('submission_type', $submissionType)
            ->whereIn('role', $roles)
            ->first();
    }

    public function barangayHasSubmissionForSlot(
        string $table,
        int $barangayId,
        int $slotId
    ): bool {
        return DB::table($table)
            ->where('barangay_id', $barangayId)
            ->where('slot_id', $slotId)
            ->exists();
    }

    protected function buildSlots(
        int $barangayId,
        string $submissionType,
        array $roles,
        string $submissionTable
    ): Collection {
        $this->expireOldSlots();

        $today = $this->today();

        $submittedSlotIds = DB::table($submissionTable)
            ->where('barangay_id', $barangayId)
            ->whereNotNull('slot_id')
            ->pluck('slot_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return DB::table('submission_slots')
            ->where('status', 'open')
            ->whereDate('end_date', '>=', $today)
            ->where('submission_type', $submissionType)
            ->whereIn('role', $roles)
            ->orderBy('start_date')
            ->get()
            ->map(function ($slot) use ($submittedSlotIds, $today) {
                $slot->has_submitted = in_array(
                    (int) $slot->slot_id,
                    $submittedSlotIds,
                    true
                );

                $slot->is_upcoming = $slot->start_date > $today;

                if ($slot->has_submitted) {
                    $slot->slot_status_label = 'Submitted';
                    $slot->slot_status_badge = 'bg-green-100 text-green-600';
                } elseif ($slot->is_upcoming) {
                    $slot->slot_status_label = 'Upcoming';
                    $slot->slot_status_badge = 'bg-blue-100 text-blue-600';
                } else {
                    $slot->slot_status_label = 'Open';
                    $slot->slot_status_badge = 'bg-red-100 text-red-600';
                }

                if ($slot->submission_type === 'budget_report') {
                    $slot->budget_category_label = $this->budgetCategoryLabel(
                        $slot->budget_category ?? null
                    );

                    $slot->budget_period_label = $this->budgetPeriodLabel($slot);

                    $slot->template_available = $this->templateAvailable($slot);
                }

                return $slot;
            });
    }

    protected function budgetCategoryLabel(?string $category): string
    {
        return match ($category) {
            'annual_budget' => 'Annual Budget',
            'supplemental_budget' => 'Supplemental Budget',
            'coa_report' => 'Financial / COA Report',
            default => 'Budget / Financial Report',
        };
    }

    protected function budgetPeriodLabel(object $slot): string
    {
        if (($slot->budget_category ?? null) !== 'coa_report') {
            return !empty($slot->fiscal_year)
                ? 'FY '.$slot->fiscal_year
                : '';
        }

        $year = $slot->fiscal_year ?? '';

        return match ($slot->budget_period_type ?? null) {
            'monthly' => $this->monthName(
                (int) ($slot->fiscal_month ?? 0)
            ).' '.$year,

            'quarterly' => ($slot->fiscal_quarter ?? 'Quarterly')
                .' '.$year,

            'semi_annual' => (
                ($slot->fiscal_half ?? null) === 'H1'
                    ? 'First Half'
                    : 'Second Half'
            ).' '.$year,

            'annual' => 'Annual '.$year,

            default => $year ? 'FY '.$year : '',
        };
    }

    protected function templateAvailable(object $slot): bool
    {
        return ($slot->budget_category ?? null) === 'coa_report'
            && in_array(
                $slot->budget_period_type ?? null,
                ['monthly', 'quarterly', 'annual'],
                true
            );
    }

    protected function monthName(int $month): string
    {
        return match ($month) {
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
            default => 'Monthly',
        };
    }
}