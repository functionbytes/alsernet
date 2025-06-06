@extends('layouts.core.frontend_dark')

@section('title', trans('messages.automation.create'))

@section('head')
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/anytime.min.js') }}"></script>
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/pickadate/picker.js') }}"></script>
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/pickadate/picker.date.js') }}"></script>

    <!-- Dropzone -->
	<script type="text/javascript" src="{{ AppUrl::asset('core/dropzone/dropzone.js') }}"></script>
    @include('helpers._dropzone_lang')
	<link href="{{ AppUrl::asset('core/dropzone/dropzone.css') }}" rel="stylesheet" type="text/css">

    <link rel="stylesheet" type="text/css" href="{{ AppUrl::asset('core/css/automation.css') }}">
    <script type="text/javascript" src="{{ AppUrl::asset('core/js/automation.js') }}"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"></link>

    <script type="text/javascript" src="{{ AppUrl::asset('core/echarts/echarts.min.js') }}"></script>
    <script type="text/javascript" src="{{ AppUrl::asset('core/echarts/dark.js') }}"></script>

    <script type="text/javascript" src="{{ AppUrl::asset('core/js/group-manager.js') }}"></script>

    <link href="{{ AppUrl::asset('core/emojionearea/emojionearea.min.css') }}" rel="stylesheet">
    <script type="text/javascript" src="{{ AppUrl::asset('core/emojionearea/emojionearea.min.js') }}"></script>
@endsection

@section('menu_title')
    <li class="d-flex align-items-center">
        <div class="d-inline-block d-flex mr-auto align-items-center ml-1">
            <h4 class="my-0 me-2 automation-title">{{ $automation->name }}</h4>
            <i class="material-symbols-rounded">alarm</i>
        </div>
    </li>
@endsection

@section('menu_right')
    <li class="d-flex align-items-center">
        <div class="d-flex align-items-center automation-top-actions">
            <span class="me-4"><i class="last_save_time" data-url="{{ route('Automation2Controller@lastSaved', $automation->uid) }}">{{ trans('messages.automation.designer.last_saved', ['time' => $automation->updated_at->diffForHumans()]) }}</i></span>
            <a href="{{ route('manager.automations') }}" class="action me-4">
                <i class="material-symbols-rounded me-2">arrow_back</i>
                {{ trans('messages.automation.go_back') }}
            </a>

            @if ($automation->getSwitchAutomations(Auth::user()->customer)->count())
                <div class="switch-automation d-flex me-2">
                    <select class="select select2 top-menu-select" name="switch_automation">
                        <option value="--hidden--"></option>
                        @foreach($automation->getSwitchAutomations(Auth::user()->customer)->get() as $auto)
                            <option value='{{ route('Automation2Controller@edit', $auto->uid) }}'>{{ $auto->name }}</option>
                        @endforeach
                    </select>

                    <a href="javascript:'" class="action">
                        <i class="material-symbols-rounded me-2">horizontal_split</i>
                        {{ trans('messages.automation.switch_automation') }}
                    </a>
                </div>
            @endif
        </div>
    </li>

    @include('layouts.core._menu_frontend_user')
@endsection

