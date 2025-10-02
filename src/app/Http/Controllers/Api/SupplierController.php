<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
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

    $query = Supplier::where('created_by', auth()->id());

        if ($request->has('search')) {
            $q = $request->query('search');
            $query->where(function ($qf) use ($q) {
                $qf->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($q) . '%'])
                   ->orWhereRaw('LOWER(email) LIKE ?', ['%' . mb_strtolower($q) . '%']);
            });
        }

        $results = $query->paginate($perPage)->appends($request->query());

        return $this->ok('Suppliers list', $results);
    }

    public function show($id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json([
                'message' => 'Не найдено',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 404);
        }

    if ((string) $supplier->created_by !== (string) auth()->id()) {
            return response()->json([
                'message' => 'Forbidden',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 403);
        }

        return $this->ok('Supplier retrieved', $supplier);
    }

    public function store(StoreSupplierRequest $request)
    {
        $data = $request->only(['name','phone','contact_name','website','description','email','is_active']);
    $supplier = Supplier::create($data);

        return $this->ok('Supplier created', $supplier, 201);
    }

    public function update(UpdateSupplierRequest $request, $id)
    {
    $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json([
                'message' => 'Не найдено',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 404);
        }

        $data = $request->only(['name','phone','contact_name','website','description','email','is_active']);
    if ((string) $supplier->created_by !== (string) auth()->id()) {
            return response()->json([
                'message' => 'Forbidden',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 403);
        }

        $supplier->fill($data);
        $supplier->save();

        return $this->ok('Supplier updated', $supplier);
    }

    public function destroy($id)
    {
    $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json([
                'message' => 'Не найдено',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 404);
        }

    if ((string) $supplier->created_by !== (string) auth()->id()) {
            return response()->json([
                'message' => 'Forbidden',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 403);
        }

        $supplier->delete();

        return $this->ok('Supplier deleted', null);
    }
}
