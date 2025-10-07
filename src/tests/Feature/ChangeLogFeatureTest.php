<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
// migrations are handled by RefreshDatabase in tests
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChangeLogFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Ensure tests run against PostgreSQL (must be set before RefreshDatabase/bootstrap)
        putenv('DB_CONNECTION=pgsql');
        putenv('DB_HOST=127.0.0.1');
        putenv('DB_DATABASE=app');
        putenv('DB_USERNAME=app');
        putenv('DB_PASSWORD=app');

    parent::setUp();
        try {
            \Illuminate\Support\Facades\DB::table('oauth_clients')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'owner_type' => null,
                'owner_id' => null,
                'name' => 'Test Personal Access Client',
                'secret' => bin2hex(random_bytes(20)),
                'provider' => null,
                'redirect_uris' => '',
                'grant_types' => json_encode(['personal_access']),
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /** @group feature */
    public function test_index_and_records_in_changelog()
    {
    $user = User::factory()->create();
    $this->actingAs($user, 'api');

        // insert a change log row
        DB::table('change_logs')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'entity_type' => 'product',
            'action' => 'created',
            'entity_id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'changes' => json_encode(['foo' => 'bar']),
            'created_at' => now(),
        ]);

        $res = $this->getJson('/api/changes');
        $res->assertStatus(200)->assertJson(['success' => true])->assertJsonFragment(['message' => 'Change logs list']);
    }
}
