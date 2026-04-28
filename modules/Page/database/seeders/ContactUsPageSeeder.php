<?php

namespace Modules\Page\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;

/**
 * Crea la página "Contacto" con bloques de información + formulario de contacto.
 * Idempotente: no duplica si ya existe una página con el slug 'contacto'.
 */
class ContactUsPageSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();

        if (! $user) {
            $this->command->warn('No users found. Skipping ContactUsPageSeeder.');

            return;
        }

        $content = <<<'HTML'
<div class="contact-page">

  <!-- Hero -->
  <section class="contact-hero py-5 bg-light text-center">
    <div class="container">
      <h1 class="display-5 fw-bold mb-3">Contacto</h1>
      <p class="lead text-muted mb-0">¿Tienes una pregunta o necesitas presupuesto? Estamos aquí para ayudarte</p>
    </div>
  </section>

  <!-- Datos de contacto -->
  <section class="py-5">
    <div class="container">
      <div class="row g-4 text-center">
        <div class="col-12 col-md-4">
          <div class="card h-100 border-0 shadow-sm p-4">
            <div class="mb-3">
              <i class="fas fa-phone fa-2x" style="color:#90bb13"></i>
            </div>
            <h5 class="fw-bold">Teléfono</h5>
            <p class="text-muted mb-1">Lun a Vie, 9:00 - 18:00</p>
            <a href="tel:+34900123456" class="fw-semibold text-decoration-none">+34 900 123 456</a>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="card h-100 border-0 shadow-sm p-4">
            <div class="mb-3">
              <i class="fas fa-envelope fa-2x" style="color:#90bb13"></i>
            </div>
            <h5 class="fw-bold">Email</h5>
            <p class="text-muted mb-1">Respondemos en 24h</p>
            <a href="mailto:info@alsernet.com" class="fw-semibold text-decoration-none">info@alsernet.com</a>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="card h-100 border-0 shadow-sm p-4">
            <div class="mb-3">
              <i class="fas fa-location-dot fa-2x" style="color:#90bb13"></i>
            </div>
            <h5 class="fw-bold">Oficina</h5>
            <p class="text-muted mb-1">Visítanos sin cita previa</p>
            <span class="fw-semibold">C/ Industria 42, Valencia</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Formulario + mapa -->
  <section class="py-5 bg-light">
    <div class="container">
      <div class="row g-5">
        <div class="col-12 col-lg-6">
          <h2 class="fw-bold mb-3">Envíanos un mensaje</h2>
          <p class="text-muted mb-4">Completa el formulario y nos pondremos en contacto contigo en menos de 24 horas laborales.</p>
          [contact-form]
        </div>
        <div class="col-12 col-lg-6">
          <h2 class="fw-bold mb-3">Cómo llegar</h2>
          <p class="text-muted mb-4">Estamos en el polígono industrial. Estacionamiento gratuito disponible.</p>
          <div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow-sm">
            <iframe
              src="https://www.openstreetmap.org/export/embed.html?bbox=-0.40%2C39.45%2C-0.35%2C39.50&amp;layer=mapnik"
              loading="lazy"
              title="Ubicación oficina Alsernet"
              referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Horario -->
  <section class="py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6 text-center">
          <h2 class="fw-bold mb-4">Horario de atención</h2>
          <div class="card border-0 shadow-sm p-4">
            <div class="d-flex justify-content-between border-bottom py-2">
              <span class="fw-semibold">Lunes a Viernes</span>
              <span class="text-muted">9:00 - 14:00 / 16:00 - 19:00</span>
            </div>
            <div class="d-flex justify-content-between border-bottom py-2">
              <span class="fw-semibold">Sábado</span>
              <span class="text-muted">10:00 - 13:00</span>
            </div>
            <div class="d-flex justify-content-between py-2">
              <span class="fw-semibold">Domingo</span>
              <span class="text-muted">Cerrado</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

</div>
HTML;

        Page::query()->firstOrCreate(
            ['slug' => 'contacto'],
            [
                'title' => 'Contacto',
                'content' => $content,
                'template' => 'default',
                'description' => 'Datos de contacto, formulario y mapa de ubicación de Alsernet.',
                'status' => PageStatus::Published->value,
                'user_id' => $user->id,
                'seo_title' => 'Contacto — Alsernet',
                'seo_description' => 'Contáctanos por teléfono, email o visitando nuestra oficina. Respondemos en menos de 24h.',
                'seo_keywords' => 'contacto, telefono, email, oficina, alsernet',
                'published_at' => now(),
            ]
        );

        $this->command->info('Contact page seeded.');
    }
}
