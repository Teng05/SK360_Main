<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialTermScope
{
    public function current(bool $lock = false): ?object
    {
        return DB::table('administration_terms')->where('status', 'current')->orderByDesc('term_id')
            ->when($lock, fn ($query) => $query->lockForUpdate())->first();
    }

    public function writeTermId(): int
    {
        $term = $this->current(true);
        abort_unless($term, 404, 'No current administration.');
        return (int)$term->term_id;
    }

    public function validateYear(int $year, object $term): void
    {
        if ($year < (int)$term->start_year || $year > (int)$term->end_year) {
            throw ValidationException::withMessages(['year' => 'Choose a fiscal year within the current administration. Previous records are available in Archive.']);
        }
    }
}
