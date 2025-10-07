<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
// migrations are handled by RefreshDatabase in tests; no Artisan call required here
use App\Models\User;
use App\Models\Category;

class CategoryFeatureTest extends TestCase
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
        // Insert a personal access oauth client; ignore failures if it already exists
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
            // ignore - client may already exist or DB column types differ
        }
    }

    /** @group feature */
    public function test_index_store_update_destroy_categories()
    {
    $user = User::factory()->create();
    $this->actingAs($user, 'api');

        // index (empty)
        $res = $this->getJson('/api/categories');
        $res->assertStatus(200)->assertJson(['success' => true]);

        // store
        $payload = ['name' => 'NewCat'];
        $res = $this->postJson('/api/categories', $payload);
        $res->assertStatus(201)->assertJson(['success' => true])->assertJsonFragment(['message' => 'Category created']);

        $this->assertDatabaseHas('categories', ['name' => 'NewCat']);
        $id = $res->json('data.id') ?? Category::where('name','NewCat')->value('id');

        // update
        $upd = $this->putJson('/api/categories/'.$id, ['name' => 'UpdatedCat']);
        $upd->assertStatus(200)->assertJson(['success' => true])->assertJsonFragment(['message' => 'Category updated']);
        $this->assertDatabaseHas('categories', ['name' => 'UpdatedCat']);

        // destroy
        $del = $this->deleteJson('/api/categories/'.$id);
        $del->assertStatus(200)->assertJson(['success' => true])->assertJsonFragment(['message' => 'Category deleted']);
    }
}
