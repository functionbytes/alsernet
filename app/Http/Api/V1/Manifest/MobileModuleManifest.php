<?php

namespace App\Http\Api\V1\Manifest;

interface MobileModuleManifest
{
    public function alias(): string;

    public function name(): string;

    public function version(): string;

    /** @return string[] */
    public function audiences(): array;

    /** @return array<string, string> */
    public function endpoints(): array;

    /** @return string[] */
    public function requiresAbilities(): array;

    /** @return array<string, mixed> */
    public function featureFlags(): array;
}
