@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Crear campaña'])

    <div class="card">
        <ul class="nav nav-pills user-profile-tab" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 active"  href="{{route('manager.campaigns.recipients', $campaign->uid )}}" >
                    <i class="ti ti-user-circle me-2 fs-6"></i>
                    <span class="d-none d-md-block">Destinatario</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3" href="{{route('manager.campaigns.setup', $campaign->uid )}}" >
                    <i class="ti ti-bell me-2 fs-6"></i>
                    <span class="d-none d-md-block">Configuracion</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3" href="{{route('manager.campaigns.template', $campaign->uid )}}"  >
                    <i class="ti ti-article me-2 fs-6"></i>
                    <span class="d-none d-md-block">Plantilla</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3" href="{{route('manager.campaigns.schedule', $campaign->uid )}}" >
                    <i class="ti ti-lock me-2 fs-6"></i>
                    <span class="d-none d-md-block">Cronograma</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3" href="{{route('manager.campaigns.confirm', $campaign->uid )}}" >
                    <i class="ti ti-lock me-2 fs-6"></i>
                    <span class="d-none d-md-block">Confirmacion</span>
                </a>
            </li>
        </ul>
        <div class="card-body">
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade active show" role="tabpanel" tabindex="0">
                    <form id="formRecipients" enctype="multipart/form-data" role="form" onSubmit="return false">
                        {{ csrf_field() }}

                        <input type="hidden" id="uid" name="uid" value="{{$campaign->uid}}">
                        <div class="addable-multiple-form">
                            <div class="addable-multiple-container campaign-list-segments">
                                <?php $num = 0 ?>
                                    @foreach ($campaign->getListsSegmentsGroups() as $index =>  $lists_segment_group)
                                            @include('managers.partials.campaigns.lists', ['lists_segment_group' => $lists_segment_group,'index' => $num,])
                                            <?php $num++ ?>
                                    @endforeach
                            </div>

                            <a sample-url="{{ route('manager.campaigns.list.segment.form', $campaign->uid) }}" href="#add_condition" class="btn btn-secondary add-form">Nueva lista/segmento</a>

                        </div>

                        <hr>

                        <div class="text-end">
                            <button class="btn btn-secondary w-100 ">Guardar y siguiente</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection






@push('scripts')


    <script src="{{ url('core/js/group-manager.js') }}" type="text/javascript"></script>

    <script type="text/javascript">
        Dropzone.autoDiscover = false;

        $(document).ready(function() {
            var CampaignsReciepientsSegment = {
                manager: null,

                rowToGroup: function(row) {
                    return {
                        listSelect: row.find('.list-select'),
                        url: row.find('.list-select').closest('.list_select_box').attr("segments-url"),
                        segmentSelect: row.find('.segments-select-box'),
                        getVal: function() {
                            return row.find('.list-select').val();
                        },
                        index: row.closest('.condition-line').attr('rel')
                    }
                },

                addRow: function(row) {
                    group = this.rowToGroup(row);
                    this.getManager().add(group);
                    this.groupAction(group);
                },

                groupAction: function(group) {
                    group.check = function() {
                        if(group.getVal() !== '') {
                            $.ajax({
                                method: "GET",
                                url: group.url,
                                data: {
                                    list_uid: group.getVal(),
                                    index: group.index
                                }
                            })
                                .done(function( res ) {
                                    group.segmentSelect.html(res);

                                    initJs(group.segmentSelect);
                                });
                        } else {
                            group.segmentSelect.html('');
                        }
                    }

                    group.listSelect.on('change', function() {
                        group.check();
                    });
                },

                getManager: function() {
                    if (this.manager == null) {
                        this.manager = new GroupManager();

                        $('.condition-line').each(function() {
                            var row = $(this);

                            CampaignsReciepientsSegment.addRow(row);
                        });
                    }

                    return this.manager;
                },

                check: function() {
                    this.getManager().groups.forEach(function(group) {
                        group.check();
                    });
                }
            }

            $(function() {
                CampaignsReciepientsSegment.getManager();

                $('.recipients-form').submit(function(e) {
                    if (!$('[radio-group=campaign_list_info_defaulf]:checked').length) {
                        new Dialog('alert', {
                            message: '{{ trans('messages.recipients.select_default_list.warning') }}',
                        });

                        e.preventDefault();
                        return false;
                    }
                });

                // addable multiple form
                $(document).on("click", ".addable-multiple-form .add-form", function(e) {
                    var form = $(this).parents('.addable-multiple-form');
                    var container = form.find('.addable-multiple-container');
                    var status = $(this).attr('automation-status');

                    if(status == 'active') {
                        $('#disable_automation_confirm').modal('show');
                        return;
                    }

                    // ajax update custom sort
                    $.ajax({
                        method: "GET",
                        url: $(this).attr('sample-url'),
                    })
                        .done(function( msg ) {
                            var num = "0";

                            if(container.find('.condition-line').length) {
                                num = parseInt(container.find('.condition-line').last().attr("rel"))+1;
                            }

                            msg = msg.replace(/__index__/g, num);

                            container.append(msg);

                            var new_line = container.find('.condition-line').last();

                            if(new_line.find('.event-campaigns-container').length) {
                                loadAutomationEmail(new_line.find('.event-campaigns-container'));
                            }

                            initJs(new_line);

                            CampaignsReciepientsSegment.addRow(new_line);
                        });
                });

                // radio group check
                $(document).on('change', '[radio-group]', function() {
                    var checked = $(this).is(':checked');
                    var group = $(this).attr('radio-group');

                    if(checked) {
                        $('[radio-group="' + group + '"]').prop('checked', false);
                        $(this).prop('checked', true);
                    }
                });
            });

            function loadAutomationEmail(container) {
                var url = container.attr('data-url');

                $.ajax({
                    method: "GET",
                    url: url
                })
                    .done(function( data ) {
                        container.html(data);
                    });
            }

        });

    </script>

@endpush





