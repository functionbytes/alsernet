<?php

namespace Modules\Campaign\Dto\PageTemplate;

/**
 * DTO para guardar el contenido del builder de un PageTemplate (JSON + HTML).
 */
class SaveContentDto
{
    public string $uid;

    public string $json;

    public ?string $content;

    public function __construct(string $uid, string $json, ?string $content = null)
    {
        $this->uid = $uid;
        $this->json = $json;
        $this->content = $content;
    }

    public static function fromRequest(string $uid, array $data): self
    {
        return new self(
            uid: $uid,
            json: $data['json'] ?? '',
            content: $data['content'] ?? null,
        );
    }
}
