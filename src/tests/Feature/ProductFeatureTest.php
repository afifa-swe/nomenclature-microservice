<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
// migrations are handled by RefreshDatabase in tests
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Product;
use App\Jobs\ImportProductsJob;
use Illuminate\Http\UploadedFile;

class ProductFeatureTest extends TestCase
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

    public function test_crud_products()
    {
    $user = User::factory()->create();
    $this->actingAs($user, 'api');

        $cat = Category::factory()->create();
        $sup = Supplier::factory()->create();

        $res = $this->getJson('/api/products');
        $res->assertStatus(200)->assertJson(['success' => true]);

        $payload = ['name' => 'P1', 'price' => 9.99, 'category_id' => $cat->id, 'supplier_id' => $sup->id];
        $store = $this->postJson('/api/products', $payload);
        $store->assertStatus(201)->assertJson(['success' => true])->assertJsonFragment(['message' => 'Product created']);

        $this->assertDatabaseHas('products', ['name' => 'P1']);
        $id = $store->json('data.id') ?? Product::where('name','P1')->value('id');

        $upd = $this->putJson('/api/products/'.$id, ['name' => 'P1-upd']);
        $upd->assertStatus(200)->assertJson(['success' => true])->assertJsonFragment(['message' => 'Product updated']);

        $del = $this->deleteJson('/api/products/'.$id);
        $del->assertStatus(200)->assertJson(['success' => true])->assertJsonFragment(['message' => 'Soft-deleted']);
    }

    public function test_import_csv_dispatches_jobs_and_logs()
    {
        Storage::fake('local');
        Bus::fake();
        Log::spy();

    $user = User::factory()->create();
    $this->actingAs($user, 'api');

        Category::factory()->count(3)->create();
        Supplier::factory()->count(3)->create();

        $rows = [];
        $header = ['name','description','price','category_id','supplier_id'];
        $rows[] = implode(',', $header);
        for ($i=0;$i<20;$i++) {
            $rows[] = implode(',', ["Product{$i}","Desc{$i}","{$i}", '', '']);
        }

        $csv = implode("\n", $rows);
        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $res = $this->postJson('/api/products/import', ['file' => $file]);
        $res->assertStatus(200)->assertJson(['success' => true])->assertJsonFragment(['message' => 'Импорт запущен']);

        $expectedChunks = (int) ceil(20 / 10);
        Bus::assertDispatchedTimes(ImportProductsJob::class, $expectedChunks);

        Log::shouldHaveReceived('info')->withArgs(function ($msg, $ctx = null) {
            if (is_string($msg) && str_contains($msg, 'Dispatched chunk')) return true;
            return false;
        })->atLeast()->once();
    }
}
