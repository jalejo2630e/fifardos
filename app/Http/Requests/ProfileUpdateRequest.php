<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'avatar' => $this->hasFile('avatar')
                ? ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096', 'dimensions:max_width=6000,max_height=6000']
                // sin archivo: sólo texto corto (emoji/inicial); prohíbe URLs y rutas
                : ['nullable', 'string', 'max:16', 'not_regex:/[\/:<>]/'],
        ];
    }
}
