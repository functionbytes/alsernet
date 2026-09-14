{{--
    Reusable shimmer skeleton for lazy-loaded contact-360 tab panes.

    Optional vars:
      $rows  int   number of shimmer rows (default 4)
      $cards bool  render card-style blocks instead of plain rows (default false)
--}}
@php
    $rows = $rows ?? 4;
    $cards = $cards ?? false;
@endphp

<div class="contacts-skeleton placeholder-glow" aria-hidden="true">
    @if($cards)
        <div class="row g-3">
            @for($i = 0; $i < $rows; $i++)
                <div class="col-12 col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <span class="placeholder col-5 mb-2 d-block rounded"></span>
                            <span class="placeholder col-8 mb-2 d-block rounded"></span>
                            <span class="placeholder col-6 d-block rounded"></span>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
    @else
        @for($i = 0; $i < $rows; $i++)
            <div class="d-flex align-items-center gap-3 py-3 border-bottom">
                <span class="placeholder rounded-circle p-3"></span>
                <div class="flex-grow-1">
                    <span class="placeholder col-4 mb-2 d-block rounded"></span>
                    <span class="placeholder col-7 d-block rounded"></span>
                </div>
                <span class="placeholder col-2 rounded"></span>
            </div>
        @endfor
    @endif
</div>
