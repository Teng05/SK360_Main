<?php

namespace Tests\Feature;

use App\Http\Controllers\sk_chairman\ChatController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChairmanChatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Isolated schema: never migrate or write to the application's database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));
        Schema::create('barangays', function (Blueprint $table) {
            $table->integer('barangay_id')->primary();
            $table->string('barangay_name');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->integer('user_id')->primary();
            foreach (['first_name', 'last_name', 'email', 'role', 'status'] as $column) $table->string($column);
            $table->integer('barangay_id')->nullable();
            $table->string('profile_pic')->nullable();
            $table->timestamp('archived_at')->nullable();
        });
        DB::table('barangays')->insert(['barangay_id' => 1, 'barangay_name' => 'Council District']);
        foreach (range(1, 7) as $id) {
            DB::table('users')->insert([
                'user_id' => $id, 'first_name' => 'Official', 'last_name' => "Person$id",
                'email' => "person$id@example.test", 'barangay_id' => 1,
                'role' => $id === 2 ? 'sk_president' : ($id === 3 ? 'sk_secretary' : ($id === 7 ? 'other' : 'sk_chairman')),
                'status' => $id === 6 ? 'inactive' : 'active',
                'archived_at' => $id === 5 ? '2026-01-01 00:00:00' : null,
                'profile_pic' => match ($id) { 2 => 'uploads/profile_pics/photo.png', 3 => 'legacy.png', default => null },
            ]);
        }
        $this->actingAs(User::findOrFail(1));
    }

    public function test_search_preserves_filters_keys_and_photo_fallbacks(): void
    {
        $controller = new ChatController();
        $result = $controller->searchUsers(Request::create('/', 'GET', ['search' => 'Official']))->getData(true);
        $this->assertSame(['2', '3', '4'], array_column($result, 'id'));
        $this->assertSame(['id', 'name', 'email', 'role', 'barangay', 'profile_pic_url'], array_keys($result[0]));
        $this->assertSame(asset('uploads/profile_pics/photo.png'), $result[0]['profile_pic_url']);
        $this->assertSame(asset('uploads/profile_pics/legacy.png'), $result[1]['profile_pic_url']);
        $this->assertNull($result[2]['profile_pic_url']);
        foreach (['Person2', 'person2@example.test'] as $search) {
            $this->assertSame(['2'], array_column($controller->searchUsers(Request::create('/', 'GET', compact('search')))->getData(true), 'id'));
        }
        $this->assertCount(3, $controller->searchUsers(Request::create('/', 'GET', ['search' => 'District']))->getData(true));
        $this->assertSame([], $controller->searchUsers(Request::create('/', 'GET', ['search' => ' ']))->getData(true));
    }

    public function test_group_members_exclude_archived_inactive_and_unrelated_roles(): void
    {
        $members = (new \ReflectionMethod(ChatController::class, 'groupMembers'))->invoke(new ChatController());
        $this->assertSame(['1', '2', '3', '4'], array_column($members, 'id'));
        $this->assertSame(asset('uploads/profile_pics/photo.png'), $members[1]['profile_pic_url']);
        $this->assertNull($members[0]['profile_pic_url']);
    }

    public function test_other_roles_cannot_use_chairman_search(): void
    {
        $this->actingAs(User::findOrFail(3));
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new ChatController())->searchUsers(Request::create('/', 'GET', ['search' => 'Official']));
    }
}
