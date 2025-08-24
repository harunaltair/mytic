<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreCheckBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'booking_trx_id' => ['required|string|max:255'],
            'phone' => ['required|email|max:255'],
        ];
    }

}
