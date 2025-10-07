<?php
namespace Tests\Unit;

use Tests\TestCase;
use Mockery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_categories()
    {
        $request = new Request();

        $paginator = Mockery::mock();
        $paginator->shouldReceive('appends')->andReturn('PAG');

        $query = Mockery::mock();
        $query->shouldReceive('paginate')->andReturn($paginator);

        $cat = Mockery::mock('alias:App\\Models\\Category');
        $cat->shouldReceive('where')->andReturn($query);

    $controller = new \App\Http\Controllers\Api\CategoryController();
        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
    }

    public function test_store_creates_category()
    {
        $mock = Mockery::mock('alias:App\\Models\\Category');
        $mock->shouldReceive('create')->once()->andReturn((object)['id' => 'uuid', 'title' => 'Test Category']);

    $controller = new \App\Http\Controllers\Api\CategoryController();
    $request = new \App\Http\Requests\StoreCategoryRequest();
    $request->merge(['name' => 'Test Category']);
    $response = $controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertEquals('Category created', $payload['message']);
    }

    public function test_update_modifies_category()
    {
        Auth::shouldReceive('id')->andReturn('user-1');

    $id = 'cat-1';
    $data = ['name' => 'Updated'];

        $model = Mockery::mock();
        $model->created_by = 'user-1';
        $model->shouldReceive('fill')->once()->with($data);
        $model->shouldReceive('save')->once()->andReturnTrue();

    $facade = Mockery::mock('alias:App\\Models\\Category');
    $facade->shouldReceive('find')->with($id)->andReturn($model);

    $controller = new \App\Http\Controllers\Api\CategoryController();
    $request = new \App\Http\Requests\UpdateCategoryRequest();
    $request->merge($data);
    $response = $controller->update($request, $id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Category updated', $response->getData(true)['message']);
    }

    public function test_destroy_soft_deletes_category()
    {
        Auth::shouldReceive('id')->andReturn('user-1');

        $id = 'cat-del';
            $model = new class {
                public $created_by = 'user-1';
                public $is_active = true;
                public function save() { return true; }
                public function delete() { $this->is_active = false; return true; }
            };

    $facade = Mockery::mock('alias:App\\Models\\Category');
    $facade->shouldReceive('find')->with($id)->andReturn($model);

    $controller = new \App\Http\Controllers\Api\CategoryController();
        $response = $controller->destroy($id);

        $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('Category deleted', $response->getData(true)['message']);
    }
}
