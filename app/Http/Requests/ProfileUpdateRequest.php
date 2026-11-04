<?php

namespace App\Http\Requests;

use App\Models\Referee;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        $rules = [
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ];

        // Il nome di un arbitro è gestito dal designatore
        if (! $user->isReferee()) {
            $rules['name'] = ['required', 'string', 'max:255'];
        }

        if ($user->referee_id) {
            $rules['email'][] = Rule::unique(Referee::class)->ignore($user->referee_id);
            $rules['phone'] = ['nullable', 'string', 'max:20'];
            $rules['city'] = ['nullable', 'string', 'max:100'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Inserisci il nome.',
            'email.required' => 'Inserisci l\'indirizzo email.',
            'email.email' => 'Inserisci un indirizzo email valido.',
            'email.unique' => 'Questo indirizzo email è già usato da un altro account.',
            'phone.max' => 'Il telefono può contenere al massimo :max caratteri.',
            'city.max' => 'La città può contenere al massimo :max caratteri.',
        ];
    }
}
