<?php

namespace Modules\Reviews\Enums;

enum ReviewSource: string
{
    case BusinessProfile = 'business_profile';
    case PlacesApi = 'places_api';
    case SerpApi = 'serpapi';
    case Csv = 'csv';
    case Manual = 'manual';
}
