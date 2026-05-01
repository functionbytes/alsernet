<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Califica tu experiencia</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background:#f7f7fa; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .csat-card { max-width:480px; border-radius:14px; box-shadow:0 8px 24px rgba(0,0,0,.08); }
        .csat-stars { display:flex; gap:8px; justify-content:center; }
        .csat-star { font-size:2.5rem; color:#dcdce6; cursor:pointer; transition:transform .1s ease, color .1s ease; }
        .csat-star:hover, .csat-star.active { color:#FEC90F; transform:scale(1.1); }
    </style>
</head>
<body>
    <div class="card csat-card mx-3">
        <div class="card-body p-4 p-md-5 text-center">
            @if($alreadyAnswered)
                <i class="fas fa-circle-check text-success" style="font-size:3rem"></i>
                <h4 class="mt-3 mb-2">Ya respondiste esta encuesta</h4>
                <p class="text-muted mb-0">Gracias por tu tiempo.</p>
            @elseif($expired)
                <i class="fas fa-clock text-warning" style="font-size:3rem"></i>
                <h4 class="mt-3 mb-2">Encuesta expirada</h4>
                <p class="text-muted mb-0">Esta encuesta ya no está activa.</p>
            @else
                <h3 class="mb-2">¿Cómo te atendimos?</h3>
                <p class="text-muted mb-4">Tu opinión nos ayuda a mejorar.</p>
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                <form method="POST" action="{{ route('helpdesk.csat.submit', ['token' => $token]) }}">
                    @csrf
                    <div class="csat-stars mb-4" id="csat-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star csat-star" data-rating="{{ $i }}"></i>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" id="rating-input" required>
                    <textarea name="comment" class="form-control mb-3" placeholder="Comentarios (opcional)" rows="3" maxlength="1000"></textarea>
                    <button type="submit" class="btn btn-primary w-100" id="submit-btn" disabled>Enviar valoración</button>
                </form>
            @endif
        </div>
    </div>
    <script>
        const stars = document.querySelectorAll('.csat-star');
        const input = document.getElementById('rating-input');
        const btn = document.getElementById('submit-btn');
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const r = parseInt(star.dataset.rating, 10);
                input.value = r;
                btn.disabled = false;
                stars.forEach(s => s.classList.toggle('active', parseInt(s.dataset.rating, 10) <= r));
            });
        });
    </script>
</body>
</html>
