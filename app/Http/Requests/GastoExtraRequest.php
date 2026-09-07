<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class GastoExtraRequest extends FormRequest
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
            'contrato_id' => 'nullable|exists:contratos,id',
            'cuenta_empresa_id' => 'required|exists:cuentas_empresa,id',
            'categoria' => 'required|string|max:50',
            'concepto' => 'required|string',
            'fecha' => 'required|date',
            'monto' => 'required|',
            'moneda' => 'required|string|max:10',
            'tipo_cambio' => 'nullable',
            'comprobante_pago' => 'nullable',
            'metodo_pago' => 'nullable|in:TRANSFERENCIA,QR',
            'codigo_seguimiento' => 'required_if:metodo_pago,TRANSFERENCIA|nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo_seguimiento.required_if' => 'El código de la transferencia es obligatorio.',
        ];
    }
}
