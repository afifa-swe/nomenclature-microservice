<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Jobs\ImportProductsJob;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    private function ok($message, $data = null, $code = 200)
    {
        $payload = [
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'success' => true,
        ];

        return response()->json($payload, $code);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 100);
        if ($perPage < 1) {
            $perPage = 1;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

    $query = Product::where('created_by', auth()->id());

        if ($request->has('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->query('supplier_id'));
        }

        if ($request->has('search')) {
            $q = $request->query('search');
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($q) . '%']);
        }

        $results = $query->paginate($perPage)->appends($request->query());

        return $this->ok('Products list', $results);
    }

    public function show($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'message' => 'Не найдено',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 404);
        }

    if ((string) $product->created_by !== (string) auth()->id()) {
            return response()->json([
                'message' => 'Forbidden',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 403);
        }

        return $this->ok('Product retrieved', $product);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->only([
            'name', 'description', 'category_id', 'supplier_id', 'price', 'file_url', 'is_active'
        ]);

    $product = Product::create($data);

        return $this->ok('Product created', $product, 201);
    }

    public function update(UpdateProductRequest $request, $id)
    {
    $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'message' => 'Не найдено',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 404);
        }

        $data = $request->only([
            'name', 'description', 'category_id', 'supplier_id', 'price', 'file_url', 'is_active'
        ]);

    if ((string) $product->created_by !== (string) auth()->id()) {
            return response()->json([
                'message' => 'Forbidden',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 403);
        }

        $product->fill($data);
        $product->save();

        return $this->ok('Product updated', $product);
    }

    public function destroy($id)
    {
    $product = Product::withoutGlobalScopes()->find($id);
        if (!$product) {
            return response()->json([
                'message' => 'Не найдено',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 404);
        }

    if ((string) $product->created_by !== (string) auth()->id()) {
            return response()->json([
                'message' => 'Forbidden',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 403);
        }

        $product->is_active = false;
        $product->save();

        return $this->ok('Soft-deleted', null);
    }

    public function upload(Request $request)
    {
        // validate incoming file (up to 5MB)
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        // debug logging to ensure file arrives
        Log::info('Upload debug (start)', [
            'hasFile' => $request->hasFile('image'),
        ]);

        if (! $request->hasFile('image')) {
            Log::warning('Upload failed: no file in request');
            return response()->json([
                'message' => 'Файл не пришёл',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 422);
        }

        $file = $request->file('image');

        // Log file details for debugging
        Log::info('Upload debug file', [
            'originalName' => $file->getClientOriginalName(),
            'clientMimeType' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'path' => $file->getPathname(),
            'isValid' => $file->isValid(),
        ]);

        if (! $file->isValid()) {
            Log::warning('Upload failed: uploaded file is not valid');
            return response()->json([
                'message' => 'Файл неверный',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 422);
        }

        try {
            $path = Storage::disk('s3')->putFile('products', $file);
            Log::info('S3 putFile result', ['path' => $path]);

            if (empty($path)) {
                Log::error('S3 returned empty path');
                return response()->json([
                    'message' => 'Ошибка загрузки: пустой путь',
                    'data' => null,
                    'timestamp' => now()->toISOString(),
                    'success' => false,
                ], 500);
            }

            $url = null;
            try {
                $url = Storage::disk('s3')->url($path);
            } catch (\Exception $e) {
                Log::warning('Storage::url failed, will attempt manual URL build: '.$e->getMessage());
            }

            if (empty($url)) {
                $s3Config = config('filesystems.disks.s3');
                $endpoint = $s3Config['endpoint'] ?? env('AWS_ENDPOINT');
                $bucket = $s3Config['bucket'] ?? env('AWS_BUCKET');
                if ($endpoint) {
                    // ensure no double slashes
                    $base = rtrim($endpoint, '/');
                    // If endpoint already contains host that serves buckets as path, build accordingly
                    $url = $base . '/' . ltrim($path, '/');
                } else {
                    Log::error('No endpoint configured to build S3 URL');
                    return response()->json([
                        'message' => 'Ошибка формирования URL',
                        'data' => null,
                        'timestamp' => now()->toISOString(),
                        'success' => false,
                    ], 500);
                }
            }
        } catch (\Exception $e) {
            Log::error('MinIO upload failed: '.$e->getMessage());
            return response()->json([
                'message' => 'MinIO недоступен',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 503);
        }

        return $this->ok('Изображение успешно загружено', ['file_url' => $url]);
    }

public function import(Request $request)
{
    $validator = Validator::make($request->all(), [
        'file' => 'required|mimes:csv,txt|max:5120',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Ошибка валидации',
            'data' => $validator->errors()->messages(),
            'timestamp' => now()->toISOString(),
            'success' => false,
        ], 422);
    }

    $file = $request->file('file');
    $rows = array_map('str_getcsv', file($file->getRealPath()));

    $header = array_map(fn($h) => mb_strtolower(trim($h)), $rows[0]);
    $dataRows = array_slice($rows, 1);

    $assocRows = [];
    foreach ($dataRows as $row) {
        $row = array_pad($row, count($header), null);
        $assoc = @array_combine($header, $row);
        if (!empty($assoc['name'])) {
            $assocRows[] = $assoc;
        }
    }

    $chunks = array_chunk($assocRows, 10);

    foreach ($chunks as $i => $chunk) {
        ImportProductsJob::dispatch($chunk, auth()->id())
            ->onConnection('rabbitmq')
            ->onQueue('imports');

        Log::info('Dispatched chunk', ['index' => $i, 'rows' => count($chunk)]);
    }

    return response()->json([
        'message' => 'Импорт запущен',
        'data' => [
            'total_rows' => count($assocRows),
            'chunks' => count($chunks),
            'chunk_size' => 10
        ],
        'timestamp' => now()->toISOString(),
        'success' => true,
    ]);
}

}
