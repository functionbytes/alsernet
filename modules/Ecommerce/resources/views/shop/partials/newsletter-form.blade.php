<section class="newsletter-section py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="text-center mb-3">
                    <h5 class="fw-bold mb-1">Suscribete al newsletter</h5>
                    <p class="text-muted small mb-0">Recibe las ultimas novedades y ofertas exclusivas.</p>
                </div>
                <form class="js-newsletter-form" action="{{ route('shop.newsletter.subscribe') }}" method="POST" novalidate>
                    @csrf
                    <div class="input-group">
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            placeholder="Tu correo electronico"
                            required
                            autocomplete="email"
                        >
                        <button type="submit" class="btn btn-primary">
                            Suscribirme
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
