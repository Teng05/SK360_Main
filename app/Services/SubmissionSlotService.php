<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubmissionSlotService
{
    public function expireOldSlots(): void
    {
        DB::table('submission_slots')
            ->where('status', 'open')
            ->whereDate('end_date', '<', Carbon::today())
            ->update(['status' => 'closed']);
    }

    public function chairmanReportSlots(int $barangayId): Collection
    {
        return $this->buildSlots($barangayId, 'accomplishment_report', ['SK Chairman', 'Both'], 'accomplishment_reports');
    }

    public function secretaryReportSlots(int $barangayId): Collection
    {
        return $this->buildSlots($barangayId, 'accomplishment_report', ['SK Secretary', 'Both'], 'accomplishment_reports');
    }

    public function chairmanBudgetSlots(int $barangayId): Collection
    {
        return $this->buildSlots($barangayId, 'budget_report', ['SK Chairman', 'Both'], 'budget_reports');
    }

    public function secretaryBudgetSlots(int $barangayId): Collection
    {
        return $this->buildSlots($barangayId, 'budget_report', ['SK Secretary', 'Both'], 'budget_reports');
    }

    public function resolveOpenSlot(int $slotId, string $submissionType, array $roles): ?object
    {
        $this->expireOldSlots();

        return DB::table('submission_slots')
            ->where('slot_id', $slotId)
            ->where('status', 'open')
            ->whereDate('end_date', '>=', Carbon::today())
            ->where('submission_type', $submissionType)
            ->where(function ($query) use ($roles) {
                foreach ($roles as $role) {
                    $query->orWhere('role', $role);
                }
            })
            ->first();
    }

    public function barangayHasSubmissionForSlot(string $table, int $barangayId, int $slotId): bool
    {
        return DB::table($table)
            ->where('barangay_id', $barangayId)
            ->where('slot_id', $slotId)
            ->exists();
    }

    protected function buildSlots(int $barangayId, string $submissionType, array $roles, string $submissionTable): Collection
    {
        $this->expireOldSlots();

        $submittedSlotIds = DB::table($submissionTable)
            ->where('barangay_id', $barangayId)
            ->whereNotNull('slot_id')
            ->pluck('slot_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return DB::table('submission_slots')
            ->where('status', 'open')
            ->whereDate('end_date', '>=', Carbon::today())
            ->where('submission_type', $submissionType)
            ->where(function ($query) use ($roles) {
                foreach ($roles as $role) {
                    $query->orWhere('role', $role);
                }
            })
            ->orderBy('start_date')
            ->get()
            ->map(function ($slot) use ($submittedSlotIds) {
                $slot->has_submitted = in_array((int) $slot->slot_id, $submittedSlotIds, true);
                $slot->slot_status_label = $slot->has_submitted ? 'Submitted' : 'Open';
                $slot->slot_status_badge = $slot->has_submitted
                    ? 'bg-green-100 text-green-600'
                    : 'bg-red-100 text-red-600';

                return $slot;
            });
    }
}
