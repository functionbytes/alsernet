<?php

namespace Modules\Mailrelay\Exceptions;

class SyncException extends MailrelayException
{
    public static function failed(string $entity, string $reason): self
    {
        return new self("Sync failed for {$entity}: {$reason}");
    }

    public static function timeout(string $entity): self
    {
        return new self("Sync timed out for {$entity}");
    }

    public static function conflict(string $entity, int $id): self
    {
        return new self("Sync conflict for {$entity} #{$id}: remote version differs");
    }
}
