<?php

namespace Modules\Campaign\Dto\PageTemplate;

/**
 * DTO para borrado masivo de PageTemplates.
 */
class DeletePageTemplatesDto
{
    /** @var string[] */
    public array $uids;

    public function __construct(array $uids)
    {
        $this->uids = $uids;
    }

    public static function fromRequest(array $data): self
    {
        $raw = $data['uids'] ?? [];
        $uids = is_array($raw) ? $raw : explode(',', (string) $raw);
        $uids = array_values(array_filter(array_map('trim', $uids), fn ($u) => $u !== ''));

        return new self($uids);
    }
}
