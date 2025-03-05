@extends('layouts.core.backend_dark')

@section('title', trans('messages.edit_template'))

@section('head')
    <script type="text/javascript" src="{{ asset('core/tinymce/tinymce.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('core/js/editor.js') }}"></script>

    <script src="{{ asset('core/js/UrlAutoFill.js') }}"></script>
@endsection

@section('menu_title')
    <li class="d-flex align-items-center">
        <div class="d-inline-block d-flex mr-auto align-items-center ml-1 lvl-1">
            <h4 class="my-0 me-2 menu-title text-white">{{ $layout->subject }}</h4>
        </div>
    </li>
@endsection

@section('menu_right')
    <li class="nav-item d-flex align-items-center">
        <a  href="{{ route('manager.layouts') }}"
            class="nav-link py-3 lvl-1 d-flex align-items-center">
            <i class="material-symbols-rounded me-2">arrow_back</i>
            <span>Atras</span>
        </a>
    </li>
    <li class="d-flex align-items-center px-3">
        <button class="btn btn-primary" onclick="$('#classic-builder-form').submit()">
            Guardar
        </button>
    </li>
    <li>
        <a href="{{ route('manager.layouts') }}"
           class="nav-link close-button action black-close-button">
            <i class="material-symbols-rounded">close</i>
        </a>
    </li>
@endsection

@section('content')

    <form id="classic-builder-form" action="{{ route('manager.layouts.update', $layout->uid) }}" method="POST" class="ajax_upload_form form-validate-jquery email-template">

        {{ csrf_field() }}

        <input type="hidden" name="_method" value="PATCH">

        <div class="row mr-0 ml-0">
            <div class="col-md-9 pl-0 pb-0 pr-0 form-group-mb-0">
                <div class="loading classic-loader"><div class="text-center inner"><div class="box-loading"><div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div></div></div></div>

                @include('helpers.form_control', [
                    'class' => 'template-editor form-control',
                    'label' => '',
                    'required' => true,
                    'type' => 'textarea',
                    'name' => 'content',
                    'value' => $layout->content,
                    'rules' => ['content' => 'required']
                ])
            </div>
            <div class="col-md-3 pr-0 pb-0 sidebar pr-4 pt-4 pl-4" style="overflow:auto;background:#f5f5f5">

                @include('helpers.form_control', [
                    'class' => 'form-control',
                    'type' => 'text',
                    'label' => 'Asunto',
                    'name' => 'subject',
                    'value' => $layout->subject,
                    'rules' => ['subject' => 'subject']
                ])

                <div class="col-12">
                    <div class="mb-3">
                        <label for="categories" class="control-label col-form-label">Idiomas</label>
                        <select class="form-control select2" id="categories" multiple="multiple">
                            @foreach($langs as $id => $name)
                                <option value="{{ $id }}" {{ $id == $layout->lang_id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        <label id="categories-error" class="error d-none" for="categories"></label>
                    </div>
                </div>
                    <hr>
                @if (count($layout->tags()) > 0)
                    <div class="tags_list">
                        <label class="text-semibold text-teal mb-2">Etiquetas disponibles:</label>
                        <br />
                        @foreach($layout->tags() as $tag)
                            @if (!$tag["required"])
                                <a  draggable="false" data-popup="tooltip" title='Haga clic para insertar etiqueta' href="javascript:;" class=" insert_tag_button" data-tag-name="{{ $tag["name"] }}">
                                    {{ $tag["name"] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </form>

    <script>

        $('.sidebar').css('height', $(window).height()-53);

        var editor;
        $(document).ready(function() {

            editor = tinymce.init({
                language: 'es',
                selector: '.template-editor',
                directionality: "",
                height: $(window).height()-53,
                convert_urls: false,
                remove_script_host: false,
                skin: "oxide",
                forced_root_block: "",
                plugins: 'fullpage print preview paste importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern noneditable help charmap quickbars emoticons',
                imagetools_cors_hosts: ['picsum.photos'],
                menubar: 'file edit view insert format tools table help',
                toolbar: [
                    'ltr rtl | acelletags | undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify',
                    'outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor codesample | ltr rtl'
                ],
                toolbar_location: 'top',
                menubar: true,
                statusbar: false,
                toolbar_sticky: true,
                valid_elements : '*[*],meta[*]',
                valid_children: '+p[ol],+p[ul],+h1[div],+h2[div],+h3[div],+h4[div],+h5[div],+h6[div],+a[div],*[*]',
                extended_valid_elements : "meta[*]",
                valid_children : "+body[style],+body[meta],+div[h2|span|meta|object],+object[param|embed]",
                external_filemanager_path:APP_URL.replace('/index.php','')+"/filemanager2/",
                filemanager_title:"Administrador de archivos responsivo" ,
                external_plugins: { "filemanager" : APP_URL.replace('/index.php','')+"/filemanager2/plugin.min.js"},
                @if ($layout->type == 'page')
                content_css: [
                    APP_URL.replace('/index.php','')+'/core/css/all.css',
                ],
                body_class : "list-page bg-slate-800",
                @endif
                setup: function (editor) {

                    editor.ui.registry.addMenuButton('acelletags', {
                        text: 'Insertar etiquetas personalizadas',
                        fetch: function (callback) {
                            var items = [];

                            @foreach(App\Models\Template\Template::tags() as $tag)
                            items.push({
                                type: 'menuitem',
                                text: '{{ "{".$tag["name"]."}" }}',
                                onAction: function (_) {
                                    editor.insertContent('{{ "{".$tag["name"]."}" }}');
                                }
                            });
                            @endforeach

                            callback(items);
                        }
                    });

                    editor.on('init', function(e) {
                        $('.classic-loader').remove();
                    });
                }
            });
        });

        $('#classic-builder-form').submit(function(e) {
            e.preventDefault();
            tinyMCE.triggerSave();

            var data = $(this).serialize();
            var url = $(this).attr('action');

            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                statusCode: {
                    // validate error
                    400: function (res) {
                        // notify
                        notify('error', 'Error', JSON.parse(res.responseText).message);
                    }
                },
                success: function (response) {
                    window.location = response.url
                }
            });
        });
    </script>

    <script>
        $(function() {
            $(document).on("click", ".insert_tag_button", function() {
                var tag = $(this).attr("data-tag-name");

                if($('textarea[name="html"]').length || $('textarea[name="content"]').length) {
                    tinymce.activeEditor.execCommand('mceInsertContent', false, tag);
                } else {
                    speechSynthesis;
                    $('textarea[name="plain"]').val($('textarea[name="plain"]').val()+tag);
                }
            });
        });
    </script>
@endsection
