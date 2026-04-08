@if (count($widgetAreas))
    @foreach ($widgetAreas as $item)
        @continue(! class_exists($item->widget_id, false))

        @php
            $widget = new $item->widget_id();
        @endphp

        <li data-id="{{ $widget->getId() }}" data-position="{{ $item->position }}" class="mb-3 widget-item">
            <div class="card">
                <div class="card-header d-flex py-1 px-3 justify-content-between">
                    {{ $widget->getConfig()['name'] }}

                    <button type="button" class="btn btn-sm">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>

            <div class="widget-content" style="display: none;">
                <form method="post">
                    <input name="id" type="hidden" value="{{ $widget->getId() }}">
                    {!! $widget->form($item->sidebar_id, $item->position) !!}
                    <div class="widget-control-actions mt-3 d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary widget-control-delete">
                            {{ trans('widget::widget.delete') }}
                        </button>

                        <button type="button" class="btn btn-primary widget-save">
                            {{ trans('core/base::forms.save_and_continue') }}
                        </button>
                    </div>
                </form>
            </div>
        </li>
    @endforeach
@else
    <li class="dropzone px-1 py-3 text-center">
        <div class="dz-default dz-message">{{ trans('widget::widget.drag_widget_to_sidebar') }}</div>
    </li>
@endif
