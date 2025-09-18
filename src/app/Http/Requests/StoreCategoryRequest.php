<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|uuid|exists:categories,id',
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
