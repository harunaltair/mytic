<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'quantity' => 'required|integer|min:1',
            'participant.*.name' => 'required|string|max:255',
            'participant.*.occupation' => 'required|string|max:255',
            'participant.*.email' => 'required|email|max:255',
        ];
    }

}
