<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContratoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_contrato'     => 'required|in:Nacional,Internacional',
            'cliente_id'        => 'nullable|exists:clientes,id',
            'proveedor_id'      => 'required|exists:proveedors,id',
            'fecha_inicio'      => 'required|date',
            'fecha_fin'         => 'nullable|date|after_or_equal:fecha_inicio',
            'toneladas_contrato' => 'required|numeric|min:0.001',
            'costo_unitario'    => 'required|numeric|min:0.01',
            'monto_total'       => 'required|numeric|min:0',
            'moneda'            => 'required|in:BOB,USD,EUR,BRL,ARS,PEN,CLP,PYG,COP',
            'documento_pdf'     => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:30720',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_contrato.required'  => 'El tipo de contrato es obligatorio.',
            'cliente_id.required'     => 'Debe seleccionar un cliente.',
            'cliente_id.exists'       => 'El cliente seleccionado no existe.',
            'proveedor_id.required'   => 'Debe seleccionar un proveedor.',
            'proveedor_id.exists'     => 'El proveedor seleccionado no existe.',
            'fecha_inicio.required'   => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date'       => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.after_or_equal'=> 'La fecha fin debe ser igual o posterior a la fecha inicio.',
            'toneladas_contrato.required' => 'Las toneladas del contrato son obligatorias.',
            'toneladas_contrato.min'      => 'Las toneladas deben ser mayores a 0.',
            'monto_total.required'    => 'El monto total a pagar al proveedor es obligatorio.',
            'monto_total.numeric'     => 'El monto total debe ser un número.',
            'moneda.required'         => 'La moneda es obligatoria.',
            'documento_pdf.file'          => 'El documento debe ser un archivo.',
            'documento_pdf.mimes'         => 'Solo se permiten archivos PDF, PNG, JPG o JPEG.',
            'documento_pdf.max'           => 'El documento no puede superar los 30 MB.',
        ];
    }
}
