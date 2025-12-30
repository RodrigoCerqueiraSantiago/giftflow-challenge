<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedeemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Para este teste, qualquer um pode tentar redeem
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
            ],
            'user' => [
                'required',
                'array',
            ],
            'user.email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'O código do gift card é obrigatório.',
            'user.email.required' => 'O e-mail do usuário é obrigatório.',
            'user.email.email' => 'O e-mail informado não é válido.',
        ];
    }
}
