<div class="card analytics-empty-state">
    <div class="card-body text-center py-5">
        <div class="empty-state-icon mb-4">
            <i class="fas fa-chart-line fa-5x text-muted opacity-50"></i>
        </div>

        <h4 class="mb-3">Google Analytics no está configurado</h4>

        <p class="text-muted mb-4">
            Para ver estadísticas y métricas de tu sitio web, necesitas configurar Google Analytics GA4.
        </p>

        <div class="row justify-content-center mb-4">
            <div class="col-md-8">
                <div class="alert alert-info border-0 bg-info-subtle text-start">
                    <h6 class="fw-bold mb-2">
                        <i class="fas fa-info-circle me-2"></i>Pasos para configurar:
                    </h6>
                    <ol class="mb-0 ps-3">
                        <li class="mb-2">
                            Crea una cuenta de Google Analytics en
                            <a href="https://analytics.google.com" target="_blank" class="text-info fw-semibold">
                                analytics.google.com
                                <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                            </a>
                        </li>
                        <li class="mb-2">
                            Obtén tu Property ID (número de 9-10 dígitos)
                        </li>
                        <li class="mb-2">
                            Crea una cuenta de servicio en
                            <a href="https://console.cloud.google.com" target="_blank" class="text-info fw-semibold">
                                Google Cloud Console
                                <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                            </a>
                        </li>
                        <li class="mb-2">
                            Descarga el archivo JSON de credenciales
                        </li>
                        <li>
                            Configura los datos en la página de configuración
                        </li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="d-flex gap-3 justify-content-center">
            <a href="{{ route('settings.analytics.index') }}" class="btn btn-primary">
                <i class="fas fa-cog me-2"></i>Ir a Configuración
            </a>
            <a href="https://support.google.com/analytics/answer/9304153" target="_blank" class="btn btn-outline-secondary">
                <i class="fas fa-book me-2"></i>Ver Documentación
            </a>
        </div>

        <div class="mt-4">
            <small class="text-muted">
                <i class="fas fa-lock me-1"></i>
                Tus credenciales se almacenan de forma segura y solo son usadas para consultar estadísticas.
            </small>
        </div>
    </div>
</div>

<style>
.analytics-empty-state {
    border: 2px dashed #dee2e6;
}

.analytics-empty-state .empty-state-icon {
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 0.5;
    }
    50% {
        opacity: 0.3;
    }
}
</style>
