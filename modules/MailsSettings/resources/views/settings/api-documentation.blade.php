@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Documentación REST API - Incoming Email'])

    <div class="widget-content searchable-container list">

        <!-- Back Button -->
        <div class="mb-3">
            <a href="{{ route('settings.incoming-email.index') }}" class="btn btn-outline-primary">
                &larr; Volver a configuración de correo entrante
            </a>
        </div>

        <!-- Overview Card -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">
                    REST API para correo entrante
                </h4>
                <p class="text-muted mb-0">
                    Esta API permite enviar correos electrónicos entrantes a Alsernet desde sistemas externos como webhooks de servicios de correo,
                    scripts personalizados o integraciones de terceros. Todos los correos recibidos se procesarán automáticamente y se crearán
                    como tickets o respuestas según la configuración.
                </p>
            </div>
        </div>

        <!-- Authentication Section -->
        <div class="card mt-4">
            <div class="card-header bg-light-primary">
                <h5 class="mb-0">
                    Autenticación
                </h5>
            </div>
            <div class="card-body">
                <p class="mb-3">
                    Todas las solicitudes a la API deben incluir un encabezado de autenticación con la API Key configurada.
                </p>

                <div class="alert alert-warning border-0 bg-warning-subtle">
                    <div>
                        <strong>Importante:</strong> La API Key es sensible. Manténgala segura y no la comparta públicamente.
                        Puede generar una nueva clave en cualquier momento desde la configuración de correo entrante.
                    </div>
                </div>

                <h6 class="mt-4 mb-3">Encabezado requerido:</h6>
                <div class="bg-black text-white p-3 rounded">
                    <code class="text-white">X-API-Key: {your_api_key}</code>
                </div>
            </div>
        </div>

        <!-- Endpoint Section -->
        <div class="card mt-4">
            <div class="card-header bg-light-success">
                <h5 class="mb-0">
                    Endpoint
                </h5>
            </div>
            <div class="card-body">
                <h6 class="mb-3">URL del Endpoint:</h6>
                <div class="input-group mb-4">
                    <input type="text"
                           class="form-control bg-light"
                           id="apiEndpointUrl"
                           value="{{ url('/api/v1/incoming-email') }}"
                           readonly>
                    <button class="btn btn-outline-primary"
                            type="button"
                            onclick="copyToClipboard('apiEndpointUrl', this)">
                        Copiar
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td class="fw-bold" width="120">Método</td>
                                <td><span class="badge bg-success">POST</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Content-Type</td>
                                <td><code>application/json</code> o <code>multipart/form-data</code></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Autenticación</td>
                                <td>API Key mediante encabezado <code>X-API-Key</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Request Parameters Section -->
        <div class="card mt-4">
            <div class="card-header bg-light-info">
                <h5 class="mb-0">
                    Parámetros de solicitud
                </h5>
            </div>
            <div class="card-body">
                <p class="mb-3">
                    Los siguientes parámetros pueden enviarse en formato JSON o como form-data:
                </p>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Parámetro</th>
                                <th>Tipo</th>
                                <th>Requerido</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>from</code></td>
                                <td>string</td>
                                <td><span class="badge bg-danger">Sí</span></td>
                                <td>Dirección de correo del remitente</td>
                            </tr>
                            <tr>
                                <td><code>to</code></td>
                                <td>string</td>
                                <td><span class="badge bg-danger">Sí</span></td>
                                <td>Dirección(es) de correo del destinatario</td>
                            </tr>
                            <tr>
                                <td><code>subject</code></td>
                                <td>string</td>
                                <td><span class="badge bg-danger">Sí</span></td>
                                <td>Asunto del correo electrónico</td>
                            </tr>
                            <tr>
                                <td><code>body</code></td>
                                <td>string</td>
                                <td><span class="badge bg-danger">Sí</span></td>
                                <td>Cuerpo del mensaje (texto plano o HTML)</td>
                            </tr>
                            <tr>
                                <td><code>cc</code></td>
                                <td>string</td>
                                <td><span class="badge bg-secondary">No</span></td>
                                <td>Direcciones en copia (separadas por coma)</td>
                            </tr>
                            <tr>
                                <td><code>bcc</code></td>
                                <td>string</td>
                                <td><span class="badge bg-secondary">No</span></td>
                                <td>Direcciones en copia oculta (separadas por coma)</td>
                            </tr>
                            <tr>
                                <td><code>reply_to</code></td>
                                <td>string</td>
                                <td><span class="badge bg-secondary">No</span></td>
                                <td>Dirección de respuesta</td>
                            </tr>
                            <tr>
                                <td><code>html_body</code></td>
                                <td>string</td>
                                <td><span class="badge bg-secondary">No</span></td>
                                <td>Cuerpo del mensaje en formato HTML</td>
                            </tr>
                            <tr>
                                <td><code>attachments[]</code></td>
                                <td>file</td>
                                <td><span class="badge bg-secondary">No</span></td>
                                <td>Archivos adjuntos (solo con multipart/form-data)</td>
                            </tr>
                            <tr>
                                <td><code>headers</code></td>
                                <td>object</td>
                                <td><span class="badge bg-secondary">No</span></td>
                                <td>Encabezados adicionales del correo (JSON)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- JSON Example Section -->
        <div class="card mt-4">
            <div class="card-header bg-light-warning">
                <h5 class="mb-0">
                    Ejemplo de solicitud (JSON)
                </h5>
            </div>
            <div class="card-body">
                <h6 class="mb-3">cURL:</h6>
                <div class="position-relative">
                    <pre class="bg-black text-white p-3 rounded" id="curlExample"><code>curl -X POST {{ url('/api/v1/incoming-email') }} \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your_api_key_here" \
  -d '{
    "from": "cliente@example.com",
    "to": "soporte@Alsernet.com",
    "subject": "Solicitud de ayuda con pedido #12345",
    "body": "Hola, necesito ayuda con mi pedido...",
    "html_body": "<p>Hola,</p><p>Necesito ayuda con mi pedido...</p>",
    "cc": "gerencia@Alsernet.com",
    "reply_to": "cliente@example.com",
    "headers": {
      "X-Priority": "1",
      "X-Customer-ID": "12345"
    }
  }'</code></pre>
                    <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2"
                            onclick="copyToClipboard('curlExample', this)">
                        Copiar
                    </button>
                </div>

                <h6 class="mt-4 mb-3">JavaScript (Axios):</h6>
                <div class="position-relative">
                    <pre class="bg-black text-white p-3 rounded" id="jsExample"><code>const axios = require('axios');

