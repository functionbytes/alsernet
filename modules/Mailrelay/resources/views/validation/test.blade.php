@extends('layouts.theme')

@section('title', 'Validador de emails')

@section('content')
<div class="widget-content searchable-container list">

    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body">
            <h2 class="mb-1 fw-bold"><i class="fas fa-envelope-circle-check me-2"></i>Validador de direcciones de email</h2>
            <p class="text-muted mb-0">Prueba direcciones de email con múltiples métodos de validación</p>
        </div>
    </div>

    {{-- Input Card --}}
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom border-light">
            <h5 class="mb-0 fw-bold"><i class="fas fa-envelope me-2"></i>Entrada de email</h5>
        </div>
        <div class="card-body">
            <form id="validationForm">
                <div class="mb-3">
                    <label for="emailInput" class="form-label fw-semibold">Dirección de email</label>
                    <input type="text"
                           class="form-control form-control-lg"
                           id="emailInput"
                           placeholder="Ingresa la dirección de email a validar"
                           autocomplete="off">
                    <div class="form-text">Ingresa un email para probar con los validadores seleccionados</div>
                </div>

                <div class="row">
                    {{-- Validator Selection --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Seleccionar validadores</label>
                        <div class="card">
                            <div class="card-body">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" value="syntax" id="validatorSyntax" checked>
                                    <label class="form-check-label" for="validatorSyntax">
                                        <i class="fas fa-code text-primary me-2"></i>Validación de sintaxis
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" value="dns" id="validatorDns" checked>
                                    <label class="form-check-label" for="validatorDns">
                                        <i class="fas fa-network-wired text-success me-2"></i>Verificación DNS/MX
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" value="smtp" id="validatorSmtp" checked>
                                    <label class="form-check-label" for="validatorSmtp">
                                        <i class="fas fa-at text-info me-2"></i>Verificación SMTP
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" value="disposable" id="validatorDisposable" checked>
                                    <label class="form-check-label" for="validatorDisposable">
                                        <i class="fas fa-trash text-warning me-2"></i>Detección de emails desechables
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="role" id="validatorRole" checked>
                                    <label class="form-check-label" for="validatorRole">
                                        <i class="fas fa-id-badge text-secondary me-2"></i>Detección de emails de rol
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Validation Level --}}
                    <div class="col-md-6 mb-3">
                        <label for="validationLevel" class="form-label fw-semibold">Nivel de validación</label>
                        <select class="form-select form-select-lg select2" id="validationLevel">
                            <option value="basic">Básico - Validación rápida (Solo sintaxis)</option>
                            <option value="standard" selected>Estándar - Balanceado (Sintaxis + DNS)</option>
                            <option value="strict">Estricto - Completo (Todos los validadores)</option>
                            <option value="custom">Personalizado - Validadores seleccionados</option>
                        </select>
                        <div class="form-text">Elige la profundidad de la validación</div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <span class="spinner-border spinner-border-sm loading-spinner d-none" role="status"></span>
                                <span class="button-text">
                                    <i class="fas fa-play-circle me-2"></i>Validar email
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Results Section --}}
    <div class="results-section d-none" id="resultsSection">
        {{-- Overall Score Card --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h5 class="mb-0 fw-bold"><i class="fas fa-gauge me-2"></i>Resultados de validación</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="score-display fs-1 fw-bold my-3" id="scoreDisplay">--</div>
                        <p class="text-muted">Puntuación de validación</p>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Estado general</label>
                            <div id="statusBadges"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Dirección de email</label>
                            <div class="p-2 bg-light-secondary rounded">
                                <code id="emailDisplay" class="text-dark"></code>
                            </div>
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Resumen de validación</label>
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar" role="progressbar" id="progressBar" style="width: 0%">
                                    <span id="progressText">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detailed Results Card --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h5 class="mb-0 fw-bold"><i class="fas fa-list-check me-2"></i>Detalles de validadores</h5>
            </div>
            <div class="card-body" id="validatorResults">
                {{-- Validator results will be injected here --}}
            </div>
        </div>

        {{-- Additional Details Card --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h5 class="mb-0 fw-bold"><i class="fas fa-circle-info me-2"></i>Información adicional</h5>
            </div>
            <div class="card-body">
                <div id="additionalDetails">
                    {{-- Additional details will be injected here --}}
                </div>
            </div>
        </div>
    </div>

    {{-- Empty State --}}
    <div class="card" id="emptyState">
        <div class="card-body">
            <div class="text-center py-5">
                <div class="d-flex flex-column align-items-center">
                    <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                        <i class="fas fa-envelope fs-7"></i>
                    </div>
                    <h6 class="mb-1">No hay resultados de validación todavía</h6>
                    <p class="text-muted mb-0">Ingresa una dirección de email y haz clic en "Validar email" para ver los resultados</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const form = document.getElementById('validationForm');
    const emailInput = document.getElementById('emailInput');
    const validationLevel = document.getElementById('validationLevel');
    const loadingSpinner = document.querySelector('.loading-spinner');
    const buttonText = document.querySelector('.button-text');
    const resultsSection = document.getElementById('resultsSection');
    const emptyState = document.getElementById('emptyState');
    const scoreDisplay = document.getElementById('scoreDisplay');
    const emailDisplay = document.getElementById('emailDisplay');
    const statusBadges = document.getElementById('statusBadges');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const validatorResults = document.getElementById('validatorResults');
    const additionalDetails = document.getElementById('additionalDetails');

    const validatorCheckboxes = {
        syntax: document.getElementById('validatorSyntax'),
        dns: document.getElementById('validatorDns'),
        smtp: document.getElementById('validatorSmtp'),
        disposable: document.getElementById('validatorDisposable'),
        role: document.getElementById('validatorRole')
    };

    validationLevel.addEventListener('change', function() {
        const level = this.value;
        switch(level) {
            case 'basic':
                validatorCheckboxes.syntax.checked = true;
                validatorCheckboxes.dns.checked = false;
                validatorCheckboxes.smtp.checked = false;
                validatorCheckboxes.disposable.checked = false;
                validatorCheckboxes.role.checked = false;
                break;
            case 'standard':
                validatorCheckboxes.syntax.checked = true;
                validatorCheckboxes.dns.checked = true;
                validatorCheckboxes.smtp.checked = false;
                validatorCheckboxes.disposable.checked = true;
                validatorCheckboxes.role.checked = true;
                break;
            case 'strict':
                Object.values(validatorCheckboxes).forEach(cb => cb.checked = true);
                break;
        }
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const email = emailInput.value.trim();

        if (!email) {
            alert('Por favor ingresa una dirección de email');
            return;
        }

        const selectedValidators = [];
        Object.entries(validatorCheckboxes).forEach(([key, checkbox]) => {
            if (checkbox.checked) selectedValidators.push(key);
        });

        if (selectedValidators.length === 0) {
            alert('Por favor selecciona al menos un validador');
            return;
        }

        loadingSpinner.classList.remove('d-none');
        buttonText.innerHTML = '<i class="fas fa-hourglass-end me-2"></i>Validando...';
        form.querySelector('button[type="submit"]').disabled = true;

        try {
            const response = await fetch('/mailrelay/validation/validate-email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    email: email,
                    validators: selectedValidators,
                    level: validationLevel.value
                })
            });

            const data = await response.json();

            if (response.ok) {
                displayResults(data);
            } else {
                alert(data.message || 'La validación falló');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Ocurrió un error durante la validación');
        } finally {
            loadingSpinner.classList.add('d-none');
            buttonText.innerHTML = '<i class="fas fa-play-circle me-2"></i>Validar email';
            form.querySelector('button[type="submit"]').disabled = false;
        }
    });

    function displayResults(data) {
        emptyState.classList.add('d-none');
        resultsSection.classList.remove('d-none');

        emailDisplay.textContent = data.email;

        const score = data.score || 0;
        scoreDisplay.textContent = score;
        scoreDisplay.className = 'score-display fs-1 fw-bold my-3 ' + getScoreClass(score);

        progressBar.style.width = score + '%';
        progressBar.className = 'progress-bar ' + getProgressBarClass(score);
        progressText.textContent = score + '%';

        displayStatusBadges(data);
        displayValidatorResults(data.validators || {});
        displayAdditionalDetails(data);
    }

    function getScoreClass(score) {
        if (score >= 90) return 'text-success';
        if (score >= 70) return 'text-info';
        if (score >= 50) return 'text-warning';
        if (score >= 30) return 'text-orange';
        return 'text-danger';
    }

    function getProgressBarClass(score) {
        if (score >= 90) return 'bg-success';
        if (score >= 70) return 'bg-info';
        if (score >= 50) return 'bg-warning';
        return 'bg-danger';
    }

    function displayStatusBadges(data) {
        let badges = '';
        if (data.is_valid) {
            badges += '<span class="badge bg-success-subtle text-success rounded-3 py-2 fw-semibold fs-2 me-2"><i class="fas fa-circle-check"></i> Válido</span>';
        } else {
            badges += '<span class="badge bg-danger-subtle text-danger rounded-3 py-2 fw-semibold fs-2 me-2"><i class="fas fa-circle-xmark"></i> Inválido</span>';
        }
        if (data.is_disposable) {
            badges += '<span class="badge bg-warning-subtle text-warning rounded-3 py-2 fw-semibold fs-2 me-2"><i class="fas fa-trash"></i> Desechable</span>';
        }
        if (data.is_role_based) {
            badges += '<span class="badge bg-secondary-subtle text-secondary rounded-3 py-2 fw-semibold fs-2 me-2"><i class="fas fa-id-badge"></i> Email de rol</span>';
        }
        if (data.has_mx_records) {
            badges += '<span class="badge bg-info-subtle text-info rounded-3 py-2 fw-semibold fs-2 me-2"><i class="fas fa-network-wired"></i> Registros MX encontrados</span>';
        }
        if (data.smtp_valid) {
            badges += '<span class="badge bg-primary-subtle text-primary rounded-3 py-2 fw-semibold fs-2 me-2"><i class="fas fa-envelope-circle-check"></i> SMTP válido</span>';
        }
        statusBadges.innerHTML = badges;
    }

    function displayValidatorResults(validators) {
        let html = '';
        Object.entries(validators).forEach(([name, result]) => {
            const bgClass = result.valid ? 'bg-success-subtle' : 'bg-danger-subtle';
            const iconClass = result.valid ? 'fas fa-circle-check text-success' : 'fas fa-circle-xmark text-danger';

            html += `
                <div class="p-3 rounded mb-3 ${bgClass}">
                    <div class="d-flex align-items-center">
                        <i class="${iconClass} fs-4 me-3"></i>
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-bold">${getValidatorName(name)}</h6>
                            <p class="mb-0 text-muted">${result.message || 'Sin información adicional'}</p>
                        </div>
                        <span class="badge ${result.valid ? 'bg-success' : 'bg-danger'} rounded-3 py-2">
                            ${result.valid ? 'Aprobado' : 'Fallido'}
                        </span>
                    </div>
                </div>
            `;
        });
        validatorResults.innerHTML = html || '<p class="text-muted">No hay resultados de validadores disponibles</p>';
    }

    function displayAdditionalDetails(data) {
        let html = '';
        if (data.domain) html += createDetailItem('Dominio', data.domain);
        if (data.local_part) html += createDetailItem('Parte local', data.local_part);
        if (data.mx_records && data.mx_records.length > 0) html += createDetailItem('Registros MX', data.mx_records.join(', '));
        if (data.validation_time) html += createDetailItem('Tiempo de validación', data.validation_time + ' ms');
        if (data.timestamp) html += createDetailItem('Marca de tiempo', new Date(data.timestamp).toLocaleString());
        additionalDetails.innerHTML = html || '<p class="text-muted">No hay detalles adicionales disponibles</p>';
    }

    function createDetailItem(label, value) {
        return `
            <div class="d-flex py-2 border-bottom">
                <span class="fw-semibold text-muted" style="min-width: 150px;">${label}:</span>
                <span class="text-dark">${value}</span>
            </div>
        `;
    }

    function getValidatorName(key) {
        const names = {
            syntax: 'Validación de sintaxis',
            dns: 'Registros DNS/MX',
            smtp: 'Verificación SMTP',
            disposable: 'Detección de email desechable',
            role: 'Detección de email de rol'
        };
        return names[key] || key.charAt(0).toUpperCase() + key.slice(1);
    }
});
</script>
@endpush
