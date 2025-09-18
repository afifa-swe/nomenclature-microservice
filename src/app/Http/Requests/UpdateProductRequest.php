<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'category_id' => 'sometimes|uuid|exists:categories,id',
            'supplier_id' => 'sometimes|uuid|exists:suppliers,id',
            'price' => 'sometimes|numeric|min:0',
            'file_url' => 'sometimes|nullable|url',
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