const response = await axios.post('{{ url('/api/v1/incoming-email') }}', {
  from: 'cliente@example.com',
  to: 'soporte@Alsernet.com',
  subject: 'Solicitud de ayuda con pedido #12345',
  body: 'Hola, necesito ayuda con mi pedido...',
  html_body: '<p>Hola,</p><p>Necesito ayuda con mi pedido...</p>',
  cc: 'gerencia@Alsernet.com',
  reply_to: 'cliente@example.com',
  headers: {
    'X-Priority': '1',
    'X-Customer-ID': '12345'
  }
}, {
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': 'your_api_key_here'
  }
});

console.log(response.data);</code></pre>
                    <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2"
                            onclick="copyToClipboard('jsExample', this)">
                        Copiar
                    </button>
                </div>

                <h6 class="mt-4 mb-3">PHP (Guzzle):</h6>
                <div class="position-relative">
                    <pre class="bg-black text-white p-3 rounded" id="phpExample"><code>use GuzzleHttp\Client;

$client = new Client();

$response = $client->post('{{ url('/api/v1/incoming-email') }}', [
    'headers' => [
        'Content-Type' => 'application/json',
        'X-API-Key' => 'your_api_key_here',
    ],
    'json' => [
        'from' => 'cliente@example.com',
        'to' => 'soporte@Alsernet.com',
        'subject' => 'Solicitud de ayuda con pedido #12345',
        'body' => 'Hola, necesito ayuda con mi pedido...',
        'html_body' => '<p>Hola,</p><p>Necesito ayuda con mi pedido...</p>',
        'cc' => 'gerencia@Alsernet.com',
        'reply_to' => 'cliente@example.com',
        'headers' => [
            'X-Priority' => '1',
            'X-Customer-ID' => '12345',
        ],
    ],
]);

