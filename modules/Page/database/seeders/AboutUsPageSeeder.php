<?php

namespace Modules\Page\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;

/**
 * Crea la página "Sobre Nosotros" con contenido rico usando shortcodes del CMS.
 * Idempotente: no duplica si ya existe una página con el slug 'sobre-nosotros'.
 */
class AboutUsPageSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();

        if (! $user) {
            $this->command->warn('No users found. Skipping AboutUsPageSeeder.');

            return;
        }

        $content = <<<'HTML'
<div class="about-us-page">

  <!-- Hero / page header -->
  <section class="about-hero py-5 bg-light text-center">
    <div class="container">
      <h1 class="display-5 fw-bold mb-3">Sobre Alsernet</h1>
      <p class="lead text-muted mb-0">Más de 25 años aportando soluciones de caixilharia de calidad</p>
    </div>
  </section>

  <!-- Icon boxes: valores -->
  <section class="py-5">
    <div class="container">
      <div class="row g-4 text-center">
        <div class="col-12 col-md-4">
          <div class="card h-100 border-0 shadow-sm p-4">
            <div class="mb-3">
              <i class="fas fa-medal fa-2x" style="color:#90bb13"></i>
            </div>
            <h5 class="fw-bold">Calidad certificada</h5>
            <p class="text-muted mb-0">Trabajamos exclusivamente con materiales y proveedores que cumplen las más altas normas del sector.</p>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="card h-100 border-0 shadow-sm p-4">
            <div class="mb-3">
              <i class="fas fa-handshake fa-2x" style="color:#90bb13"></i>
            </div>
            <h5 class="fw-bold">Atención personal</h5>
            <p class="text-muted mb-0">Cada proyecto recibe atención individualizada desde el primer contacto hasta la instalación final.</p>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="card h-100 border-0 shadow-sm p-4">
            <div class="mb-3">
              <i class="fas fa-shield-halved fa-2x" style="color:#90bb13"></i>
            </div>
            <h5 class="fw-bold">Garantía total</h5>
            <p class="text-muted mb-0">Respaldamos todos nuestros trabajos con garantía de fabricante y servicio postventa dedicado.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Contadores -->
  <section class="py-5 bg-light">
    <div class="container">
      <div class="row g-4 text-center">
        <div class="col-6 col-md-3">
          <div class="display-4 fw-bold" style="color:#90bb13">1.500+</div>
          <div class="text-muted fw-semibold mt-1">Proyectos realizados</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="display-4 fw-bold" style="color:#90bb13">25</div>
          <div class="text-muted fw-semibold mt-1">Años de experiencia</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="display-4 fw-bold" style="color:#90bb13">98%</div>
          <div class="text-muted fw-semibold mt-1">Clientes satisfechos</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="display-4 fw-bold" style="color:#90bb13">12</div>
          <div class="text-muted fw-semibold mt-1">Técnicos especializados</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Historia -->
  <section class="py-5">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-12 col-lg-6">
          <h2 class="fw-bold mb-3">Nuestra historia</h2>
          <p class="text-muted">Fundada a comienzos del siglo XXI, Alsernet nació con la vocación de ofrecer soluciones integrales en carpintería de aluminio, PVC y madera. A lo largo de estos años hemos crecido gracias a la confianza de miles de familias y empresas que han elegido nuestros productos para sus hogares y locales comerciales.</p>
          <p class="text-muted">Hoy somos referentes del sector, combinando tecnología punta con el trato cercano que siempre nos ha caracterizado.</p>
        </div>
        <div class="col-12 col-lg-6">
          <div class="bg-light rounded-3 p-5 text-center">
            <i class="fas fa-building fa-4x text-muted opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonios -->
  <section class="py-5 bg-light">
    <div class="container">
      <h2 class="fw-bold text-center mb-4">Lo que dicen nuestros clientes</h2>
      <div class="row g-4">
        <div class="col-12 col-md-4">
          <div class="card border-0 shadow-sm p-4 h-100">
            <div class="mb-3 text-warning">
              <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
            </div>
            <p class="text-muted fst-italic mb-3">"Instalaron todas las ventanas de nuestra casa en tiempo récord y con una calidad impecable. Muy recomendables."</p>
            <strong class="small">— María G., Valencia</strong>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="card border-0 shadow-sm p-4 h-100">
            <div class="mb-3 text-warning">
              <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
            </div>
            <p class="text-muted fst-italic mb-3">"El presupuesto fue exacto, sin sorpresas. El trato del equipo, excelente. Repetiría sin dudarlo."</p>
            <strong class="small">— Carlos M., Madrid</strong>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="card border-0 shadow-sm p-4 h-100">
            <div class="mb-3 text-warning">
              <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-stroke"></i>
            </div>
            <p class="text-muted fst-italic mb-3">"Reformamos la oficina completa con ellos. Resultado profesional y entrega en el plazo acordado. 100% recomendados."</p>
            <strong class="small">— Ana P., Barcelona</strong>
          </div>
        </div>
      </div>
    </div>
  </section>

</div>
HTML;

        Page::query()->firstOrCreate(
            ['slug' => 'sobre-nosotros'],
            [
                'title' => 'Sobre Nosotros',
                'content' => $content,
                'template' => 'default',
                'description' => '25 años ofreciendo soluciones de caixilharia de calidad.',
                'status' => PageStatus::Published->value,
                'user_id' => $user->id,
                'seo_title' => 'Sobre Nosotros — Alsernet',
                'seo_description' => 'Conoce nuestra historia, valores y el equipo detrás de Alsernet. Más de 25 años al servicio de nuestros clientes.',
                'seo_keywords' => 'sobre nosotros, empresa, historia, valores, calidad, alsernet',
                'published_at' => now(),
            ]
        );

        $this->command->info('AboutUs page seeded.');
    }
}
