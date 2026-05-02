@extends('layouts.admin')

@section('title', 'Calendario de Publicaciones')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<style>
    #calendar {
        max-width: 1200px;
        margin: 0 auto;
    }

    .fc-event {
        cursor: pointer;
        border: none;
        padding: 2px 5px;
    }

    .fc-event:hover {
        opacity: 0.8;
    }

    .fc-toolbar-title {
        font-size: 1.5rem !important;
        font-weight: 600 !important;
    }

    .fc-button {
        text-transform: capitalize !important;
        font-weight: 500 !important;
    }

    .fc-button-primary {
        background-color: #5D87FF !important;
        border-color: #5D87FF !important;
    }

    .fc-button-primary:hover {
        background-color: #4570EA !important;
        border-color: #4570EA !important;
    }

    .fc-button-primary:disabled {
        background-color: #E8F1FF !important;
        border-color: #E8F1FF !important;
        color: #5D87FF !important;
    }
</style>
@endpush

@section('content')

    <div class="widget-content">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-calendar-alt me-2"></i>Calendario de Publicaciones
                        </h5>
                        <p class="small mb-0 text-muted">Visualiza y gestiona tus publicaciones programadas</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.social.publishing.index') }}" class="btn btn-light">
                            <i class="fas fa-list me-1"></i> Vista Lista
                        </a>
                        <a href="{{ route('admin.social.publishing.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva Publicación
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/es.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');

        const events = @json($posts);

        const calendar = new FullCalendar.Calendar(calendarEl, {
            // Configuration
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            buttonText: {
                today: 'Hoy',
                month: 'Mes',
                week: 'Semana',
                day: 'Día',
                list: 'Lista'
            },

            // Events
            events: events,

            // Event click handler
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                if (info.event.url) {
                    window.location.href = info.event.url;
                }
            },

            // Event rendering
            eventContent: function(arg) {
                const timeText = arg.timeText;
                const title = arg.event.title;

                return {
                    html: `
                        <div class="fc-event-main-frame">
                            <div class="fc-event-time">${timeText}</div>
                            <div class="fc-event-title-container">
                                <div class="fc-event-title fc-sticky">${title}</div>
                            </div>
                        </div>
                    `
                };
            },

            // Styling
            height: 'auto',
            eventDisplay: 'block',
            displayEventTime: true,
            displayEventEnd: false,

            // Date click handler (optional - create new post on date click)
            dateClick: function(info) {
                const createUrl = '{{ route("admin.social.publishing.create") }}';
                const scheduledDate = info.dateStr;
                window.location.href = `${createUrl}?scheduled_at=${scheduledDate}`;
            },

            // No events message
            noEventsContent: {
                html: `
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="text-muted mb-2">No hay publicaciones programadas</h5>
                        <p class="text-muted mb-4">Haz clic en una fecha para crear una nueva publicación</p>
                    </div>
                `
            }
        });

        calendar.render();

        // Handle window resize
        window.addEventListener('resize', function() {
            calendar.updateSize();
        });
    });
</script>
@endpush
