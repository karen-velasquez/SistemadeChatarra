<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use RealRashid\SweetAlert\Facades\Alert;

class UserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        if ($this->method() === 'PUT') {
            $rules = [
                'name' => 'required',
                'role_id' => 'required',
                'email' => ['required', Rule::unique('users')->ignore($this->route('user'))],
                'empleado_id' => 'nullable|exists:empleados,id', // Opcional en edición pero debe existir si se envía
            ];
        } else {
            $rules = [
                'name' => 'required',
                'empleado_id' => 'required|exists:empleados,id',
                'password' => ['required', 'confirmed', 'min:8'],
                'role_id' => 'required',
                'email' => ['required', Rule::unique('users')],
            ];
        }
        return $rules;
    }
    public function messages()
    {
        return [
            'name.required' => 'El campo nombre es obligatorio.',
            'empleado_id.required' => 'Debe seleccionar un empleado.',
            'empleado_id.exists' => 'El empleado seleccionado no existe.',
            'email.required' => 'El campo correo electrónico es obligatorio.',
            'email.unique' => 'El correo ingresado ya fue utilizado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden. Verifique que ambos campos sean idénticos.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'role_id.required' => 'Debe asignar un rol al usuario.',
        ];
    }
   protected function failedValidation(Validator $validator)
    {
        Alert::error('Error', $validator->errors()->first());
        throw new HttpResponseException(redirect()->back()->withErrors($validator)->withInput()->with('active_tab', 'change_password'));
    }
}