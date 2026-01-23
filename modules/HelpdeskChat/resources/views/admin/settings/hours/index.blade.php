@extends('layouts.admin')

@section('content')
    <div class="widget-content">
        @include(\'helpdeskchat::components.alerts\')

    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-clock-history me-2"></i>Business Hours</h2>
                <form action="{{ route('admin.settings.hours.reset') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('Reset to default business hours (Monday-Friday, 9am-5pm)?')">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reset to Default
                    </button>
                </form>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card border">
                <div class="card-body">
                    <p class="text-muted">
                        Configure your business hours to let customers know when your team is available.
                        You can enable/disable specific days and set opening and closing times.
                    </p>

                    <form action="{{ route('admin.settings.hours.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Timezone Selection -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="timezone" class="form-label"><strong>Timezone</strong></label>
                                <select name="timezone" id="timezone" class="form-select" required>
                                    @php
                                        $currentTimezone = $businessHours->first()->first()->timezone ?? config('app.timezone');
                                        $timezones = timezone_identifiers_list();
                                    @endphp
                                    @foreach($timezones as $tz)
                                        <option value="{{ $tz }}" {{ $tz === $currentTimezone ? 'selected' : '' }}>
                                            {{ $tz }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">All times will be displayed in this timezone</small>
                            </div>
                        </div>

                        <!-- Business Hours Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Status</th>
                                        <th>Opening Time</th>
                                        <th>Closing Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($daysOfWeek as $dayNum => $dayName)
                                        @php
                                            $hour = $businessHours->get($dayNum)?->first();
                                        @endphp
                                        @if($hour)
                                            <tr>
                                                <td>
                                                    <strong>{{ $dayName }}</strong>
                                                    <input type="hidden" name="business_hours[{{ $loop->index }}][id]" value="{{ $hour->id }}">
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input day-toggle"
                                                               type="checkbox"
                                                               role="switch"
                                                               id="enabled_{{ $dayNum }}"
                                                               name="business_hours[{{ $loop->index }}][is_enabled]"
                                                               value="1"
                                                               data-day="{{ $dayNum }}"
                                                               {{ $hour->is_enabled ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="enabled_{{ $dayNum }}">
                                                            <span class="badge {{ $hour->is_enabled ? 'bg-success' : 'bg-secondary' }}" id="badge_{{ $dayNum }}">
                                                                {{ $hour->is_enabled ? 'Open' : 'Closed' }}
                                                            </span>
                                                        </label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="time"
                                                           class="form-control time-input"
                                                           name="business_hours[{{ $loop->index }}][open_time]"
                                                           id="open_{{ $dayNum }}"
                                                           value="{{ \Carbon\Carbon::parse($hour->open_time)->format('H:i') }}"
                                                           {{ !$hour->is_enabled ? 'disabled' : '' }}
                                                           required>
                                                </td>
                                                <td>
                                                    <input type="time"
                                                           class="form-control time-input"
                                                           name="business_hours[{{ $loop->index }}][close_time]"
                                                           id="close_{{ $dayNum }}"
                                                           value="{{ \Carbon\Carbon::parse($hour->close_time)->format('H:i') }}"
                                                           {{ !$hour->is_enabled ? 'disabled' : '' }}
                                                           required>
                                                </td>
                                            </tr>
                                            <!-- Hidden field for disabled days -->
                                            @if(!$hour->is_enabled)
                                                <input type="hidden" name="business_hours[{{ $loop->index }}][is_enabled]" value="0">
                                            @endif
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Quick Actions -->
                        <div class="row mt-4 mb-3">
                            <div class="col-md-12">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="enableWeekdays">
                                        <i class="fa fa-check-circle me-1"></i> Enable Weekdays
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="disableWeekends">
                                        <i class="fa fa-times-circle me-1"></i> Disable Weekends
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="set9to5">
                                        <i class="bi bi-clock me-1"></i> Set All to 9AM-5PM
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Current Status Card -->
            <div class="card mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Current Status</h5>
                </div>
                <div class="card-body">
                    @php
                        $now = \Carbon\Carbon::now($currentTimezone ?? config('app.timezone'));
                        $currentDay = $now->dayOfWeek;
                        $currentHour = $businessHours->get($currentDay)?->first();
                    @endphp

                    <div class="d-flex align-items-center">
                        @if($currentHour && $currentHour->is_enabled && $currentHour->isWithinBusinessHours($now))
                            <div class="badge bg-success fs-6 me-3">
                                <i class="fa fa-check-circle me-1"></i> Currently Open
                            </div>
                            <div>
                                <p class="mb-0">Today's hours: {{ \Carbon\Carbon::parse($currentHour->open_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($currentHour->close_time)->format('g:i A') }}</p>
                                <small class="text-muted">Current time: {{ $now->format('g:i A') }} ({{ $currentTimezone ?? config('app.timezone') }})</small>
                            </div>
                        @else
                            <div class="badge bg-secondary fs-6 me-3">
                                <i class="fa fa-times-circle me-1"></i> Currently Closed
                            </div>
                            <div>
                                @if($currentHour && $currentHour->is_enabled)
                                    <p class="mb-0">Today's hours: {{ \Carbon\Carbon::parse($currentHour->open_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($currentHour->close_time)->format('g:i A') }}</p>
                                @else
                                    <p class="mb-0">Closed today</p>
                                @endif
                                <small class="text-muted">Current time: {{ $now->format('g:i A') }} ({{ $currentTimezone ?? config('app.timezone') }})</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function() {
    // Toggle time inputs based on day enabled status
    const dayToggles = document.querySelectorAll('.day-toggle');

    dayToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const day = this.dataset.day;
            const openInput = document.getElementById(`open_${day}`);
            const closeInput = document.getElementById(`close_${day}`);
            const badge = document.getElementById(`badge_${day}`);

            if (this.checked) {
                openInput.disabled = false;
                closeInput.disabled = false;
                badge.textContent = 'Open';
                badge.classList.remove('bg-secondary');
                badge.classList.add('bg-success');
            } else {
                openInput.disabled = true;
                closeInput.disabled = true;
                badge.textContent = 'Closed';
                badge.classList.remove('bg-success');
                badge.classList.add('bg-secondary');
            }
        });
    });

    // Quick action: Enable weekdays
    document.getElementById('enableWeekdays').addEventListener('click', function() {
        [1, 2, 3, 4, 5].forEach(day => {
            const toggle = document.querySelector(`.day-toggle[data-day="${day}"]`);
            if (toggle && !toggle.checked) {
                toggle.checked = true;
                toggle.dispatchEvent(new Event('change'));
            }
        });
    });

    // Quick action: Disable weekends
    document.getElementById('disableWeekends').addEventListener('click', function() {
        [0, 6].forEach(day => {
            const toggle = document.querySelector(`.day-toggle[data-day="${day}"]`);
            if (toggle && toggle.checked) {
                toggle.checked = false;
                toggle.dispatchEvent(new Event('change'));
            }
        });
    });

    // Quick action: Set all to 9AM-5PM
    document.getElementById('set9to5').addEventListener('click', function() {
        document.querySelectorAll('.time-input').forEach(input => {
            if (!input.disabled) {
                if (input.id.startsWith('open_')) {
                    input.value = '09:00';
                } else if (input.id.startsWith('close_')) {
                    input.value = '17:00';
                }
            }
        });
    });
});
</script>
@endpush
@endsection
