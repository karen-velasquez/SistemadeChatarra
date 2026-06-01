<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ProveedorRequest extends FormRequest
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

            'nombre'=>'required',
            'pais_id'=>'required|exists:parametros,id',
            'nit'=>[
                'required',
                'numeric',
                Rule::unique('proveedors','nit')->ignore(optional($this->route('proveedor'))->id)->whereNull('deleted_at')
            ]
        ];
    }
    
}
