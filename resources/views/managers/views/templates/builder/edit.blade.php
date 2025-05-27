<!doctype html>
<html>
  <head>
    <title>Editar plantilla - {{ $template->title }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="{{ url('builder/builder.css') }}">
    <script type="text/javascript" src="{{ url('builder/builder.js') }}" type="text/javascript"></script>

    <link rel="stylesheet" href="{{ url('core/css/builder-custom.css') }}">
    @include('builder.js.widgets')

      <link rel="stylesheet" href="{{ url('core/select2/css/select2.min.css') }}">
    <script type="text/javascript" src="{{ url('core/select2/js/select2.min.js') }}" type="text/javascript"></script>

    <link rel="stylesheet" href="{{ url('core/css/UrlAutoFill.css') }}">
    <script src="{{ url('core/js/UrlAutoFill.js') }}" type="text/javascript"></script>


    @if ($template->theme)
        @include('builder.themes.' . $template->theme)
    @endif

    @include('helpers._builder_form')

    <script>
        var CSRF_TOKEN = "{{ csrf_token() }}";
        var editor;

        var templates = {!! json_encode($templates) !!};

        $( document ).ready(function() {
            editor = new Editor({
                strict: true,
                showHelp: false,
                showInlineToolbar: false,
                emailMode: true,
                lang: {!! json_encode($admin->language->getBuilderLang()) !!},
                url: '{{ route('manager.templates.builder.edit.content', $template->uid) }}',
                backCallback: function() {
                    if (parent.$('.full-iframe-popup').length) {
                        parent.$('.full-iframe-popup').hide();
                        parent.$('body').removeClass('overflow-hidden');
                    }

                    if (parent.$('.listing-form').length) {
                        parent.TemplatesIndex.getList().load();
                    } else {
                        window.location = '{{ route('manager.templates') }}';
                    }
                },
                disableFeatures: [ 'change_template' ],
                uploadAssetUrl: '{{ route('manager.templates.upload.template.assets', $template->uid) }}',
                uploadAssetMethod: 'POST',
                saveUrl: '{{ route('manager.templates.builder.edit', $template->uid) }}',
                saveMethod: 'POST',
                tags: {!! json_encode(App\Models\Template\Template::builderTags((isset($list) ? $list : null))) !!},
                root: '{{ asset('builder') }}/',
                templates: templates,
                filemanager: '{{ asset('filemanager2/dialog.php') }}',
                logo: '{{ getSiteLogoUrl('light') }}',
                backgrounds: [
                    '{{ url('/images/backgrounds/images1.jpg') }}',
                    '{{ url('/images/backgrounds/images2.jpg') }}',
                    '{{ url('/images/backgrounds/images3.jpg') }}',
                    '{{ url('/images/backgrounds/images4.png') }}',
                    '{{ url('/images/backgrounds/images5.jpg') }}',
                    '{{ url('/images/backgrounds/images6.jpg') }}',
                    '{{ url('/images/backgrounds/images9.jpg') }}',
                    '{{ url('/images/backgrounds/images11.jpg') }}',
                    '{{ url('/images/backgrounds/images12.jpg') }}',
                    '{{ url('/images/backgrounds/images13.jpg') }}',
                    '{{ url('/images/backgrounds/images14.jpg') }}',
                    '{{ url('/images/backgrounds/images15.jpg') }}',
                    '{{ url('/images/backgrounds/images16.jpg') }}',
                    '{{ url('/images/backgrounds/images17.png') }}'
                ],
                customInlineEdit: function(container) {
                    thisEditor = this;

                  var tinyconfig = {
                      skin: 'oxide-dark',
                      inline: true,
                      menubar: false,
                      valid_elements : '*[*],meta[*]',
                      valid_children: '+p[ol],+p[ul],+h1[div],+h2[div],+h3[div],+h4[div],+h5[div],+h6[div],+a[div],*[*]',
                      force_br_newlines : false,
                      force_p_newlines : false,
                      forced_root_block : '',
                      inline_boundaries: false,
                      relative_urls: false,
                      convert_urls: false,
                      remove_script_host : false,
                      plugins: 'image link lists autolink',
                      font_formats: "Andale Mono=andale mono,times; Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Book Antiqua=book antiqua,palatino; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; MS Mincho=ms mincho; MS PMincho=ms pmincho; Oswald=oswald; Symbol=symbol; Tahoma=tahoma,arial,helvetica,sans-serif; Terminal=terminal,monaco; Times New Roman=times new roman,times; Trebuchet MS=trebuchet ms,geneva; Verdana=verdana,geneva; Webdings=webdings; Wingdings=wingdings,zapf dingbats",
                      toolbar: [
                      ],
                      external_filemanager_path:'{{ url('/') }}'.replace('/index.php','')+"/filemanager2/",
                      filemanager_title:"Administrador de archivos responsivo" ,
                      external_plugins: { "filemanager" : '{{ url('/') }}'.replace('/index.php','')+"/filemanager2/plugin.min.js"},
                      setup: function (editor) {

                          editor.ui.registry.addMenuButton('menuDateButton', {
                            text: getI18n('editor.insert_tag'),
                            fetch: function (callback) {
                              var items = [];

                              thisEditor.tags.forEach(function(tag) {
                                  if ( tag.type == 'label') {
                                      items.push({
                                          type: 'menuitem',
                                          text: tag.tag.replace("{", "").replace("}", ""),
                                          onAction: function (_) {
                                              if (tag.text) {
                                                  editor.insertContent(tag.text);
                                              } else {
                                                  editor.insertContent(tag.tag);
                                              }
                                          }
                                      });
                                  }
                              });

                              callback(items);
                            }
                          });
                      }
                  };

                  var unsupported_types = 'td, table, img, body';
                  if (!container.is(unsupported_types) && (container.is('[builder-inline-edit]') || !editor.strict)) {
                      container.addClass('builder-class-tinymce');
                      tinyconfig.selector = '.builder-class-tinymce';
                      editor.tinymce = $("#builder_iframe")[0].contentWindow.tinymce.init(tinyconfig);

                      container.removeClass('builder-class-tinymce');
                  }
                },
                loaded: function() {
                    var thisEditor = this;
                    this.addCustomCss('{{ url('/core/css/builder-edit.css') }}');
                }
            });

            editor.addWidget(new RssWidget(), {
                index: 3
            });

            editor.init();

            $(document).on('click', '.filemanager-ok', function(e) {alert('Haga clic en la miniatura para seleccionar la imagen correspondiente.');})
            $(document).on('click', '.filemanager-cancel', function(e) {$('.PopUpCloseButton').click();})


            var urlFill = new UrlAutoFill({!! json_encode($template->urlTagsDropdown()) !!});

        });
    </script>
  </head>
  <body>
        <style>
            .lds-dual-ring {
                display: inline-block;
                width: 80px;
                height: 80px;
            }
            .lds-dual-ring:after {
                content: " ";
                display: block;
                width: 30px;
                height: 30px;
                margin: 4px;
                border-radius: 80%;
                border: 2px solid #aaa;
                border-color: #007bff transparent #007bff transparent;
                animation: lds-dual-ring 1.2s linear infinite;
            }
            @keyframes lds-dual-ring {
                0% {
                    transform: rotate(0deg);
                }
                100% {
                    transform: rotate(360deg);
                }
            }
        </style>
        <div style="text-align: center;
            height: 100vh;
            vertical-align: middle;
            padding: auto;
            display: flex;">
            <div style="margin:auto" class="lds-dual-ring"></div>
        </div>
  </body>
</html>
