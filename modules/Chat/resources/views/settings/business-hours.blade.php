@extends('layouts.theme')

@section('title', 'Business hours')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-clock text-primary"></i> Business hours
                </h2>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('settings.chat.business-hours.update') }}" method="POST" id="businessHoursForm">
                                @csrf
                                @method('PUT')

                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="enabled"
                                           name="enabled" value="1"
                                           {{ $settings['enabled'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enabled">
                                        <strong>Enable business hours</strong>
                                        <br>
                                        <small class="text-muted">When enabled, customers will see your availability schedule</small>
                                    </label>
                                </div>

                                <hr>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Day</th>
                                                <th>Status</th>
                                                <th>Opening</th>
                                                <th>Closing</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                                $dayLabels = [
                                                    'monday' => 'Monday',
                                                    'tuesday' => 'Tuesday',
                                                    'wednesday' => 'Wednesday',
                                                    'thursday' => 'Thursday',
                                                    'friday' => 'Friday',
                                                    'saturday' => 'Saturday',
                                                    'sunday' => 'Sunday',
                                                ];
                                            @endphp
                                            @foreach($days as $day)
                                                <tr>
                                                    <td><strong>{{ $dayLabels[$day] }}</strong></td>
                                                    <td>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input day-toggle" type="checkbox"
                                                                   id="{{ $day }}_enabled"
                                                                   name="{{ $day }}[enabled]" value="1"
                                                                   data-day="{{ $day }}"
                                                                   {{ $settings[$day]['enabled'] ?? false ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="{{ $day }}_enabled">
                                                                <span class="badge {{ $settings[$day]['enabled'] ?? false ? 'bg-success' : 'bg-secondary' }}"
                                                                      id="badge_{{ $day }}">
                                                                    {{ $settings[$day]['enabled'] ?? false ? 'Open' : 'Closed' }}
                                                                </span>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="time" class="form-control form-control-sm"
                                                               name="{{ $day }}[start]"
                                                               id="{{ $day }}_start"
                                                               value="{{ $settings[$day]['start'] ?? '09:00' }}"
                                                               {{ !($settings[$day]['enabled'] ?? false) ? 'disabled' : '' }}
                                                               required>
                                                    </td>
                                                    <td>
                                                        <input type="time" class="form-control form-control-sm"
                                                               name="{{ $day }}[end]"
                                                               id="{{ $day }}_end"
                                                               value="{{ $settings[$day]['end'] ?? '18:00' }}"
                                                               {{ !($settings[$day]['enabled'] ?? false) ? 'disabled' : '' }}
                                                               required>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-between mt-3">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="enableWeekdays">
                                            <i class="fas fa-check-circle"></i> Enable weekdays
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm" id="disableWeekends">
                                            <i class="fas fa-times-circle"></i> Disable weekends
                                        </button>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check-circle"></i> Save changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas fa-info-circle"></i> About business hours
                            </h6>
                            <p class="small mb-3">
                                Set your team's working hours so customers know when to expect responses.
                            </p>
                            <p class="small mb-0">
                                Outside business hours, the widget will display an offline message and conversations will be queued.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function() {
    $('.day-toggle').on('change', function() {
        var day = $(this).data('day');
        var isChecked = $(this).is(':checked');
        var $badge = $('#badge_' + day);

        $('#' + day + '_start, #' + day + '_end').prop('disabled', !isChecked);

        if (isChecked) {
            $badge.text('Open').removeClass('bg-secondary').addClass('bg-success');
        } else {
            $badge.text('Closed').removeClass('bg-success').addClass('bg-secondary');
        }
    });

    $('#enableWeekdays').on('click', function() {
        ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].forEach(function(day) {
            var $toggle = $('#' + day + '_enabled');
            if (!$toggle.is(':checked')) {
                $toggle.prop('checked', true).trigger('change');
            }
        });
    });

    $('#disableWeekends').on('click', function() {
        ['saturday', 'sunday'].forEach(function(day) {
            var $toggle = $('#' + day + '_enabled');
            if ($toggle.is(':checked')) {
                $toggle.prop('checked', false).trigger('change');
            }
        });
    });
});
</script>
@endpush
@endsection
