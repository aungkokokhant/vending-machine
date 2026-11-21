<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->get('current_user')['role'] === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'regex:/\A\d{1,7}(?:\.\d{1,3})?\z/', 'gt:0', 'max:9999999.999'],
            'quantity_available' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ];
    }
}
