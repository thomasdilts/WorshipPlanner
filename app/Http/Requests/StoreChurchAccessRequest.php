<?php

namespace App\Http\Requests;

use App\Enums\ExpectedUserRange;
use App\Support\CountryOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChurchAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'church_name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:500'],
            'country' => ['required', 'string', Rule::in(array_keys(CountryOptions::all()))],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
            'expected_users' => ['required', Rule::enum(ExpectedUserRange::class)],
            'special_requests' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'church_name' => __('Church name'),
            'address' => __('Address'),
            'country' => __('Country'),
            'phone' => __('Telephone'),
            'email' => __('Email'),
            'expected_users' => __('Expected users'),
            'special_requests' => __('Special requests'),
        ];
    }
}
