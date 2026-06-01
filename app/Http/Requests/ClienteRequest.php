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
    public function rules(): array
    {
        return [
            'nombre'=>'required',
            'pais_id'=>'required|exists:parametros,id',
            'nit' => [
                'required',
                'numeric',
                Rule::unique('clientes', 'nit')->ignore(optional($this->route('cliente'))->id)->whereNull('deleted_at')
            ]
        ];
    }
}
