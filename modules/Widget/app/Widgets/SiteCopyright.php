<?php

namespace Modules\Widget\Widgets;

use Illuminate\Support\Collection;
use Modules\Widget\AbstractWidget;
use Modules\Widget\Forms\WidgetForm;

class SiteCopyright extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => __('Site Copyright'),
            'description' => __('Copyright text at the bottom footer.'),
        ]);

        $widgetDirectory = $this->getWidgetDirectory();

        $this->setFrontendTemplate('widget::widgets.'.$widgetDirectory.'.frontend');
        $this->setBackendTemplate('widget::widgets.'.$widgetDirectory.'.backend');
    }

    protected function settingForm(): WidgetForm|string|null
    {
        $widgetDirectory = $this->getWidgetDirectory();

        if (! view()->exists($this->backendTemplate)) {
            return null;
        }

        return view('widget::widgets.'.$widgetDirectory.'.backend', array_merge([
            'config' => $this->config,
        ], $this->adminConfig()))->render();
    }

    protected function data(): array|Collection
    {
        $copyright = setting('site_copyright', '© '.date('Y').' All rights reserved.');

        return [
            'copyright' => $copyright,
        ];
    }

    public function getWidgetDirectory(): string
    {
        return 'site-copyright';
    }
}
