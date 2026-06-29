@include('campaign::builder.default', [
    'builderTemplateKind' => 'email',
    'title' => $customerEmailTemplate->name,
    'template' => $customerEmailTemplate->template,
    'saveUrl' => route('manager.my_email_templates.builder', $customerEmailTemplate->uid),
    'changeTemplateUrl' => route('manager.my_email_templates.change_template', $customerEmailTemplate->uid),
    'cancelUrl' => route('manager.my_email_templates.index'),
    'templates' => collect([]),
    'assetUploadHandler' => route('manager.page_templates.asset_upload'),
])
