<?php

declare(strict_types=1);

namespace App\Boost;

use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\CodeEnvironment\CodeEnvironment;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;

class KimiCode extends CodeEnvironment implements McpClient
{
    public function name(): string
    {
        return '.kimi';
    }

    public function displayName(): string
    {
        return 'Kimi Code CLI';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'command -v .kimi',
            ],
            Platform::Windows => [
                'command' => 'where .kimi 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.kimi'],
            'files' => ['.kimi/AGENTS.md'],
        ];
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::SHELL;
    }

    public function shellMcpCommand(): string
    {
        return '.kimi mcp add --transport stdio {key} -- {command} {args}';
    }
}
