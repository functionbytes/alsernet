{{-- Server Type Selector Component
    Parameters:
    - selectedType: string (current selected type)
    - serverTypes: array (optional, default: predefined types)
    - inputName: string (name of hidden input, default: 'type')
    - required: boolean (default: true)
--}}

@php
    $selectedType = $selectedType ?? '';
    $inputName = $inputName ?? 'type';
    $required = $required ?? true;

    // Default server types with icons and descriptions
    $serverTypes = $serverTypes ?? [
        [
            'value' => 'smtp',
            'label' => 'SMTP',
            'icon' => 'fa-envelope',
            'description' => 'Servidor SMTP personalizado',
            'color' => 'primary'
        ],
        [
            'value' => 'sendgrid',
            'label' => 'SendGrid',
            'icon' => 'fa-paper-plane',
            'description' => 'API de SendGrid',
            'color' => 'info'
        ],
        [
            'value' => 'mailgun',
            'label' => 'Mailgun',
            'icon' => 'fa-gun',
            'description' => 'API de Mailgun',
            'color' => 'warning'
        ],
        [
            'value' => 'ses',
            'label' => 'Amazon SES',
            'icon' => 'fa-amazon',
            'description' => 'AWS Simple Email Service',
            'color' => 'secondary'
        ],
        [
            'value' => 'postmark',
            'label' => 'Postmark',
            'icon' => 'fa-stamp',
            'description' => 'API de Postmark',
            'color' => 'danger'
        ],
        [
            'value' => 'sparkpost',
            'label' => 'SparkPost',
            'icon' => 'fa-fire',
            'description' => 'API de SparkPost',
            'color' => 'success'
        ],
        [
            'value' => 'mailjet',
            'label' => 'Mailjet',
            'icon' => 'fa-jet-fighter',
            'description' => 'API de Mailjet',
            'color' => 'primary'
        ]
    ];
@endphp

<div class="server-type-selector">
    {{-- Hidden Input for Form --}}
    <input type="hidden"
           id="{{ $inputName }}"
           name="{{ $inputName }}"
           value="{{ old($inputName, $selectedType) }}"
           @if($required) required @endif>

    {{-- Type Cards Grid --}}
    <div class="row g-3">
        @foreach($serverTypes as $type)
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="type-card card h-100 cursor-pointer border-2 transition-all"
                     data-type-value="{{ $type['value'] }}"
                     onclick="selectServerType(this, '{{ $inputName }}')">

                    {{-- Selected Indicator --}}
                    <div class="card-body position-relative p-3">
                        <div class="position-absolute top-0 end-0 p-2"
                             id="indicator-{{ $type['value'] }}"
                             style="display: {{ old($inputName, $selectedType) === $type['value'] ? 'block' : 'none' }}">
                            <i class="fas fa-check-circle text-success fs-5"></i>
                        </div>

                        {{-- Icon and Title --}}
                        <div class="text-center mb-3">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                 style="width: 50px; height: 50px; background-color: rgba(var(--bs-{{ $type['color'] }}-rgb), 0.1);">
                                <i class="fas {{ $type['icon'] }} fs-4 text-{{ $type['color'] }}"></i>
                            </div>
                            <h6 class="card-title fw-bold mb-1">{{ $type['label'] }}</h6>
                            <p class="card-text small text-muted mb-0">{{ $type['description'] }}</p>
                        </div>

                        {{-- Hover State Background --}}
                        <div class="stretched-link"></div>
                    </div>

                    {{-- Border State --}}
                    <div class="card-footer bg-transparent border-0"
                         id="footer-{{ $type['value'] }}"
                         style="border-top: 2px solid transparent; transition: border-color 0.3s ease;"
                         data-border-color="{{ $type['color'] }}">
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Error Message --}}
    @error($inputName)
        <div class="invalid-feedback d-block mt-2">
            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
        </div>
    @enderror
</div>

@push('styles')
<style>
    .server-type-selector {
        margin-bottom: 1.5rem;
    }

    .type-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border-color: var(--bs-light) !important;
    }

    .type-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .type-card.selected {
        border-color: var(--bs-primary) !important;
        background-color: rgba(13, 110, 253, 0.02);
    }

    /* Card footer border animation */
    .type-card .card-footer {
        border-top: 2px solid transparent;
    }

    .type-card.selected .card-footer {
        border-top-color: var(--bs-primary);
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .type-card {
            border-color: var(--bs-border-color);
        }

        .type-card.selected {
            background-color: rgba(13, 110, 253, 0.1);
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function selectServerType(element, inputName) {
        // Get the type value from data attribute
        const typeValue = element.getAttribute('data-type-value');

        // Update hidden input
        document.getElementById(inputName).value = typeValue;

        // Remove selected class from all cards
        document.querySelectorAll('.type-card').forEach(card => {
            card.classList.remove('selected');
            const cardValue = card.getAttribute('data-type-value');
            document.getElementById('indicator-' + cardValue).style.display = 'none';
        });

        // Add selected class to clicked card
        element.classList.add('selected');
        document.getElementById('indicator-' + typeValue).style.display = 'block';

        // Trigger change event for form validation
        const event = new Event('change', { bubbles: true });
        document.getElementById(inputName).dispatchEvent(event);

        // Update border color based on type
        updateTypeCardBorder(typeValue);
    }

    function updateTypeCardBorder(typeValue) {
        document.querySelectorAll('.type-card').forEach(card => {
            const footer = card.querySelector('.card-footer');
            const cardValue = card.getAttribute('data-type-value');

            if (cardValue === typeValue) {
                const borderColor = footer.getAttribute('data-border-color');
                footer.style.borderTopColor = `var(--bs-${borderColor})`;
            } else {
                footer.style.borderTopColor = 'transparent';
            }
        });
    }

    // Initialize selected type on page load
    document.addEventListener('DOMContentLoaded', function() {
        const inputName = 'type';
        const selectedValue = document.getElementById(inputName).value;

        if (selectedValue) {
            const selectedCard = document.querySelector(`[data-type-value="${selectedValue}"]`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
                document.getElementById('indicator-' + selectedValue).style.display = 'block';
                updateTypeCardBorder(selectedValue);
            }
        }
    });
</script>
@endpush