$data = json_decode($response->getBody(), true);</code></pre>
                    <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2"
                            onclick="copyToClipboard('phpExample', this)">
                        Copiar
                    </button>
                </div>
            </div>
        </div>

        <!-- Response Section -->
        <div class="card mt-4">
            <div class="card-header bg-light-secondary">
                <h5 class="mb-0">
                    Respuestas de la API
                </h5>
            </div>
            <div class="card-body">
                <h6 class="mb-3">Respuesta exitosa (200):</h6>
                <pre class="bg-black text-white p-3 rounded"><code>{
  "success": true,
  "message": "Email procesado correctamente",
  "ticket_id": 12345,
  "ticket_number": "TKT-2024-00123"
}</code></pre>

                <h6 class="mt-4 mb-3">Error de autenticación (401):</h6>
                <pre class="bg-black text-white p-3 rounded"><code>{
  "success": false,
  "message": "API Key inválida o no proporcionada",
  "error_code": "INVALID_API_KEY"
}</code></pre>

                <h6 class="mt-4 mb-3">Error de validación (422):</h6>
                <pre class="bg-black text-white p-3 rounded"><code>{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "from": ["El campo from es requerido"],
    "subject": ["El campo subject es requerido"]
  }
}</code></pre>

                <h6 class="mt-4 mb-3">Error del servidor (500):</h6>
                <pre class="bg-black text-white p-3 rounded"><code>{
  "success": false,
  "message": "Error interno del servidor",
  "error_code": "SERVER_ERROR"
}</code></pre>
            </div>
        </div>

        <!-- Status Codes Section -->
        <div class="card mt-4">
            <div class="card-header bg-light-danger">
                <h5 class="mb-0">
                    Códigos de estado HTTP
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="100">Código</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-success">200</span></td>
                                <td>Solicitud procesada exitosamente</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning">401</span></td>
                                <td>API Key inválida o no proporcionada</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning">422</span></td>
                                <td>Error de validación - parámetros faltantes o inválidos</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger">429</span></td>
                                <td>Demasiadas solicitudes - límite de tasa excedido</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger">500</span></td>
                                <td>Error interno del servidor</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Best Practices Section -->
        <div class="card mt-4">
            <div class="card-header" style="background-color: #90bb13;">
                <h5 class="mb-0 text-white">
                    Mejores prácticas
                </h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li class="mb-2">
                        <strong>Seguridad:</strong> Nunca exponga su API Key en el código frontend. Úsela solo en el backend.
                    </li>
                    <li class="mb-2">
                        <strong>Rate Limiting:</strong> La API tiene límites de tasa. Implemente reintentos exponenciales en caso de errores 429.
                    </li>
                    <li class="mb-2">
                        <strong>Validación:</strong> Valide todos los campos antes de enviarlos para evitar errores 422.
                    </li>
                    <li class="mb-2">
                        <strong>Logging:</strong> Registre todas las solicitudes y respuestas para depuración.
                    </li>
                    <li class="mb-2">
                        <strong>Timeout:</strong> Configure un timeout apropiado (recomendado: 30 segundos).
                    </li>
                    <li class="mb-0">
                        <strong>HTTPS:</strong> Siempre use HTTPS en producción para proteger la API Key en tránsito.
                    </li>
                </ul>
            </div>
        </div>

    </div>

@push('scripts')
<script>
function copyToClipboard(elementId, button) {
    const element = document.getElementById(elementId);
    let textToCopy = element.value || element.textContent;

    // Clean up the text if it's from a code block
    if (!element.value) {
        textToCopy = textToCopy.trim();
    }

    navigator.clipboard.writeText(textToCopy).then(() => {
        const originalHTML = button.innerHTML;
        button.innerHTML = '✓ Copiado';
        button.classList.remove('btn-outline-primary', 'btn-outline-light');
        button.classList.add('btn-success');

        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.classList.remove('btn-success');
            button.classList.add(elementId === 'apiEndpointUrl' ? 'btn-outline-primary' : 'btn-outline-light');
        }, 2000);
    }).catch(err => {
        console.error('Error al copiar:', err);
        alert('Error al copiar al portapapeles');
    });
}
</script>
@endpush

@endsection
