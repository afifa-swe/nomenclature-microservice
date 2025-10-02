<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChangeLogController extends Controller
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

        $query = DB::table('change_logs');

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->query('entity_type'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        } else {
            // по умолчанию показываем только для текущего пользователя
            $query->where('user_id', auth()->id());
        }

        $sort = $request->query('sort', 'desc');
        $sort = strtolower($sort) === 'asc' ? 'asc' : 'desc';

        $results = $query->orderBy('created_at', $sort)
            ->paginate($perPage)
            ->appends($request->query());

        return $this->ok('Change logs list', $results);
    }
}
