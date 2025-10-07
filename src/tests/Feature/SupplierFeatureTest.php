<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
// migrations are handled by RefreshDatabase in tests
use App\Models\User;
use App\Models\Supplier;

class SupplierFeatureTest extends TestCase
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
    public function test_index_store_update_destroy_suppliers()
    {
    $user = User::factory()->create();
    $this->actingAs($user, 'api');

        $res = $this->getJson('/api/suppliers');
        $res->assertStatus(200)->assertJson(['success' => true]);

        $payload = ['name' => 'SupCo', 'email' => 'sup@example.com'];
        $store = $this->postJson('/api/suppliers', $payload);
        $store->assertStatus(201)->assertJson(['success' => true])->assertJsonFragment(['message' => 'Supplier created']);

        $this->assertDatabaseHas('suppliers', ['name' => 'SupCo']);
        $id = $store->json('data.id') ?? Supplier::where('name','SupCo')->value('id');

        $upd = $this->putJson('/api/suppliers/'.$id, ['name' => 'SupCoUpdated']);
        $upd->assertStatus(200)->assertJson(['success' => true])->assertJsonFragment(['message' => 'Supplier updated']);
        $this->assertDatabaseHas('suppliers', ['name' => 'SupCoUpdated']);

        $del = $this->deleteJson('/api/suppliers/'.$id);
        $del->assertStatus(200)->assertJson(['success' => true])->assertJsonFragment(['message' => 'Supplier deleted']);
    }
}
