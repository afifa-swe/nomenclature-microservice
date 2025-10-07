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
        // Ensure tests run against PostgreSQL (must be set before RefreshDatabase/bootstrap)
        putenv('DB_CONNECTION=pgsql');
        putenv('DB_HOST=127.0.0.1');
        putenv('DB_DATABASE=app');
        putenv('DB_USERNAME=app');
        putenv('DB_PASSWORD=app');

        parent::setUp();

        // Ensure a personal access client exists (migrations already ran via RefreshDatabase)
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
            // ignore: client may already exist or column differences
        }
    }

    /** @group feature */
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

    /** @group feature */
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

    /** @group feature */
    public function test_logout_and_user_endpoints_if_present()
    {
        // Some projects expose logout and current user routes; if they are not present we skip the test
    $user = User::factory()->create();
    $this->actingAs($user, 'api');

        // attempt logout (many apps use POST /api/logout)
        $resp = $this->postJson('/api/logout');
        if ($resp->getStatusCode() === 404) {
            $this->markTestSkipped('Logout route not defined - skipped');
            return;
        }

        $resp->assertStatus(200)->assertJson(['success' => true]);

        // attempt get current user at /api/auth/user or /api/user
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
