<?php

namespace Modules\Reviews\Enums;

enum ReportStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
