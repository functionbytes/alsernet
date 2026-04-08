<div class="card-body border-bottom">
    <div class="row g-3">
        @foreach($cards as $card)
            <div class="col-md-3">
                <div class="card bg-light-secondary h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">{{ $card['label'] }}</h6>
                        <h4 class="mb-1 fw-bold">{{ $card['value'] }}</h4>
                        <small class="text-muted">{{ $card['subtitle'] }}</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
