<?php

namespace App\Http\Requests;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function messages(): array
    {
        return [
            'nit.unique'             => 'Ya existe un cliente registrado con este NIT / CI / RUC.',
            'direcciones.required'   => 'Debe registrar al menos una dirección de entrega.',
            'direcciones.min'        => 'Debe registrar al menos una dirección de entrega.',
            'direcciones.*.required' => 'La dirección no puede estar vacía.',
            'direcciones.*.min'      => 'La dirección es demasiado corta (mínimo 5 caracteres).',
        ];
    }

    public function rules(): array
    {
        return [
            'nombre'         => 'required',
            'pais_id'        => 'required|exists:parametros,id',
            'nit'            => [
                'required',
                'numeric',
                Rule::unique('clientes', 'nit')->ignore(optional($this->route('cliente'))->id)->whereNull('deleted_at')
            ],
            'direcciones'    => ['required', 'array', 'min:1'],
            'direcciones.*'  => ['required', 'string', 'min:5'],
        ];
    }
}
