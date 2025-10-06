<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use App\Jobs\ImportProductsJob;
use App\Http\Controllers\Api\ProductController;
use Mockery;

class ProductImportLogicTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_import_splits_csv_into_chunks_and_dispatches_jobs()
    {
        //Создаём временный CSV
        $tmp = tmpfile();
        $meta = stream_get_meta_data($tmp);
        $path = $meta['uri'];

        $header = ["name", "description", "price"];
        fwrite($tmp, implode(',', $header) . "\n");

        $totalValid = 0;
        for ($i = 0; $i < 25; $i++) {
            if ($i % 7 === 0) {
                fwrite($tmp, ",desc{$i},{$i}\n");
            } else {
                fwrite($tmp, "Product{$i},desc{$i},{$i}\n");
                $totalValid++;
            }
        }
        fflush($tmp);

        // Подготавливаем фейковый CSV-файл
        $uploaded = new UploadedFile($path, 'products.csv', null, null, true);

        //Фейкаем очередь и лог
        Bus::fake();
        Log::spy();

        //Создаём контроллер и подготавливаем запрос
        $request = Request::create('/api/products/import', 'POST', [], [], ['file' => $uploaded]);
        $controller = new ProductController();

        //Вызываем импорт
        $response = $controller->import($request);

        //Проверяем результат
        $this->assertEquals(200, $response->getStatusCode());

        $payload = $response->getData(true);
        $expectedChunks = (int) ceil($totalValid / 10);

        $this->assertEquals($totalValid, $payload['data']['total_rows']);
        $this->assertEquals($expectedChunks, $payload['data']['chunks']);
        $this->assertEquals(10, $payload['data']['chunk_size']);

        //Проверяем что джобы реально диспатчились
        Bus::assertDispatchedTimes(ImportProductsJob::class, $expectedChunks);

        //Проверяем что логи записались
        Log::shouldHaveReceived('info')->atLeast()->once();

        fclose($tmp);
    }
}
