<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FinancialCategoryService
{
    public const DEFAULTS = ['Sports and Youth Development', 'Education', 'Health', 'Environment', 'Livelihood',
        'Community Development', 'Leadership and Governance', 'Social Services', 'Cultural Activities', 'Administrative'];

    public function normalize(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    public function key(string $name): string
    {
        return hash('sha256', mb_strtolower($this->normalize($name)));
    }

    public function options(): Collection
    {
        return collect(self::DEFAULTS)->merge(DB::table('budget_projects')->distinct()->pluck('category'))
            ->map(fn ($name) => $this->normalize($name))->filter()
            ->unique(fn ($name) => $this->key($name))->sort()->values();
    }

    public function resolve(string $name): string
    {
        $name = $this->normalize($name);
        if ($name === '' || mb_strlen($name) > 100) {
            throw ValidationException::withMessages(['category' => 'Enter a category of up to 100 characters.']);
        }
        $key = $this->key($name);
        // The owning project persists the category in its existing transaction.
        // Historical project values stay intact; only dropdown options are deduplicated.
        return $this->options()->first(fn ($existing) => $this->key($existing) === $key) ?? $name;
    }
}
