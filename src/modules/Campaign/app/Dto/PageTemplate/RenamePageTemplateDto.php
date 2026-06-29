<?php

namespace Modules\Campaign\Dto\PageTemplate;

/**
 * DTO para renombrar un PageTemplate existente.
 */
class RenamePageTemplateDto
{
    public string $uid;

    public string $newName;

    public function __construct(string $uid, string $newName)
    {
        $this->uid = $uid;
        $this->newName = $newName;
    }

    public static function fromRequest(string $uid, array $data): self
    {
        return new self(
            uid: $uid,
            newName: $data['name'],
        );
    }
}
