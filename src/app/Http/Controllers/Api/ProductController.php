<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        $perPage = (int) $request->query('per_page', 15);
        if ($perPage <= 0) $perPage = 15;
        $perPage = min($perPage, 100);

        $query = Product::query();

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

        $product->is_active = false;
        $product->save();

        return $this->ok('Soft-deleted', null);
    }

    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'data' => $validator->errors()->messages(),
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 422);
        }

        $file = $request->file('image');
        $path = Storage::disk('s3')->putFile('products', $file);
        $url = Storage::disk('s3')->url($path);

        return $this->ok('Изображение успешно загружено', ['file_url' => $url]);
    }
}
