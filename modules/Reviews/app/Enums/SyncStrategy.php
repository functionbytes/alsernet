<?php

namespace Modules\Reviews\Enums;

enum SyncStrategy: string
{
    case Oauth = 'oauth';
    case PlacesApi = 'places_api';
    case SerpApi = 'serpapi';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Oauth => 'Google Business Profile (OAuth)',
            self::PlacesApi => 'Google Places API',
            self::SerpApi => 'SerpAPI',
            self::Manual => 'Importación manual',
        };
    }

    public function requiresPlaceId(): bool
    {
        return in_array($this, [self::PlacesApi, self::SerpApi]);
    }

    public function requiresOauth(): bool
    {
        return $this === self::Oauth;
    }
}