@section('content')
    <style>
        rect.selected {
            stroke-width: 4 !important;;
        }

        rect.element {
            stroke:black;
            stroke-width:0;
        }

        rect.action {
            fill: rgb(101, 117, 138);
            stroke:  rgb(154 181 214);
        }

        rect.trigger {
            fill: rgba(12, 12, 12, 0.49);
            stroke: #c5c0c0;
        }

        rect.wait {
            fill: #fafafa;
            stroke: #94a0d4;
            stroke-width: 1;
        }

        rect.operation {
            fill: #966089;
            stroke: #d6a6ca;
        }

        g.wait > g > a tspan {
            fill: #666;
        }

        rect.condition {
            fill: #e47a50;
            stroke: #ffb091;
        }

        g text:hover, g tspan:hover {
            fill: pink !important;
        }
    </style>

    <main role="main">
        <div class="automation2">
            <div class="diagram text-center scrollbar-inner">
                <svg id="svg" style="overflow: auto" width="3800" height="6800" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <text x="475" y="30" alignment-baseline="middle" text-anchor="middle">{{ trans('messages.automation.designer.intro') }}</text>

                </svg>

                <div class="history">
                    <a class="history-action history-undo" href="javascript:;">
                        <i class="material-symbols-rounded">undo</i>
                    </a>
                    <a class="history-action history-redo disabled" href="javascript:;">
                        <i class="material-symbols-rounded">redo</i>
                    </a>
                    <a class="history-action history-list" href="javascript:;">
                        <i class="material-symbols-rounded">history</i>
                    </a>
                    <ul class="history-list-items">
                        <li>
                            <a href="" class="d-flex align-items-center current">
                                <i class="material-symbols-rounded me-2">refresh</i>
                                <span class="content mr-auto">Reset current flow</span>
                                {{-- <time class="mini text-muted">1 minute</time> --}}
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="" class="d-flex align-items-center">
                                <i class="material-symbols-rounded me-2">alarm</i>
                                <span class="content mr-auto">Wait activity added</span>
                                {{-- <time class="mini text-muted">3 hours</time> --}}
                            </a>
                        </li>
                        <li>
                            <a href="" class="d-flex align-items-center">
                                <i class="material-symbols-rounded me-2">email</i>
                                <span class="content mr-auto">Send email activity added</span>
                                {{-- <time class="mini text-muted">4 days</time> --}}
                            </a>
                        </li>
                        <li>
                            <a href="" class="d-flex align-items-center">
                                <i class="material-symbols-rounded me-2">call_split</i>
                                <span class="content mr-auto">Condition activity added</span>
                                {{-- <time class="mini text-muted">20 Aug</time> --}}
                            </a>
                        </li>
                        <li>
                            <a href="" class="d-flex align-items-center">
                                <i class="material-symbols-rounded me-2">play_circle_outline</i>
                                <span class="content mr-auto">Trigger criteria setup</span>
                                {{-- <time class="mini text-muted">11 Aug</time> --}}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="sidebar scrollbar-inner">
                <div class="sidebar-content">

                </div>
            </div>
        </div>
    </main>

    <script>
        // timeline popup
        var timelinePopup = new Popup(undefined, undefined, {
            onclose: function() {
                // sidebar.load();
            }
        });

        // popup
        var popup = new Popup(undefined, undefined, {
            onclose: function() {
                sidebar.load();
            }
        });

        var sidebar = new Box($('.sidebar-content'));
        var lastSaved = new Box($('.last_save_time'), $('.last_save_time').attr('data-url'));

        function toggleHistory() {
            var his = $('.history .history-list-items');

            if (his.is(":visible")) {
                his.fadeOut();
            } else {
                his.fadeIn();
            }
        }

        function openBuilder(url) {
            var div = $('<div class="full-iframe-popup">').html('<iframe scrolling="no" class="builder d-none" src="'+url+'"></iframe>');

            $('body').append(div);

            // open builder effects
            addMaskLoading("{{ trans('messages.automation.template.opening_builder') }}");
            $('.builder').on("load", function() {
                removeMaskLoading();

                $(this).removeClass("d-none");
            });
        }

        function openBuilderClassic(url) {
            var div = $('<div class="full-iframe-popup">').html('<iframe scrolling="yes" class="builder d-none" src="'+url+'"></iframe>');

            $('body').append(div);

            // open builder effects
            addMaskLoading("{{ trans('messages.automation.template.opening_builder') }}");
            $('.builder').on("load", function() {
                removeMaskLoading();

                $(this).removeClass("d-none");
            });
        }

        function saveData(callback, extra = {}) {
            if (!(extra instanceof Object)) {
                alert("A hash is required");
                return false;
            }

            if ('data' in extra) {
                alert("data key is not allowed");
                return false;
            }

            var url = '{{ route('Automation2Controller@saveData', $automation->uid) }}';

            var postContent = {
                _token: CSRF_TOKEN,
                data: JSON.stringify(tree.toJson()),
            }

            postContent = {...extra, ...postContent};

            $.ajax({
                url: url,
                type: 'POST',
                data: postContent
            }).always(function() {
                if (callback != null) {
                    callback();
                }

                // update last saved
                lastSaved.load();
            });
        }

        function setAutomationName(name) {
            $('.navbar h1').html(name);
        }

        function SelectActionConfirm(key) {
            var url = '{{ route('Automation2Controller@actionSelectConfirm', $automation->uid) }}' + '?key=' + key;

            popup.load(url, function() {
                // when click confirm select trigger type
                popup.popup.find('#action-select').submit(function(e) {
                    e.preventDefault();

                    var url = $(this).attr('action');
                    var data = $(this).serialize();

                    // show loading effect
                    popup.loading();
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: data,
                    }).always(function(response) {
                        if (response.options.key == 'wait') {
                            var newE = new ElementWait({title: response.title, options: response.options});
                        } else if (response.options.key == 'condition') {
                            var newE = new ElementCondition({title: response.title, options: response.options});
                        }

                        MyAutomation.addToTree(newE);

                        newE.validate();

                        // save tree
                        saveData(function() {
                            // hide popup
                            popup.hide();

                            doSelect(newE);

                            notify({
                                type: 'success',
                                title: '{!! trans('messages.notify.success') !!}',
                                message: response.message
                            });
                        });
                    });
                });
            });
        }

        function EmailSetup(id) {
            var url = '{{ route('Automation2Controller@emailSetup', $automation->uid) }}' + '?action_id=' + id;

            popup.load(url, function() {
            });
        }

        function OpenActionSelectPopup(conditionBranch = null) {
            var hasChildren = false;
            if (conditionBranch == null) {
                hasChildren = tree.getSelected().hasChildren();
            } else if (conditionBranch == 'yes') {
                hasChildren = tree.getSelected().hasChildYes();
            } else if (conditionBranch == 'no') {
                hasChildren = tree.getSelected().hasChildNo();
            }

            popup.load('{{ route('Automation2Controller@actionSelectPupop', $automation->uid) }}?hasChildren=' + hasChildren, function() {
                console.log('Select action popup loaded!');

                // when click on action type
                popup.popup.find('.action-select-but').click(function() {
                    var key = $(this).attr('data-key');
                    // EMAIL
                    if (key == 'send-an-email') {
                        // new action as email
                        var newE = new ElementAction({
                            title: '{{ trans('messages.automation.tree.action_not_set') }}',
                            options: {init: "false"}
                        });

                        // add email to tree
                        MyAutomation.addToTree(newE);

                        // validate
                        newE.validate();

                        // save tree
                        saveData(function() {
                            notify('success', '{{ trans('messages.notify.success') }}', '{{ trans('messages.automation.email.created') }}');
                        });
                    } else {
                        // show select trigger confirm box
                        SelectActionConfirm(key);
                    }
                });
            });
        }

        function OpenTriggerSelectPopup() {
            popup.load('{{ route('Automation2Controller@triggerSelectPupop', $automation->uid) }}', function() {
                console.log('Select trigger popup loaded!');

                // when click on trigger type
                popup.popup.find('.trigger-select-but').click(function() {
                    var key = $(this).attr('data-key');

                    // show select trigger confirm box
                    SelectTriggerConfirm(key);
                });
            });
        }

        function SelectTriggerConfirm(key) {
            var url = '{{ route('Automation2Controller@triggerSelectConfirm', $automation->uid) }}' + '?key=' + key;

            popup.load(url, function() {
                console.log('Confirm trigger type popup loaded!');
            });
        }

        function EditTrigger(url) {
            sidebar.load(url);
        }

        function EditAction(url) {
            sidebar.load(url);
        }

        $(document).ready(function() {
            lastSaved.load();

            // load sidebar
            sidebar.load('{{ route('Automation2Controller@settings', $automation->uid) }}');

            // history toggle
            $('.diagram .history .history-list').click(function() {
                toggleHistory();
            });
            $(document).mouseup(function(e)
            {
                var container = $(".history .history-list-items");

                // if the target of the click isn't the container nor a descendant of the container
                if (!container.is(e.target) && container.has(e.target).length === 0)
                {
                    container.fadeOut();
                }
            });

            // switch automation
            $('[name=switch_automation]').change(function() {
                var val = $(this).val();
                var text = $('[name=switch_automation] option:selected').text();
                var confirm = "{{ trans('messages.automation.switch_automation.confirm') }} <span class='font-weight-semibold'>" + text + "</span>";

                var dialog = new Dialog('confirm', {
                    message: confirm,
                    ok: function(dialog) {
                        window.location = val;
                    },
                    cancel: function() {
                        $('[name=switch_automation]').val('');
                    },
                    close: function() {
                        $('[name=switch_automation]').val('');
                    },
                });
            });
            $('.select2-results__option').each

            // fake history
            $('.diagram .history .history-list-items a, .history .history-undo').click(function(e) {
                e.preventDefault();

                var dialog = new Dialog('alert', {
                    message: '{{ trans('messages.automation.rollback.warning') }}',
                });
            });

            // quota view
            $('.quota-view').click(function(e) {
                e.preventDefault();

                var url = $(this).attr('href');

                popup.load(url, function() {
                    console.log('quota popup loaded!');
                });
            });
        });

        var tree;

        function doSelect(e) {
            // TODO 1:
            // Gọi Ajax to Automation2@action
            // Prams: e.getId()
            // Trả về thông tin chi tiết của action để load nội dung bên phải
            // Trên server: gọi hàm model: Automation2::getActionInfo(id)

            e.select(); // highlight

            // if click on a trigger
            if (e.getType() == 'ElementTrigger') {
                var options = e.getOptions();

                // check if trigger is not init
                if (options.init == "false") {
                    OpenTriggerSelectPopup();
                }
                // trigger was init
                else {
                    var url = '{{ route('Automation2Controller@triggerEdit', $automation->uid) }}' + '?key=' + e.getOptions().key + '&id=' + e.getId();

                    // Open trigger types select list
                    EditTrigger(url);
                }
            }
            // is WAIT
            else if (e.getType() == 'ElementWait') {
                    var url = '{{ route('Automation2Controller@actionEdit', $automation->uid) }}' + '?key=' + e.getOptions().key + '&id=' + e.getId();

                    // Open trigger types select list
                    EditAction(url);
            }
            // is Condition
            else if (e.getType() == 'ElementCondition') {
                    var url = '{{ route('Automation2Controller@actionEdit', $automation->uid) }}' + '?key=' + e.getOptions().key + '&id=' + e.getId();

                    // Open trigger types select list
                    EditAction(url);
            }
            // is Email
            else if (e.getType() == 'ElementAction') {
                if (e.getOptions().init == "true") {
                    var type = $(this).attr('data-type');
                    var url = '{{ route('Automation2Controller@email', $automation->uid) }}?email_uid=' + e.getOptions().email_uid;

                    // Open trigger types select list
                    EditAction(url);
                } else {
                    var type = $(this).attr('data-type');
                    var url = '{{ route('Automation2Controller@email', $automation->uid) }}?email_uid=' + e.getOptions().email_uid;

                    // Open trigger types select list
                    EditAction(url);

                    // show select trigger confirm box
                    EmailSetup(e.getId());
                }
            }
            // is Email
            else if (e.getType() == 'ElementOperation') {
                var type = $(this).attr('data-type');
                var url = '{{ route('Automation2Controller@operationShow', $automation->uid) }}?operation=' + e.getOptions().operation_type + '&id=' + e.getId();

                // Open trigger types select list
                sidebar.load(url);
            }
        }

        function getEmailSetupUrl(email_uid) {
            var url = `{{ route('Automation2Controller@emailSetup', [
                'uid' => $automation->uid,
                'email_uid' => '___email_uid___',
            ]) }}`;

            return url.replace('___email_uid___', email_uid);
        }

        var MyAutomation = {
            // Set the AddElement function
            // It depends on the currently selected element
            addToTree: null
        };

        (function() {
            //var json = [
            //    {title: "Click to choose a trigger", id: "trigger", type: "ElementTrigger", options: {init: false}}
            //];

            @if ($automation->data)
                var json = {!! $automation->getData() !!};
            @else
                var json = [
                    {title: "Click to choose a trigger", id: "trigger", type: "ElementTrigger", options: {init: "false"}}
                ];
            @endif

            var container = document.getElementById('svg');

            tree = AutomationElement.fromJson(json, container, {
                onclick: function(e) {
                    doSelect(e);
                },

                onhover: function(e) {
                    console.log(e.title + " hovered!");
                },

                onadd: function(e) {
                    e.select();

                    MyAutomation.addToTree = function(element) {
                        e.insert(element);
                        e.getTrigger().organize();

                        // select new element
                        doSelect(element);
                    };

                    OpenActionSelectPopup();
                },

                onaddyes: function(e) {
                    e.select();

                    MyAutomation.addToTree = function(element) {
                        e.insertYes(element);
                        e.getTrigger().organize();

                        // select new element
                        doSelect(element);
                    };

                    OpenActionSelectPopup('yes');
                },

                onaddno: function(e) {
                    e.select();

                    MyAutomation.addToTree = function(element) {
                        e.insertNo(element);
                        e.getTrigger().organize();

                        // select new element
                        doSelect(element);
                    };

                    OpenActionSelectPopup('no');
                },

                validate: function(e) {
                    if (e.getType() == 'ElementTrigger') {
                        if (e.getOptions()['init'] == null || !(e.getOptions()['init'] == "true" || e.getOptions()['init'] == true)) {
                            e.showNotice('{{ trans('messages.automation.trigger.is_not_setup') }}');
                            e.setTitle('{{ trans('messages.automation.trigger.is_not_setup.title') }}');
                        } else if (e.getOptions()["key"] == 'say-happy-birthday') {
                            if (!e.getOptions()["field"] || e.getOptions()["field"] == 'date_of_birth') {
                                e.showNotice('{{ trans('messages.automation.trigger.no_date_of_birth_field') }}');
                            } else {
                                // check if current field belongs to automation mail list
                                var cField = e.getOptions()["field"];
                                var fields = {!! json_encode($automation->mailList->getDateOrDateTimeFields()->get()->map(function($field) {
                                    return $field->uid;
                                })->toArray()) !!};

                                if (!fields.includes(cField)) {
                                    e.setOptions($.extend(e.getOptions(), {field: 'date_of_birth'}));
                                    e.showNotice('{{ trans('messages.automation.trigger.no_date_of_birth_field') }}');
                                } else {
                                    e.hideNotice();
                                }
                            }
                        } else {
                            e.hideNotice();
                            // e.setTitle('Correct title goes here');
                        }
                    }

                    if (e.getType() == 'ElementAction') {
                        if (e.getOptions()['init'] == null || !(e.getOptions()['init'] == "true" || e.getOptions()['init'] == true)) {
                            e.showNotice('{{ trans('messages.automation.email.is_not_setup') }}');
                            e.setTitle('{{ trans('messages.automation.email.is_not_setup.title') }}');
                        } else if (e.getOptions()['template'] == null || !(e.getOptions()['template'] == "true" || e.getOptions()['template'] == true)) {
                            e.showNotice('{{ trans('messages.automation.email.has_no_content') }}');
                        } else {
                            e.hideNotice();
                            // e.setTitle('Correct title goes here');
                        }
                    }

                    if (e.getType() == 'ElementCondition') {
                        if     (      e.getOptions()['type'] == null ||
                                 (e.getOptions()['type'] == 'click' && e.getOptions()['email_link'] == null ) ||
                                (e.getOptions()['type'] == 'open' && e.getOptions()['email'] == null ) ||
                                (e.getOptions()['type'] == 'cart_buy_item' && !e.getOptions()['item_id'] )
                            ) {
                            e.showNotice('Condition not set up yet');
                            e.setTitle('Condition not set up yet');
                        } else {
                            e.hideNotice();
                            // e.setTitle('Correct title goes here');
                        }
                    }
                }
            });

            @if (request()->auto_popup)
                doSelect(tree.child);
                setTimeout(function() {
                    popup.load(getEmailSetupUrl(tree.child.getOptions().email_uid),
                    function() {
                        $('.email_title').html('{{ trans('messages.source.abandoned_cart_email') }}');
                    });
                }, 100);

                popup.onHide = function() {
                    if (parent && parent.$('.trans_frame')) {
                        parent.$('.trans_frame').fadeOut();
                    }
                    parent.hidden = true;

                    // parent
                    parent.$('html').css('overflow', 'auto');

                    doSelect(tree.child);
                    setTimeout(function() {
                        popup.load(getEmailSetupUrl(tree.child.getOptions().email_uid),
                        function() {
                            $('.email_title').html('{{ trans('messages.source.abandoned_cart_email') }}');
                        });
                    }, 100);

                    parent.jReload();
                };
            @endif

        })();
    </script>
@endsection
