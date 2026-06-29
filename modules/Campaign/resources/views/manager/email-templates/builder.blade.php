@include('campaign::builder.default', [
    'builderTemplateKind' => 'email',
    'title' => $systemEmailTemplate->name,
    'template' => $systemEmailTemplate->template,
    'saveUrl' => route('manager.page_templates.builder_edit', $systemEmailTemplate->template->uid),
    'changeTemplateUrl' => route('manager.page_templates.change_template', $systemEmailTemplate->template->uid),
    'cancelUrl' => route('manager.email_templates.index'),
    'templates' => collect([]),
    'assetUploadHandler' => route('manager.page_templates.asset_upload'),
])
