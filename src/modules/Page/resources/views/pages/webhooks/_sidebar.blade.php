<div class="card">
    <div class="card-header p-3 bg-white border-bottom">
        <h5 class="mb-0 fw-bold">Cómo funcionan</h5>
    </div>
    <div class="card-body small text-muted">
        <p class="mb-2">Cuando ocurre un evento, se envía un <strong>HTTP POST</strong> a la URL configurada con el siguiente payload:</p>
        <pre class="bg-light rounded p-2 mb-3" style="font-size:0.75rem">{
  "event": "published",
  "timestamp": "2026-03-26T...",
  "data": {
    "id": 1,
    "title": "Mi página",
    "slug": "mi-pagina",
    "status": "published",
    "url": "https://...",
    "published_at": "...",
    "updated_at": "..."
  }
}</pre>
        <p class="mb-2"><strong>Eventos disponibles:</strong></p>
        <ul class="ps-3 mb-3">
            <li><code>published</code> — página publicada</li>
            <li><code>updated</code> — página actualizada</li>
            <li><code>deleted</code> — página eliminada</li>
        </ul>
        <p class="mb-2"><strong>Firma HMAC:</strong></p>
        <p class="mb-0">Si se configura un secret, el header <code>X-Webhook-Signature</code> contendrá <code>sha256=&lt;hmac&gt;</code> para verificar la autenticidad.</p>
    </div>
</div>
