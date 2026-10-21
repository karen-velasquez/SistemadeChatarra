<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 1.5cm; }
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: Helvetica, Arial, sans-serif; }
    h1 { font-size: 16pt; margin-bottom: 4px; }
    .subtitulo { font-size: 10pt; color: #555; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; font-size: 9pt; }
    th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
    th { background: #f0f0f0; }
    tr:nth-child(even) { background: #fafafa; }
    .col-n { width: 5%; text-align: center; }
    .col-fecha { width: 13%; }
</style>
</head>
<body>
    <h1>Impresión masiva de documentos</h1>
    <div class="subtitulo">
        Rango: {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
        &nbsp;·&nbsp; {{ count($documentos) }} documento(s)
        &nbsp;·&nbsp; Generado el {{ now()->format('d/m/Y H:i') }}
    </div>
    <table>
        <thead>
            <tr>
                <th class="col-n">N°</th>
                <th>Tipo de documento</th>
                <th>Referencia</th>
                <th class="col-fecha">Fecha</th>
                <th>Archivo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($documentos as $i => $doc)
            <tr>
                <td class="col-n">{{ $i + 1 }}</td>
                <td>{{ $doc['tipo'] }}</td>
                <td>{{ $doc['referencia'] }}</td>
                <td class="col-fecha">{{ $doc['fecha'] }}</td>
                <td>{{ basename($doc['archivo']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
