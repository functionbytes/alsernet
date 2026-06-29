<?php

namespace Modules\Campaign\Dto\PageTemplate;

/**
 * DTO para copiar un PageTemplate existente a otro con nuevo nombre.
 */
class CopyPageTemplateDto
{
    public string $sourceUid;

    public string $newName;

    public function __construct(string $sourceUid, string $newName)
    {
        $this->sourceUid = $sourceUid;
        $this->newName = $newName;
    }

    public static function fromRequest(string $sourceUid, array $data): self
    {
        return new self(
            sourceUid: $sourceUid,
            newName: $data['name'],
        );
    }
}
