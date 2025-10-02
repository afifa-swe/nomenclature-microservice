<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => [
                'required',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->where('created_by', auth()->id());
                }),
            ],
            'supplier_id' => [
                'required',
                'uuid',
                Rule::exists('suppliers', 'id')->where(function ($query) {
                    $query->where('created_by', auth()->id());
                }),
            ],
            'price' => 'required|numeric|min:0',
            'file_url' => 'nullable|url',
            'is_active' => 'sometimes|boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $payload = [
            'message' => 'Ошибка валидации',
            'data' => $errors->messages(),
            'timestamp' => now()->toISOString(),
            'success' => false,
        ];

        throw new HttpResponseException(response()->json($payload, 422));
    }
}
