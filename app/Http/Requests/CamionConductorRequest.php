<?php

namespace App\Http\Requests;

use App\Models\CamionConductor;
use App\Models\OperadorTransporte;
use Illuminate\Foundation\Http\FormRequest;

class CamionConductorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'camion_id'     => 'required|exists:camiones,id',
            'conductor_id'  => 'required|exists:operadores_transporte,id',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $conductorId = $this->input('conductor_id');
            $camionId    = $this->input('camion_id');

            if (!$conductorId) return;

            // Validar que el operador sea de tipo chofer/ambos (la falta de licencia ya no bloquea, solo se avisa en el frontend)
            $operador = OperadorTransporte::find($conductorId);
            if ($operador && !in_array($operador->tipo_operador, ['chofer', 'ambos'])) {
                $validator->errors()->add('conductor_id', 'Este operador no está registrado como chofer y no puede ser asignado como conductor.');
            }

            // Validar que no haya asignación activa duplicada
            if ($camionId) {
                $asignacionActiva = CamionConductor::where('camion_id', $camionId)
                    ->where('conductor_id', $conductorId)
                    ->whereNull('fecha_fin')
                    ->exists();

                if ($asignacionActiva) {
                    $validator->errors()->add('conductor_id', 'Este conductor ya tiene una asignación activa en este camión.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'camion_id.required'    => 'Debe seleccionar un camión.',
            'camion_id.exists'      => 'El camión seleccionado no existe.',
            'conductor_id.required' => 'Debe seleccionar un conductor.',
            'conductor_id.exists'   => 'El conductor seleccionado no existe.',
        ];
    }
}
