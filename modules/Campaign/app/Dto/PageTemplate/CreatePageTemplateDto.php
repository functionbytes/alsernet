<?php

namespace Modules\Campaign\Dto\PageTemplate;

/**
 * DTO para crear un PageTemplate a partir de una plantilla base.
 */
class CreatePageTemplateDto
{
    public string $name;

    public string $baseTemplateUid;

    public function __construct(string $name, string $baseTemplateUid)
    {
        $this->name = $name;
        $this->baseTemplateUid = $baseTemplateUid;
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            baseTemplateUid: $data['template'],
        );
    }
}
