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

    public function test_index_and_records_in_changelog()
    {
    $user = User::factory()->create();
    $this->actingAs($user, 'api');

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
