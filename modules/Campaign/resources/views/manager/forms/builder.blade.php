@include('campaign::builder.default', [
    'builderTemplateKind' => 'form',
    'title' => $form->name,
    'template' => $form->template,
    'saveUrl' => route('manager.forms.builder_save', $form->uid),
    'changeTemplateUrl' => route('manager.page_templates.change_template', $form->template->uid),
    'cancelUrl' => route('manager.forms.index'),
    'templates' => collect([]),
    'assetUploadHandler' => route('manager.page_templates.asset_upload'),
])
