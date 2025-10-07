<?php
namespace Tests\Unit;

use Tests\TestCase;
use Mockery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_suppliers()
    {
        $request = new Request();

        $paginator = Mockery::mock();
        $paginator->shouldReceive('appends')->andReturn('PAG');

        $query = Mockery::mock();
        $query->shouldReceive('paginate')->andReturn($paginator);

        $facade = Mockery::mock('alias:App\\Models\\Supplier');
        $facade->shouldReceive('where')->andReturn($query);

    $controller = new \App\Http\Controllers\Api\SupplierController();
    $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
    }

    public function test_store_creates_supplier()
    {
        $mock = Mockery::mock('alias:App\\Models\\Supplier');
        $mock->shouldReceive('create')->once()->andReturn((object)['id' => 'uuid', 'title' => 'Test Supplier']);

    $controller = new \App\Http\Controllers\Api\SupplierController();
    $request = new \App\Http\Requests\StoreSupplierRequest();
    $request->merge(['name' => 'Test Supplier']);
    $response = $controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertEquals('Supplier created', $payload['message']);
    }

    public function test_update_modifies_supplier()
    {
        Auth::shouldReceive('id')->andReturn('user-1');

    $id = 'sup-1';
    $data = ['name' => 'Updated'];

    $model = Mockery::mock();
    $model->created_by = 'user-1';
    $model->shouldReceive('fill')->once()->with($data);
    $model->shouldReceive('save')->once()->andReturnTrue();

        $facade = Mockery::mock('alias:App\\Models\\Supplier');
        $facade->shouldReceive('find')->with($id)->andReturn($model);

    $controller = new \App\Http\Controllers\Api\SupplierController();
    $request = new \App\Http\Requests\UpdateSupplierRequest();
    $request->merge($data);
    $response = $controller->update($request, $id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Supplier updated', $response->getData(true)['message']);
    }

    public function test_destroy_soft_deletes_supplier()
    {
        Auth::shouldReceive('id')->andReturn('user-1');

        $id = 'sup-del';
        $model = new class {
            public $created_by = 'user-1';
            public $is_active = true;
            public function save() { return true; }
            public function delete() { $this->is_active = false; return true; }
        };

        $facade = Mockery::mock('alias:App\\Models\\Supplier');
        $facade->shouldReceive('find')->with($id)->andReturn($model);

        $controller = new \App\Http\Controllers\Api\SupplierController();
        $response = $controller->destroy($id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Supplier deleted', $response->getData(true)['message']);
        $this->assertFalse($model->is_active);
    }
}
