<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow any authenticated user to create patients by default.
        // Adjust this logic if you want role-based permissions.
    return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'document' => ['required','numeric','digits:10'],
            'email' => ['required','email','max:255'],
            'eps' => ['required','string','max:255'],
        ];
    }

    /**
     * Mensajes de validación en español.
     *
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no debe exceder los :max caracteres.',

            'document.required' => 'El documento es obligatorio.',
            'document.numeric' => 'El documento debe ser numérico.',
            'document.digits' => 'El documento debe tener :digits dígitos.',

            // teléfono eliminado del modelo

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.max' => 'El correo electrónico no debe exceder los :max caracteres.',

            'eps.required' => 'La EPS es obligatoria.',
            'eps.string' => 'La EPS debe ser texto.',
            'eps.max' => 'La EPS no debe exceder los :max caracteres.',
        ];
    }
}
