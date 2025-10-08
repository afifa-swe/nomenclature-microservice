<?php
namespace Tests\Unit;

use Tests\TestCase;
use Mockery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\AuthController;

class AuthControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_login_success()
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'secret'];
        Auth::shouldReceive('attempt')->with($credentials)->once()->andReturnTrue();

        $userMock = Mockery::mock();
        $tokenObj = (object)['accessToken' => 'tok', 'token' => (object)['expires_at' => now()->addHour()]];
        $userMock->shouldReceive('createToken')->with('api-token')->andReturn($tokenObj)->once();

        Auth::shouldReceive('user')->andReturn($userMock);

        $controller = new AuthController();
        $request = new Request($credentials);

        $response = $controller->login($request);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertEquals('Login successful', $payload['message']);
    }

    public function test_login_failure()
    {
        $credentials = ['email' => 'bad@example.com', 'password' => 'wrong'];

        Auth::shouldReceive('attempt')->with($credentials)->once()->andReturnFalse();

        $controller = new AuthController();
        $request = new Request($credentials);

        $response = $controller->login($request);

        $this->assertEquals(401, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertFalse($payload['success']);
    }

    public function test_register_creates_user_and_returns_token()
    {
        $data = ['name' => 'T', 'email' => 't@example.com', 'password' => 'password', 'password_confirmation' => 'password'];

        $userMock = Mockery::mock();
        $tokenObj = (object)['accessToken' => 'tok', 'token' => (object)['expires_at' => now()->addHour()]];
        $userMock->shouldReceive('createToken')->with('api-token')->andReturn($tokenObj)->once();

        $userFacade = Mockery::mock('alias:App\\Models\\User');
        $userFacade->shouldReceive('create')->once()->andReturn($userMock);

        $controller = new AuthController();
        $request = new class($data) extends \Illuminate\Http\Request {
            public function __construct($data = [])
            {
                parent::__construct($data, [], [], [], [], [], null);
            }

            public function validate($rules)
            {
                return $this->all();
            }
        };

        $response = $controller->register($request);

        $this->assertEquals(201, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertEquals('User registered', $payload['message']);
    }
}
