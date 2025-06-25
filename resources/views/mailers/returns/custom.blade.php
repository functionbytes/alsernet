@extends('emails.layouts.return')

@section('title', $customData['subject'] ?? 'Información sobre su devolución')

@section('content')
    @if($show_header)
        <div class="header-section">
            <h2>{{ $customData['header'] ?? 'Actualización de su Devolución' }}</h2>
            <p class="subtitle">Devolución #{{ $return->number }}</p>
        </div>
    @endif

    <div class="content-section">
        {!! $content !!}
    </div>

    @if(!empty($action_buttons))
        <div class="action-section" style="text-align: center; margin: 30px 0;">
            @foreach($action_buttons as $button)
                <a href="{{ $button['url'] }}"
                   class="button button-{{ $button['style'] }}"
                   style="display: inline-block; padding: 12px 24px; margin: 5px;
                          background-color: {{ $button['style'] === 'primary' ? '#007bff' : ($button['style'] === 'danger' ? '#dc3545' : '#6c757d') }};
                          color: white; text-decoration: none; border-radius: 5px;">
                    @if($button['icon'])
                        <span style="margin-right: 5px;">{{ $button['icon'] }}</span>
                    @endif
                    {{ $button['text'] }}
                </a>
            @endforeach
        </div>
    @endif

    @if(!empty($additional_info))
        <div class="info-section" style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            @foreach($additional_info as $info)
                <p style="margin: 5px 0;">
                    <strong>{{ $info['label'] }}:</strong> {{ $info['value'] }}
                </p>
            @endforeach
        </div>
    @endif

    @if($signature)
        <div class="signature-section" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            {!! $signature !!}
        </div>
    @endif

    @if($show_footer)
        <div class="footer-section" style="margin-top: 40px; text-align: center; color: #666; font-size: 12px;">
            <p>Este email está relacionado con su devolución #{{ $return->number }}</p>
            <p>Si tiene alguna pregunta, no dude en contactarnos.</p>
        </div>
    @endif

    @if($custom_css)
        <style>
            {!! $custom_css !!}
        </style>
    @endif
@endsection
