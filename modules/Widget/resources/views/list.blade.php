@extends('core::layouts.base')

@section('content')
    @php
        do_action(BASE_ACTION_TOP_FORM_CONTENT_NOTIFICATION, request(), WIDGET_MANAGER_MODULE_SCREEN_NAME);
    @endphp
    <div class="widget-main" id="wrap-widgets">
        <div class="alert alert-info">
            {{ trans('widget::widget.usage_instruction') }}
        </div>

        <div class="row">
            <div class="col-md-6">
                <h2>{{ trans('widget::widget.available') }}</h2>
                <div id="wrap-widget-1" class="row">
                    @foreach (Widget::getWidgets() as $widget)
                        <li data-id="{{ $widget->getId() }}" class="col-md-6 widget-item mb-3">
                            <form method="post">
                                <input name="id" type="hidden" value="{{ $widget->getId() }}">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between p-3">
                                        {{ $widget->getConfig()['name'] }}
                                        <button type="button" class="btn btn-sm d-none">
                                            <i class="ti ti-chevron-down"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="widget-description mt-1">
                                    <small class="text-muted">{{ $widget->getConfig()['description'] }}</small>
                                </div>
                            </form>
                        </li>
                    @endforeach
                </div>
            </div>
            <div class="col-md-6" id="added-widget">
                {!! apply_filters(WIDGET_TOP_META_BOXES, null, WIDGET_MANAGER_MODULE_SCREEN_NAME) !!}
                <div class="row mt-3">
                    @foreach (WidgetGroup::getGroups() as $group)
                        <div class="col-md-6 sidebar-item mb-3" data-id="{{ $group->getId() }}">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title mb-1">{{ $group->getName() }}</h5>
                                        <p class="card-subtitle text-muted mb-0">{{ $group->getDescription() }}</p>
                                    </div>
                                    <button type="button" class="btn btn-sm button-sidebar">
                                        <i class="ti ti-chevron-up"></i>
                                    </button>
                                </div>

                                <div class="card-body">
                                    <ul id="wrap-widget-{{ $loop->index + 2 }}" class="p-1">
                                        @include('widget::item', [
                                            'widgetAreas' => $group->getWidgets(),
                                        ])
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer')
    <script>
        'use strict';
        var BWidget = BWidget || {};
        BWidget.routes = {
            'delete': '{{ route('widgets.destroy', ['ref_lang' => request()->input('ref_lang')]) }}',
            'save_widgets_sidebar': '{{ route('widgets.save_widgets_sidebar', ['ref_lang' => request()->input('ref_lang')]) }}'
        };
    </script>
@endpush
