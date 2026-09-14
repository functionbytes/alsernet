<?php

namespace Modules\HelpdeskSocial\Widgets;

use Illuminate\Contracts\View\View;
use Modules\HelpdeskSocial\Models\SocialComment;

class SocialInboxWidget
{
    public function render(): View
    {
        $pending = SocialComment::query()
            ->pending()
            ->notSpam()
            ->orderByDesc('urgency')
            ->orderBy('posted_at')
            ->limit(10)
            ->get();

        $stats = [
            'total_pending' => SocialComment::pending()->notSpam()->count(),
            'total_escalated' => SocialComment::where('status', 'escalated')->count(),
            'today_comments' => SocialComment::whereDate('posted_at', today())->count(),
            'today_replies' => SocialComment::whereDate('replied_at', today())->count(),
        ];

        return view('helpdesksocial::widgets.inbox', [
            'pendingComments' => $pending,
            'stats' => $stats,
        ]);
    }
}
