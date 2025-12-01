<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'id_specialty' => ['required','exists:specialties,id_specialty'],
            'id_user' => ['nullable'],
            'email' => ['nullable','email','max:255'],
            'status' => ['sometimes','in:Activo,Inactivo,active,inactive'],
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

            'id_specialty.required' => 'La especialidad es obligatoria.',
            'id_specialty.exists' => 'La especialidad seleccionada no es válida.',

            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.max' => 'El correo electrónico no debe exceder los :max caracteres.',

            'status.in' => 'El estado seleccionado no es válido.',
        ];
    }
}
