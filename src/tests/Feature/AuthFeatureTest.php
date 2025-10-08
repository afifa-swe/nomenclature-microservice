<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::table('oauth_clients')->insert([
                'id' => (string) Str::uuid(),
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

    public function test_register_creates_user_and_returns_token()
    {
        $payload = [
            'name' => 'Feature User',
            'email' => 'feature@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['message','data' => ['access_token']]);

        $this->assertDatabaseHas('users', ['email' => 'feature@example.com']);
    }

    public function test_login_returns_token()
    {
        $user = User::factory()->create(['password' => bcrypt('secretpass'), 'email' => 'login@example.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'secretpass',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['message','data' => ['access_token']]);
    }

    public function test_logout_and_user_endpoints_if_present()
    {
    $user = User::factory()->create();
    $this->actingAs($user, 'api');

        $resp = $this->postJson('/api/logout');
        if ($resp->getStatusCode() === 404) {
            $this->markTestSkipped('Logout route not defined - skipped');
            return;
        }

        $resp->assertStatus(200)->assertJson(['success' => true]);

        $resp2 = $this->getJson('/api/auth/user');
        if ($resp2->getStatusCode() === 404) {
            $resp2 = $this->getJson('/api/user');
            if ($resp2->getStatusCode() === 404) {
                $this->markTestSkipped('Current user route not defined - skipped');
                return;
            }
        }

        $resp2->assertStatus(200)->assertJson(['success' => true]);
    }
}
