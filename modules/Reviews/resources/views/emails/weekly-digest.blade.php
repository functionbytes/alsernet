<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9;">

    <div style="background: #fff; border-radius: 8px; padding: 30px; border-top: 4px solid #b10100;">

        <h2 style="color: #b10100; margin-top: 0;">
            Tu resumen semanal de reseñas
        </h2>

        <p style="color: #555;">
            Hola <strong>{{ $user->name }}</strong>,<br>
            aqui tienes un resumen de la actividad de reseñas durante los ultimos 7 dias
            ({{ $stats['period']['from'] }} — {{ $stats['period']['to'] }}).
        </p>

        {{-- KPI Row --}}
        <table style="width: 100%; border-collapse: collapse; margin: 24px 0;">
            <tr>
                <td style="width: 25%; padding: 4px;">
                    <div style="background: #f8faf0; border: 1px solid #d9e8a0; border-radius: 6px; padding: 16px 12px; text-align: center;">
                        <div style="font-size: 26px; font-weight: 700; color: #b10100;">{{ $stats['new_reviews'] }}</div>
                        <div style="font-size: 11px; color: #888; margin-top: 4px;">Nuevas reseñas</div>
                    </div>
                </td>
                <td style="width: 25%; padding: 4px;">
                    <div style="background: #f8faf0; border: 1px solid #d9e8a0; border-radius: 6px; padding: 16px 12px; text-align: center;">
                        <div style="font-size: 26px; font-weight: 700; color: #b10100;">{{ number_format($stats['avg_rating'], 1) }}</div>
                        <div style="font-size: 11px; color: #888; margin-top: 4px;">Rating promedio</div>
                    </div>
                </td>
                <td style="width: 25%; padding: 4px;">
                    <div style="background: #f8faf0; border: 1px solid #d9e8a0; border-radius: 6px; padding: 16px 12px; text-align: center;">
                        <div style="font-size: 26px; font-weight: 700; color: #b10100;">{{ $stats['response_rate'] }}%</div>
                        <div style="font-size: 11px; color: #888; margin-top: 4px;">Tasa de respuesta</div>
                    </div>
                </td>
                <td style="width: 25%; padding: 4px;">
                    <div style="background: {{ $stats['pending_replies'] > 0 ? '#fff5f0' : '#f8faf0' }}; border: 1px solid {{ $stats['pending_replies'] > 0 ? '#fdc8a0' : '#d9e8a0' }}; border-radius: 6px; padding: 16px 12px; text-align: center;">
                        <div style="font-size: 26px; font-weight: 700; color: {{ $stats['pending_replies'] > 0 ? '#FA896B' : '#b10100' }};">{{ $stats['pending_replies'] }}</div>
                        <div style="font-size: 11px; color: #888; margin-top: 4px;">Sin respuesta</div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Rating trend --}}
        @php
            $change = $stats['rating_change'] ?? 0;
            $changeAbs = abs($change);
        @endphp
        <div style="background: #f8f9fa; border-radius: 6px; padding: 14px 18px; margin: 20px 0;">
            @if ($change > 0)
                <p style="margin: 0; color: #13C672; font-weight: 600;">
                    &#8593; Tu rating subio {{ number_format($changeAbs, 2) }} puntos esta semana
                </p>
            @elseif ($change < 0)
                <p style="margin: 0; color: #FA896B; font-weight: 600;">
                    &#8595; Tu rating bajo {{ number_format($changeAbs, 2) }} puntos esta semana
                </p>
            @else
                <p style="margin: 0; color: #888; font-weight: 600;">
                    Tu rating se mantuvo estable esta semana
                </p>
            @endif
        </div>

        {{-- Reviews needing attention --}}
        @if (!empty($stats['attention_reviews']))
            <h3 style="color: #555; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px;">
                Reseñas que necesitan atencion
            </h3>

            @foreach ($stats['attention_reviews'] as $review)
                <div style="border-left: 3px solid #FA896B; background: #fff9f8; border-radius: 0 4px 4px 0; padding: 12px 16px; margin: 12px 0;">
                    <div style="margin-bottom: 6px;">
                        @for ($i = 1; $i <= 5; $i++)
                            <span style="color: {{ $i <= ($review['rating'] ?? 0) ? '#FEC90F' : '#ddd' }}; font-size: 14px;">&#9733;</span>
                        @endfor
                        <span style="color: #888; font-size: 12px; margin-left: 6px;">{{ $review['reviewer_name'] ?? 'Anonimo' }}</span>
                        <span style="color: #bbb; font-size: 11px; margin-left: 8px;">{{ $review['location_name'] ?? '' }}</span>
                    </div>
                    @if (!empty($review['comment']))
                        <p style="color: #666; font-style: italic; margin: 0; font-size: 13px;">&ldquo;{{ $review['comment'] }}&rdquo;</p>
                    @endif
                </div>
            @endforeach
        @endif

        {{-- Top / worst location --}}
        @if (!empty($stats['top_location']) || !empty($stats['worst_location']))
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                @if (!empty($stats['top_location']))
                    <tr>
                        <td style="padding: 8px 0; color: #888; font-size: 13px; width: 160px;">Mejor ubicacion</td>
                        <td style="padding: 8px 0; font-weight: 600; font-size: 13px; color: #13C672;">
                            {{ $stats['top_location']['name'] ?? '' }}
                            @if (!empty($stats['top_location']['avg_rating']))
                                ({{ number_format($stats['top_location']['avg_rating'], 1) }} &#9733;)
                            @endif
                        </td>
                    </tr>
                @endif
                @if (!empty($stats['worst_location']))
                    <tr>
                        <td style="padding: 8px 0; color: #888; font-size: 13px;">Ubicacion a mejorar</td>
                        <td style="padding: 8px 0; font-weight: 600; font-size: 13px; color: #FA896B;">
                            {{ $stats['worst_location']['name'] ?? '' }}
                            @if (!empty($stats['worst_location']['avg_rating']))
                                ({{ number_format($stats['worst_location']['avg_rating'], 1) }} &#9733;)
                            @endif
                        </td>
                    </tr>
                @endif
            </table>
        @endif

        {{-- CTA --}}
        <div style="text-align: center; margin: 28px 0;">
            <a href="{{ route('reviews.dashboard') }}"
               style="background: #b10100; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 15px; display: inline-block;">
                Ver dashboard
            </a>
        </div>

        <p style="color: #999; font-size: 12px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 16px;">
            Recibes este correo porque tienes activado el resumen semanal en tus preferencias de notificacion.
            Este es un correo automatico. No respondas a este mensaje.
        </p>

    </div>

</body>
</html>
