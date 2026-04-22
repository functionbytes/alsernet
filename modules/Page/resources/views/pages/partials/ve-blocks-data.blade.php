<script>
window.veBlocks = [
    // ── Secciones ────────────────────────────────────────────────────────
    {
        name: 'Hero principal',
        category: 'sections',
        icon: '<i class="fa-solid fa-image fa-lg"></i>',
        html: `<section class="py-5 bg-primary text-white">
  <div class="container text-center">
    <h1 class="display-4 fw-bold">Título principal</h1>
    <p class="lead mb-4">Descripción de tu propuesta de valor aquí. Explica brevemente qué ofreces.</p>
    <a href="#" class="btn btn-light btn-lg me-2">Llamada a la acción</a>
    <a href="#" class="btn btn-outline-light btn-lg">Saber más</a>
  </div>
</section>`,
    },
    {
        name: 'Hero con imagen',
        category: 'sections',
        icon: '<i class="fa-solid fa-panorama fa-lg"></i>',
        html: `<section class="py-5" style="background: #90bb13; color: white;">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6">
        <h1 class="display-5 fw-bold">Tu Título</h1>
        <p class="lead">Descripción persuasiva de tu servicio o producto.</p>
        <a href="#" class="btn btn-light btn-lg">Comenzar ahora</a>
      </div>
      <div class="col-md-6 text-center">
        <img src="https://via.placeholder.com/500x350" alt="Hero image" class="img-fluid rounded shadow">
      </div>
    </div>
  </div>
</section>`,
    },
    {
        name: 'Sección de texto',
        category: 'sections',
        icon: '<i class="fa-solid fa-align-left fa-lg"></i>',
        html: `<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <h2 class="fw-bold mb-4">Título de sección</h2>
        <p class="lead text-muted mb-3">Párrafo de introducción con texto de muestra. Describe tu servicio, producto o información relevante de manera clara.</p>
        <p>Texto adicional del contenido. Puedes agregar toda la información que necesites aquí.</p>
      </div>
    </div>
  </div>
</section>`,
    },
    {
        name: '2 Columnas',
        category: 'sections',
        icon: '<i class="fa-solid fa-columns fa-lg"></i>',
        html: `<section class="py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-6">
        <h3>Columna izquierda</h3>
        <p>Contenido de la columna izquierda. Puedes agregar texto, imágenes o cualquier elemento HTML aquí.</p>
      </div>
      <div class="col-md-6">
        <h3>Columna derecha</h3>
        <p>Contenido de la columna derecha. Puedes agregar texto, imágenes o cualquier elemento HTML aquí.</p>
      </div>
    </div>
  </div>
</section>`,
    },
    {
        name: '3 Columnas',
        category: 'sections',
        icon: '<i class="fa-solid fa-table-columns fa-lg"></i>',
        html: `<section class="py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4"><h4>Columna 1</h4><p>Texto de la primera columna.</p></div>
      <div class="col-md-4"><h4>Columna 2</h4><p>Texto de la segunda columna.</p></div>
      <div class="col-md-4"><h4>Columna 3</h4><p>Texto de la tercera columna.</p></div>
    </div>
  </div>
</section>`,
    },
    {
        name: 'CTA',
        category: 'sections',
        icon: '<i class="fa-solid fa-bullhorn fa-lg"></i>',
        html: `<section class="py-5 bg-light">
  <div class="container text-center">
    <h2 class="fw-bold mb-3">¿Listo para empezar?</h2>
    <p class="lead text-muted mb-4">Únete a miles de clientes satisfechos.</p>
    <a href="#" class="btn btn-primary btn-lg px-5">Contactar ahora</a>
  </div>
</section>`,
    },
    {
        name: 'Separador',
        category: 'sections',
        icon: '<i class="fa-solid fa-minus fa-lg"></i>',
        html: `<hr class="my-5">`,
    },

    // ── Cards ─────────────────────────────────────────────────────────────
    {
        name: 'Card simple',
        category: 'cards',
        icon: '<i class="fa-solid fa-square fa-lg"></i>',
        html: `<div class="card shadow-sm">
  <div class="card-body">
    <h5 class="card-title">Título de la card</h5>
    <p class="card-text text-muted">Descripción o contenido de la card. Puedes incluir texto, listas o cualquier contenido.</p>
    <a href="#" class="btn btn-primary">Saber más</a>
  </div>
</div>`,
    },
    {
        name: 'Card con imagen',
        category: 'cards',
        icon: '<i class="fa-solid fa-image fa-lg"></i>',
        html: `<div class="card shadow-sm">
  <img src="https://via.placeholder.com/400x200" class="card-img-top" alt="Card image">
  <div class="card-body">
    <h5 class="card-title">Título de la card</h5>
    <p class="card-text text-muted">Descripción del contenido. Agrega información relevante aquí.</p>
    <a href="#" class="btn btn-primary">Ver más</a>
  </div>
</div>`,
    },
    {
        name: 'Grid 3 cards',
        category: 'cards',
        icon: '<i class="fa-solid fa-grip fa-lg"></i>',
        html: `<section class="py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Servicio 1</h5>
            <p class="card-text text-muted">Descripción del primer servicio o característica.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Servicio 2</h5>
            <p class="card-text text-muted">Descripción del segundo servicio o característica.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Servicio 3</h5>
            <p class="card-text text-muted">Descripción del tercer servicio o característica.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>`,
    },
    {
        name: 'Grid 4 cards',
        category: 'cards',
        icon: '<i class="fa-solid fa-th fa-lg"></i>',
        html: `<section class="py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-3"><div class="card h-100 shadow-sm"><div class="card-body"><h6 class="card-title">Item 1</h6><p class="card-text text-muted">Descripción breve.</p></div></div></div>
      <div class="col-md-3"><div class="card h-100 shadow-sm"><div class="card-body"><h6 class="card-title">Item 2</h6><p class="card-text text-muted">Descripción breve.</p></div></div></div>
      <div class="col-md-3"><div class="card h-100 shadow-sm"><div class="card-body"><h6 class="card-title">Item 3</h6><p class="card-text text-muted">Descripción breve.</p></div></div></div>
      <div class="col-md-3"><div class="card h-100 shadow-sm"><div class="card-body"><h6 class="card-title">Item 4</h6><p class="card-text text-muted">Descripción breve.</p></div></div></div>
    </div>
  </div>
</section>`,
    },

    // ── Contenido ─────────────────────────────────────────────────────────
    {
        name: 'Features con iconos',
        category: 'content',
        icon: '<i class="fa-solid fa-star fa-lg"></i>',
        html: `<section class="py-5">
  <div class="container">
    <h2 class="text-center fw-bold mb-5">Nuestras características</h2>
    <div class="row g-4">
      <div class="col-md-4 text-center">
        <div class="mb-3"><i class="fa-solid fa-star fa-3x text-warning"></i></div>
        <h4>Característica 1</h4>
        <p class="text-muted">Descripción de la primera característica de tu producto o servicio.</p>
      </div>
      <div class="col-md-4 text-center">
        <div class="mb-3"><i class="fa-solid fa-shield-alt fa-3x text-success"></i></div>
        <h4>Característica 2</h4>
        <p class="text-muted">Descripción de la segunda característica importante.</p>
      </div>
      <div class="col-md-4 text-center">
        <div class="mb-3"><i class="fa-solid fa-rocket fa-3x text-primary"></i></div>
        <h4>Característica 3</h4>
        <p class="text-muted">Descripción de la tercera característica destacada.</p>
      </div>
    </div>
  </div>
</section>`,
    },
    {
        name: 'FAQ / Acordeón',
        category: 'content',
        icon: '<i class="fa-solid fa-circle-question fa-lg"></i>',
        html: `<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <h2 class="fw-bold text-center mb-5">Preguntas frecuentes</h2>
        <div class="accordion" id="faqAccordion">
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                ¿Primera pregunta frecuente?
              </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
              <div class="accordion-body">Respuesta a la primera pregunta frecuente.</div>
            </div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                ¿Segunda pregunta frecuente?
              </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">Respuesta a la segunda pregunta frecuente.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>`,
    },
    {
        name: 'Timeline',
        category: 'content',
        icon: '<i class="fa-solid fa-timeline fa-lg"></i>',
        html: `<section class="py-5">
  <div class="container">
    <h2 class="fw-bold text-center mb-5">Nuestra historia</h2>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="d-flex mb-4">
          <div class="me-3 text-center" style="min-width: 60px;">
            <span class="badge bg-primary rounded-pill px-3 py-2">2020</span>
            <div class="border-start border-2 border-primary ms-3" style="height: 40px;"></div>
          </div>
          <div><h5>Hito 1</h5><p class="text-muted">Descripción del primer hito o evento importante.</p></div>
        </div>
        <div class="d-flex mb-4">
          <div class="me-3 text-center" style="min-width: 60px;">
            <span class="badge bg-success rounded-pill px-3 py-2">2022</span>
            <div class="border-start border-2 border-success ms-3" style="height: 40px;"></div>
          </div>
          <div><h5>Hito 2</h5><p class="text-muted">Descripción del segundo hito o evento importante.</p></div>
        </div>
        <div class="d-flex">
          <div class="me-3 text-center" style="min-width: 60px;">
            <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Hoy</span>
          </div>
          <div><h5>Actualidad</h5><p class="text-muted">Descripción de la situación actual.</p></div>
        </div>
      </div>
    </div>
  </div>
</section>`,
    },
    {
        name: 'Galería de imágenes',
        category: 'content',
        icon: '<i class="fa-solid fa-images fa-lg"></i>',
        html: `<section class="py-5">
  <div class="container">
    <h2 class="text-center fw-bold mb-5">Galería</h2>
    <div class="row g-3">
      <div class="col-md-4"><img src="https://via.placeholder.com/400x300" class="img-fluid rounded" alt="Imagen 1"></div>
      <div class="col-md-4"><img src="https://via.placeholder.com/400x300" class="img-fluid rounded" alt="Imagen 2"></div>
      <div class="col-md-4"><img src="https://via.placeholder.com/400x300" class="img-fluid rounded" alt="Imagen 3"></div>
      <div class="col-md-4"><img src="https://via.placeholder.com/400x300" class="img-fluid rounded" alt="Imagen 4"></div>
      <div class="col-md-4"><img src="https://via.placeholder.com/400x300" class="img-fluid rounded" alt="Imagen 5"></div>
      <div class="col-md-4"><img src="https://via.placeholder.com/400x300" class="img-fluid rounded" alt="Imagen 6"></div>
    </div>
  </div>
</section>`,
    },
    {
        name: 'Video embed',
        category: 'content',
        icon: '<i class="fa-solid fa-play-circle fa-lg"></i>',
        html: `<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <h2 class="fw-bold text-center mb-4">Video</h2>
        <div class="ratio ratio-16x9 rounded shadow overflow-hidden">
          <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"
                  title="Video" allowfullscreen></iframe>
        </div>
        <p class="text-center text-muted mt-3">Descripción o título del video.</p>
      </div>
    </div>
  </div>
</section>`,
    },
    {
        name: 'Imagen con pie de foto',
        category: 'content',
        icon: '<i class="fa-solid fa-camera fa-lg"></i>',
        html: `<figure class="text-center my-4">
  <img src="https://via.placeholder.com/800x400" class="img-fluid rounded shadow" alt="Descripción de la imagen">
  <figcaption class="text-muted mt-2"><small>Pie de foto o descripción de la imagen</small></figcaption>
</figure>`,
    },
    {
        name: 'Tabla simple',
        category: 'content',
        icon: '<i class="fa-solid fa-table fa-lg"></i>',
        html: `<div class="table-responsive my-4">
  <table class="table table-bordered table-hover">
    <thead class="table-light">
      <tr>
        <th>Columna 1</th>
        <th>Columna 2</th>
        <th>Columna 3</th>
      </tr>
    </thead>
    <tbody>
      <tr><td>Dato 1</td><td>Dato 2</td><td>Dato 3</td></tr>
      <tr><td>Dato 4</td><td>Dato 5</td><td>Dato 6</td></tr>
      <tr><td>Dato 7</td><td>Dato 8</td><td>Dato 9</td></tr>
    </tbody>
  </table>
</div>`,
    },
    {
        name: 'Tabla de precios',
        category: 'content',
        icon: '<i class="fa-solid fa-tags fa-lg"></i>',
        html: `<section class="py-5">
  <div class="container">
    <h2 class="text-center fw-bold mb-5">Planes y precios</h2>
    <div class="row g-4 justify-content-center">
      <div class="col-md-4">
        <div class="card text-center shadow-sm">
          <div class="card-header py-3"><h5 class="mb-0">Básico</h5></div>
          <div class="card-body">
            <h3 class="display-6 fw-bold">€29<small class="fs-6 fw-normal text-muted">/mes</small></h3>
            <ul class="list-unstyled mt-3">
              <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Característica 1</li>
              <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Característica 2</li>
              <li class="mb-2"><i class="fa-solid fa-times text-danger me-2"></i>Característica 3</li>
            </ul>
            <a href="#" class="btn btn-outline-primary w-100 mt-3">Elegir plan</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-center shadow border-primary">
          <div class="card-header bg-primary text-white py-3"><h5 class="mb-0">Pro</h5></div>
          <div class="card-body">
            <h3 class="display-6 fw-bold">€79<small class="fs-6 fw-normal text-muted">/mes</small></h3>
            <ul class="list-unstyled mt-3">
              <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Característica 1</li>
              <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Característica 2</li>
              <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Característica 3</li>
            </ul>
            <a href="#" class="btn btn-primary w-100 mt-3">Elegir plan</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>`,
    },
    {
        name: 'Título H2',
        category: 'content',
        icon: '<span style="font-weight:700;font-size:13px;">H2</span>',
        html: `<h2 class="fw-bold mb-3">Título de sección</h2>`,
    },
    {
        name: 'Título H3',
        category: 'content',
        icon: '<span style="font-weight:700;font-size:13px;">H3</span>',
        html: `<h3 class="fw-semibold mb-3">Subtítulo de sección</h3>`,
    },
    {
        name: 'Párrafo',
        category: 'content',
        icon: '<i class="fa-solid fa-paragraph fa-lg"></i>',
        html: `<p class="mb-3">Escribe tu párrafo aquí. Puedes agregar todo el texto que necesites para describir tu contenido.</p>`,
    },
    {
        name: 'Cita / Blockquote',
        category: 'content',
        icon: '<i class="fa-solid fa-quote-left fa-lg"></i>',
        html: `<blockquote class="blockquote border-start border-4 border-primary ps-4 my-4">
  <p class="mb-1 fst-italic">"Texto de la cita o testimonio que quieres destacar en tu página."</p>
  <footer class="blockquote-footer mt-1">Nombre del autor</footer>
</blockquote>`,
    },
    {
        name: 'Alerta info',
        category: 'content',
        icon: '<i class="fa-solid fa-info-circle fa-lg text-info"></i>',
        html: `<div class="alert alert-info" role="alert">
  <i class="fa-solid fa-info-circle me-2"></i> Mensaje informativo importante para tus visitantes.
</div>`,
    },
    {
        name: 'Alerta éxito',
        category: 'content',
        icon: '<i class="fa-solid fa-check-circle fa-lg text-success"></i>',
        html: `<div class="alert alert-success" role="alert">
  <i class="fa-solid fa-check-circle me-2"></i> Operación completada con éxito.
</div>`,
    },
    {
        name: 'Alerta advertencia',
        category: 'content',
        icon: '<i class="fa-solid fa-triangle-exclamation fa-lg text-warning"></i>',
        html: `<div class="alert alert-warning" role="alert">
  <i class="fa-solid fa-triangle-exclamation me-2"></i> Atención: revisa este punto antes de continuar.
</div>`,
    },

    // ── Formularios ───────────────────────────────────────────────────────
    {
        name: 'Formulario de contacto',
        category: 'forms',
        icon: '<i class="fa-solid fa-envelope fa-lg"></i>',
        html: `<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6">
        <h2 class="fw-bold text-center mb-4">Contáctanos</h2>
        <form>
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" placeholder="Tu nombre">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" placeholder="tu@email.com">
          </div>
          <div class="mb-3">
            <label class="form-label">Mensaje</label>
            <textarea class="form-control" rows="4" placeholder="Tu mensaje..."></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-100">Enviar mensaje</button>
        </form>
      </div>
    </div>
  </div>
</section>`,
    },
    {
        name: 'Newsletter',
        category: 'forms',
        icon: '<i class="fa-solid fa-paper-plane fa-lg"></i>',
        html: `<section class="py-5 bg-primary text-white">
  <div class="container">
    <div class="row justify-content-center text-center">
      <div class="col-lg-6">
        <h3 class="fw-bold mb-2">Suscríbete a nuestro boletín</h3>
        <p class="mb-4 opacity-75">Recibe las últimas novedades directamente en tu email.</p>
        <form class="d-flex gap-2">
          <input type="email" class="form-control" placeholder="tu@email.com">
          <button type="submit" class="btn btn-light fw-semibold text-nowrap">Suscribirse</button>
        </form>
      </div>
    </div>
  </div>
</section>`,
    },
];
</script>
