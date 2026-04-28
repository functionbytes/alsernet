<?php

namespace Modules\CampaignSendingServers\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de un feedback (queja de spam) procesado por un FeedbackLoopHandler.
 *
 * @property int $id
 * @property string $email
 * @property string|null $message_id
 * @property string|null $description
 * @property int|null $feedback_loop_handler_id
 */
class FeedbackLog extends Model
{
    protected $table = 'campaign_sending_server_feedback_logs';

    protected $fillable = [
        'email',
        'message_id',
        'description',
        'feedback_loop_handler_id',
    ];

    public function feedbackLoopHandler()
    {
        return $this->belongsTo(FeedbackLoopHandler::class, 'feedback_loop_handler_id');
    }
}
