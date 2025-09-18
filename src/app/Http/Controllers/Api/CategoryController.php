<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
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

        $query = Category::query();

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->query('parent_id'));
        }

        if ($request->has('search')) {
            $q = $request->query('search');
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($q) . '%']);
        }

        $results = $query->paginate($perPage)->appends($request->query());

        return $this->ok('Categories list', $results);
    }

    public function show($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'message' => 'Не найдено',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 404);
        }

        return $this->ok('Category retrieved', $category);
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->only(['name', 'parent_id']);
        $category = Category::create($data);

        return $this->ok('Category created', $category, 201);
    }

    public function update(UpdateCategoryRequest $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'message' => 'Не найдено',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 404);
        }

        $data = $request->only(['name', 'parent_id']);
        $category->fill($data);
        $category->save();

        return $this->ok('Category updated', $category);
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'message' => 'Не найдено',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 404);
        }

        $category->delete();

        return $this->ok('Category deleted', null);
    }
}
