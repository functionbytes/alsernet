@include('campaign::builder.default', [
    'builderTemplateKind' => 'page',
    'title' => $pageTemplate->name,
    'template' => $pageTemplate->template,
    'saveUrl' => route('manager.page_templates.builder_edit', $pageTemplate->template->uid),
    'changeTemplateUrl' => route('manager.page_templates.change_template', $pageTemplate->template->uid),
    'cancelUrl' => route('manager.page_templates.index'),
    'templates' => collect([]),
    'assetUploadHandler' => route('manager.page_templates.asset_upload'),
])
