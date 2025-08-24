<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'proof' => ['required', 'image', 'mimes:jpeg,png,jpg,heic'],
            'customer_bank_account' => 'required|string|max:255',
            'customer_bank_name' => 'required|string|max:255',
            'customer_bank_number' => 'required|string|max:255',

        ];
    }

}
