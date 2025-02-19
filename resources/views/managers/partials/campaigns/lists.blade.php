<?php $index = isset($index) ? $index : '__index__' ?>

@php
dd($lists_segment_group);
@endphp
<div class="condition-line" rel="{{ $index }}">
    <div class="row list-segment-container">
        <div class="col-md-1 d-flex justify-content-center align-items-center ">
            <div class="form-group">
                <div>
                    <input type='hidden' name="lists_segments[{{ $index }}][is_default]" value="false" />
                    @include('helpers.form_control', [
                        'include_blank' => trans('messages.choose'),
                        'type' => 'radio',
                        'name' => 'lists_segments[' . $index . '][is_default]',
                        'label' => '',
                        'popup' => 'Se requiere una lista predeterminada si selecciona más de una lista.',
                        'value' => $lists_segment_group['is_default'],
                        'options' => [['text' => '' , 'value' => 'true']],
                        'rules' => [],
                        'radio_group' => 'campaign_list_info_defaulf',
                    ])
                </div>
            </div>
        </div>
        <div class="col-md-5 list_select_box" target-box="segments-select-box"  segments-url="{{ route('manager.segments.selectBox') }}" >
            @include('helpers.form_control', [
                'name' => 'lists_segments[' . $index . '][mail_list_uid]',
                'class' => 'list-select',
                'include_blank' => trans('messages.choose'),
                'type' => 'select',
                'label' => '¿A qué lista enviaremos?',
                'value' => ($lists_segment_group['list'] ? $lists_segment_group['list']->uid : ""),
                'options' => Auth::user()->readCache('MailListSelectOptions', []),
                'rules' => isset($rules) ? $rules : []
            ])
        </div>
        <div class="col-md-5 segments-select-box multiple">
            @if ($lists_segment_group['list'] && collect($lists_segment_group['list']->readCache('SegmentSelectOptions', []))->count())
                @include('helpers.form_control', [
                    'value' => implode(",", $lists_segment_group['segment_uids']),
                    'type' => 'select',
                    'name' => 'lists_segments[' . $index . '][segment_uids][]',
                    'label' => '¿A qué segmento de la lista enviaremos?',
                    'options' => collect($lists_segment_group['list']->readCache('SegmentSelectOptions', [])),
                    'multiple' => true,
                    'quick_note' => trans('messages.leave_empty_to_choose_all_list')
                ])
            @endif
        </div>
        <div class="col-md-1 d-flex justify-content-center align-items-center">
            <a onclick="$(this).parents('.condition-line').remove()" href="#delete" class="btn btn-light"><span class="material-symbols-rounded">delete_outline</span></a>
        </div>
    </div>
    <hr class="mt-10">
</div>
