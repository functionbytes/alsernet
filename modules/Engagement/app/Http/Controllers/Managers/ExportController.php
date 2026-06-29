<?php

namespace Modules\Engagement\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Models\VisitorScore;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.livechat.events.view');
    }

    public function events(Request $request): StreamedResponse
    {
        $request->validate([
            'inbox_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'format' => ['required', 'in:csv,json'],
        ]);

        $from = $request->input('from') ? Carbon::parse($request->input('from')) : now()->subDays(30);
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : now();
        $inboxId = $request->input('inbox_id');

        $query = Event::query()
            ->when($inboxId, fn ($q) => $q->forInbox((int) $inboxId))
            ->whereBetween('occurred_at', [$from, $to])
            ->orderBy('occurred_at');

        $filename = sprintf('events-%s-%s.%s', $from->format('Y-m-d'), $to->format('Y-m-d'), $request->input('format'));

        return $request->input('format') === 'csv'
            ? $this->csvStream($query, $filename, ['id', 'session_token', 'inbox_id', 'event_name', 'platform', 'occurred_at'])
            : $this->jsonStream($query, $filename);
    }

    public function scores(Request $request): StreamedResponse
    {
        $request->validate([
            'inbox_id' => ['nullable', 'integer'],
            'format' => ['required', 'in:csv,json'],
        ]);

        $query = VisitorScore::query()
            ->when($request->input('inbox_id'), fn ($q, $id) => $q->where('inbox_id', (int) $id))
            ->orderByDesc('score');

        $filename = 'scores-'.now()->format('Y-m-d').'.'.$request->input('format');

        return $request->input('format') === 'csv'
            ? $this->csvStream($query, $filename, ['session_token', 'inbox_id', 'customer_id', 'score', 'segment', 'updated_at'])
            : $this->jsonStream($query, $filename);
    }

    private function csvStream($query, string $filename, array $columns): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            $query->chunk(500, function ($rows) use ($handle, $columns) {
                foreach ($rows as $row) {
                    $line = [];
                    foreach ($columns as $col) {
                        $val = $row->{$col};
                        $line[] = $val instanceof Carbon ? $val->toIso8601String() : (string) $val;
                    }
                    fputcsv($handle, $line);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    private function jsonStream($query, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query) {
            echo '[';
            $first = true;
            $query->chunk(500, function ($rows) use (&$first) {
                foreach ($rows as $row) {
                    echo $first ? '' : ',';
                    echo json_encode($row);
                    $first = false;
                }
            });
            echo ']';
        }, $filename, [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }
}
