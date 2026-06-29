{{--
    Shared pagination footer for McList containers (ported to campaign::).

    McList wires any element with [data-page]. Renders:
      - "Showing X–Y of Z" count on the left (when items->total() > 0)
      - Prev | numbered pages with ellipsis | Next on the right (when lastPage > 1)

    Inputs: $items (Illuminate\Pagination\LengthAwarePaginator)
--}}
@if ($items->total() > 0)
    @php
        $current = $items->currentPage();
        $last = $items->lastPage();
        $window = 1; // pages on each side of current
        $pages = collect();
        $pages->push(1);
        for ($i = max(2, $current - $window); $i <= min($last - 1, $current + $window); $i++) {
            $pages->push($i);
        }
        if ($last > 1) {
            $pages->push($last);
        }
        $pages = $pages->unique()->values();
    @endphp
    <div class="mc-pagination">
        <div class="mc-pagination-summary">
            {!! trans('campaign::page-templates.pagination.showing', [
                'from' => '<strong>'.$items->firstItem().'</strong>',
                'to' => '<strong>'.$items->lastItem().'</strong>',
                'total' => '<strong>'.number_format($items->total()).'</strong>',
            ]) !!}
        </div>
        @if ($last > 1)
        <nav class="mc-pagination-nav" aria-label="{{ trans('campaign::page-templates.pagination.prev') }} / {{ trans('campaign::page-templates.pagination.next') }}">
            <a href="#"
               data-page="{{ max(1, $current - 1) }}"
               class="mc-pagination-btn mc-pagination-prev {{ $current === 1 ? 'is-disabled' : '' }}"
               aria-label="{{ trans('campaign::page-templates.pagination.prev') }}"
               title="{{ trans('campaign::page-templates.pagination.prev') }}"
               @if ($current === 1) tabindex="-1" aria-disabled="true" @endif>
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'chevron-left', 'size' => 16])
            </a>

            @php $prev = 0; @endphp
            @foreach ($pages as $p)
                @if ($p - $prev > 1)
                    <span class="mc-pagination-ellipsis" aria-hidden="true">…</span>
                @endif
                <a href="#"
                   data-page="{{ $p }}"
                   class="mc-pagination-page {{ $p === $current ? 'is-current' : '' }}"
                   @if ($p === $current) aria-current="page" @endif>
                    {{ $p }}
                </a>
                @php $prev = $p; @endphp
            @endforeach

            <a href="#"
               data-page="{{ min($last, $current + 1) }}"
               class="mc-pagination-btn mc-pagination-next {{ $current === $last ? 'is-disabled' : '' }}"
               aria-label="{{ trans('campaign::page-templates.pagination.next') }}"
               title="{{ trans('campaign::page-templates.pagination.next') }}"
               @if ($current === $last) tabindex="-1" aria-disabled="true" @endif>
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'chevron-right', 'size' => 16])
            </a>
        </nav>
        @endif
    </div>
@endif
