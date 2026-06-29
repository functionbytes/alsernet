@include('campaign::builder.default', [
    'builderTemplateKind' => 'page',
    'title' => $step->funnel->name.' — '.$step->name,
    'template' => $step->template,
    'saveUrl' => route('manager.funnels.steps.builder', $step->uid),
    'changeTemplateUrl' => route('manager.page_templates.change_template', $step->template->uid),
    'cancelUrl' => route('manager.funnels.edit', $step->funnel->uid),
    'templates' => collect([]),
    'assetUploadHandler' => route('manager.page_templates.asset_upload'),
])
