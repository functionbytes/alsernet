<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Helpdesk\Concerns\FindsLocalizedAutoReply;

class ConversationFarewell extends Model
{
    use FindsLocalizedAutoReply;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_conversation_farewells';

    protected $fillable = [
        'channel',
        'language',
        'message',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
