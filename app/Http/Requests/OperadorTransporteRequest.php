<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class OperadorTransporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $operadorId = $this->route('operador')?->id;

        $rules = [
            'nombre'             => ['required', 'string', 'max:150', 'regex:/^[\pL\s]+$/u'],
            'apellido_paterno'   => ['required', 'string', 'max:100', 'regex:/^[\pL\s]+$/u'],
            'apellido_materno'   => ['required', 'string', 'max:100', 'regex:/^[\pL\s]+$/u'],
            'ci'                 => ['required', 'string', 'max:20',
                Rule::unique('operadores_transporte')->ignore($operadorId)->whereNull('deleted_at')
            ],
            'ci_pais_id'         => 'required|exists:parametros,id',
            'telefono'           => 'required|string|max:30',
            'email'              => ['nullable', 'email', 'max:150',
                Rule::unique('operadores_transporte')->ignore($operadorId)->whereNull('deleted_at')
            ],
            'direccion'          => 'nullable|string|max:255',
            'tipo_operador'      => 'required|in:propietario,chofer,ambos',
            'licencia_numero'    => 'nullable|string|max:30',
            'licencia_pais_id'   => 'nullable|exists:parametros,id',
            'licencia_vencimiento' => 'nullable|date',
            'estado'             => 'required|in:Activo,Inactivo',
            'doc_carnet'         => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
            'doc_licencia'       => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ];

        // Si es chofer o ambos, licencia es obligatoria
        if (in_array($this->input('tipo_operador'), ['chofer', 'ambos'])) {
            $rules['licencia_numero']    = 'required|string|max:30';
            $rules['licencia_pais_id']   = 'required|exists:parametros,id';
            $rules['licencia_vencimiento'] = 'required|date';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nombre.required'              => 'El nombre es obligatorio.',
            'nombre.regex'                 => 'El nombre solo puede contener letras y espacios.',
            'apellido_paterno.required'    => 'El apellido paterno es obligatorio.',
            'apellido_paterno.regex'       => 'El apellido paterno solo puede contener letras y espacios.',
            'apellido_materno.required'    => 'El apellido materno es obligatorio.',
            'apellido_materno.regex'       => 'El apellido materno solo puede contener letras y espacios.',
            'ci.required'                  => 'El número de documento es obligatorio.',
            'ci.unique'                    => 'Este número de documento ya está registrado.',
            'ci_pais_id.required'          => 'El país de expedición del documento es obligatorio.',
            'ci_pais_id.exists'            => 'El país seleccionado no es válido.',
            'telefono.required'            => 'El teléfono es obligatorio.',
            'tipo_operador.required'       => 'El tipo de operador es obligatorio.',
            'licencia_numero.required'     => 'El número de licencia es obligatorio para choferes.',
            'licencia_pais_id.required'    => 'El país de expedición de la licencia es obligatorio.',
            'licencia_pais_id.exists'      => 'El país de la licencia seleccionado no es válido.',
            'licencia_vencimiento.required'=> 'La fecha de vencimiento de licencia es obligatoria para choferes.',
            'doc_carnet.mimes'            => 'El carnet debe ser PDF, JPG o PNG.',
            'doc_carnet.max'              => 'El carnet no puede superar los 5 MB.',
            'doc_licencia.mimes'          => 'La licencia debe ser PDF, JPG o PNG.',
            'doc_licencia.max'            => 'La licencia no puede superar los 5 MB.',
        ];
    }
}
