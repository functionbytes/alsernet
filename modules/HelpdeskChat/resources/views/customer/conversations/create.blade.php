@extends('layouts.helpdesk')

@section('title', 'Nueva conversación')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="ti ti-plus me-2"></i>
                        Iniciar nueva conversación
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('customer.helpdesk.conversation.store') }}" id="conversationForm">
                        @csrf

                        {{-- Inbox Selection --}}
                        <div class="mb-4">
                            <label for="inbox_id" class="form-label">Departamento <span class="text-danger">*</span></label>
                            <select class="form-select @error('inbox_id') is-invalid @enderror" id="inbox_id" name="inbox_id" required>
                                <option value="">Selecciona un departamento</option>
                                @foreach($inboxes as $inbox)
                                    <option value="{{ $inbox->id }}" {{ old('inbox_id') == $inbox->id ? 'selected' : '' }}>
                                        {{ $inbox->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('inbox_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Selecciona el departamento adecuado para tu consulta</small>
                        </div>

                        {{-- Subject --}}
                        <div class="mb-4">
                            <label for="subject" class="form-label">Asunto</label>
                            <input type="text"
                                   class="form-control @error('subject') is-invalid @enderror"
                                   id="subject"
                                   name="subject"
                                   value="{{ old('subject') }}"
                                   placeholder="¿De qué trata tu consulta?">
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Message --}}
                        <div class="mb-4">
                            <label for="message" class="form-label">Mensaje <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('message') is-invalid @enderror"
                                      id="message"
                                      name="message"
                                      rows="6"
                                      required
                                      placeholder="Describe tu consulta o problema...">{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('customer.helpdesk.conversation.index') }}" class="btn btn-outline-secondary">
                                <i class="ti ti-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-send"></i> Iniciar conversación
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Help Text --}}
            <div class="card mt-3">
                <div class="card-body bg-light">
                    <h6 class="mb-2"><i class="ti ti-info-circle me-2"></i>Antes de enviar</h6>
                    <ul class="mb-0 small">
                        <li>Asegúrate de seleccionar el departamento correcto para una respuesta más rápida</li>
                        <li>Describe tu consulta con el mayor detalle posible</li>
                        <li>Recibirás una notificación cuando un agente responda tu mensaje</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
