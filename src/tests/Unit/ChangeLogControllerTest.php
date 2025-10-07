<?php
namespace Tests\Unit;

use Tests\TestCase;
use Mockery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChangeLogControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_changelogs()
    {
        $request = new Request();

        $paginator = Mockery::mock();
        $paginator->shouldReceive('appends')->andReturn('PAG');

    $query = Mockery::mock();
    $query->shouldReceive('where')->andReturnSelf();
    $query->shouldReceive('orderBy')->andReturnSelf();
    $query->shouldReceive('paginate')->andReturn($paginator);

    DB::shouldReceive('table')->with('change_logs')->andReturn($query);
    \Illuminate\Support\Facades\Auth::shouldReceive('id')->andReturn('user-1');

        $controller = new \App\Http\Controllers\Api\ChangeLogController();
        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
    }
}
