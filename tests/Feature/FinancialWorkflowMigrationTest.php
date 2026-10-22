<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinancialWorkflowMigrationTest extends TestCase
{
    public function test_migration_keeps_existing_references_and_allows_null_without_touching_existing_records(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('annual_budgets', function (Blueprint $table) {
            $table->increments('annual_budget_id'); $table->string('approval_reference', 150);
        });
        DB::table('annual_budgets')->insert(['approval_reference' => 'Original session reference']);
        $migration = require database_path('migrations/2026_10_22_000001_refine_financial_workflows.php');
        $migration->up();
        DB::table('annual_budgets')->insert(['approval_reference' => null]);
        $this->assertDatabaseHas('annual_budgets', ['annual_budget_id' => 1, 'approval_reference' => 'Original session reference']);
        $this->assertDatabaseHas('annual_budgets', ['annual_budget_id' => 2, 'approval_reference' => null]);
        $this->assertFalse(Schema::hasTable('financial_categories'));
    }
}
