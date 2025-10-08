<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Category;

class CategoryFeatureTest extends TestCase
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
        }
    }

    public function test_index_store_update_destroy_categories()
    {
    $user = User::factory()->create();
    $this->actingAs($user, 'api');

        $res = $this->getJson('/api/categories');
        $res->assertStatus(200)->assertJson(['success' => true]);

        $payload = ['name' => 'NewCat'];
        $res = $this->postJson('/api/categories', $payload);
        $res->assertStatus(201)->assertJson(['success' => true])->assertJsonFragment(['message' => 'Category created']);

        $this->assertDatabaseHas('categories', ['name' => 'NewCat']);
        $id = $res->json('data.id') ?? Category::where('name','NewCat')->value('id');

        $upd = $this->putJson('/api/categories/'.$id, ['name' => 'UpdatedCat']);
        $upd->assertStatus(200)->assertJson(['success' => true])->assertJsonFragment(['message' => 'Category updated']);
        $this->assertDatabaseHas('categories', ['name' => 'UpdatedCat']);


        $del = $this->deleteJson('/api/categories/'.$id);
        $del->assertStatus(200)->assertJson(['success' => true])->assertJsonFragment(['message' => 'Category deleted']);
    }
}
