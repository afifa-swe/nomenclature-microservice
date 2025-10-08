<?php
namespace Tests\Unit;

use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\Api\ProductController;
use App\Jobs\ImportProductsJob;

class ProductControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_paginated_products()
    {
        $perPage = 25;
        $request = new Request(['per_page' => $perPage]);

        $queryMock = Mockery::mock();
        $paginatorMock = Mockery::mock();
        $paginatorMock->shouldReceive('appends')->with($request->query())->andReturn('PAGINATED_RESULT');
        $queryMock->shouldReceive('paginate')->with($perPage)->andReturn($paginatorMock);

        $productFacade = Mockery::mock('alias:App\\Models\\Product');
        $productFacade->shouldReceive('where')->with('created_by', Mockery::any())->andReturn($queryMock);

        $controller = new ProductController();
        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertEquals('Products list', $payload['message']);
        $this->assertEquals('PAGINATED_RESULT', $payload['data']);
        $this->assertArrayHasKey('timestamp', $payload);
        $this->assertTrue($payload['success']);
    }

    public function test_store_creates_product()
    {
        $productMock = (object)['id' => 'uuid', 'name' => 'Test Product'];
        $productFacade = Mockery::mock('alias:App\\Models\\Product');
        $productFacade->shouldReceive('create')->once()->andReturn($productMock);

        $controller = new ProductController();
        $request = new StoreProductRequest();
        $request->merge(['name' => 'Test Product', 'price' => 10, 'category_id' => '00000000-0000-0000-0000-000000000000', 'supplier_id' => '00000000-0000-0000-0000-000000000000']);
        $response = $controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertEquals('Product created', $payload['message']);
        $this->assertEquals('uuid', $payload['data']['id']);
        $this->assertEquals('Test Product', $payload['data']['name']);
        $this->assertTrue($payload['success']);
    }

    public function test_update_modifies_product()
    {
        Auth::shouldReceive('id')->andReturn('user-1');

        $id = 'some-id';
        $data = ['name' => 'Updated', 'price' => 5];

        $productMock = Mockery::mock();
        $productMock->created_by = 'user-1';
        $productMock->shouldReceive('fill')->once()->with($data);
        $productMock->shouldReceive('save')->once()->andReturnTrue();

        $productFacade = Mockery::mock('alias:App\\Models\\Product');
        $productFacade->shouldReceive('find')->with($id)->andReturn($productMock);

        $controller = new ProductController();
        $request = new UpdateProductRequest();
        $request->merge($data);
        $response = $controller->update($request, $id);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertEquals('Product updated', $payload['message']);
        $this->assertTrue($payload['success']);
    }

    public function test_destroy_soft_deletes_product()
    {
        Auth::shouldReceive('id')->andReturn('user-1');

        $id = 'del-id';

        $productMock = Mockery::mock();
        $productMock->created_by = 'user-1';
        $productMock->is_active = true;
        $productMock->shouldReceive('save')->once()->andReturnTrue();

        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('find')->with($id)->andReturn($productMock);

        $productFacade = Mockery::mock('alias:App\\Models\\Product');
        $productFacade->shouldReceive('withoutGlobalScopes')->andReturn($queryMock);

        $controller = new ProductController();
        $response = $controller->destroy($id);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertEquals('Soft-deleted', $payload['message']);
        $this->assertTrue($payload['success']);
        $this->assertFalse($productMock->is_active);
    }

    public function test_import_dispatches_chunks()
    {
        Auth::shouldReceive('id')->andReturn('user-1');

        $tmp = tmpfile();
        $meta = stream_get_meta_data($tmp);
        $path = $meta['uri'];

        $header = ["name", "description", "price"];
        fwrite($tmp, implode(',', $header) . "\n");

        $totalValid = 0;
        for ($i = 0; $i < 23; $i++) {
            if ($i % 6 === 0) {
                fwrite($tmp, ",desc{$i},{$i}\n");
            } else {
                fwrite($tmp, "Product{$i},desc{$i},{$i}\n");
                $totalValid++;
            }
        }
        fflush($tmp);

        $uploaded = new UploadedFile($path, 'products.csv', null, null, true);

        Bus::fake();

        $request = new Request([], [], [], [], ['file' => $uploaded]);
        $controller = new ProductController();
        $response = $controller->import($request);

        $expectedChunks = (int) ceil($totalValid / 10);
        Bus::assertDispatchedTimes(ImportProductsJob::class, $expectedChunks);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertEquals($totalValid, $payload['data']['total_rows']);
        $this->assertEquals($expectedChunks, $payload['data']['chunks']);
        $this->assertEquals(10, $payload['data']['chunk_size']);

        fclose($tmp);
    }
}
