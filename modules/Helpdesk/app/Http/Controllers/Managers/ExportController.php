<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Modules\Helpdesk\Http\Requests\Exports\ExportConversationsRequest;
use Modules\Helpdesk\Http\Requests\Exports\ExportCsatRequest;
use Modules\Helpdesk\Http\Requests\Exports\ExportCustomersRequest;
use Modules\Helpdesk\Services\Exports\ConversationsExporter;
use Modules\Helpdesk\Services\Exports\CsatExporter;
use Modules\Helpdesk\Services\Exports\CsvStreamExporter;
use Modules\Helpdesk\Services\Exports\CustomersExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        private readonly CsvStreamExporter $streamer
    ) {}

    public function conversations(ExportConversationsRequest $request): StreamedResponse
    {
        $exporter = new ConversationsExporter($request->validated());

        return $this->streamer->stream(
            'conversaciones-'.now()->format('Ymd').'.csv',
            $exporter->headers(),
            $exporter->rows()
        );
    }

    public function customers(ExportCustomersRequest $request): StreamedResponse
    {
        $exporter = new CustomersExporter($request->validated());

        return $this->streamer->stream(
            'contactos-'.now()->format('Ymd').'.csv',
            $exporter->headers(),
            $exporter->rows()
        );
    }

    public function csat(ExportCsatRequest $request): StreamedResponse
    {
        $exporter = new CsatExporter($request->validated());

        return $this->streamer->stream(
            'csat-'.now()->format('Ymd').'.csv',
            $exporter->headers(),
            $exporter->rows()
        );
    }
}
