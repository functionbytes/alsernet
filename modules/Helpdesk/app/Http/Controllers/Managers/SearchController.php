<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Services\Search\GlobalSearchService;

class SearchController extends Controller
{
    public function __construct(
        private readonly GlobalSearchService $search,
    ) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->string('q'));
        $type = $request->string('type', 'conversations')->toString();

        if (! in_array($type, ['conversations', 'contacts', 'messages'], true)) {
            $type = 'conversations';
        }

        $tooShort = mb_strlen($term) > 0 && mb_strlen($term) < GlobalSearchService::MIN_QUERY_LENGTH;
        $hasQuery = mb_strlen($term) >= GlobalSearchService::MIN_QUERY_LENGTH;

        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|null $results Tipado solo para autocompletado del IDE en las vistas */
        $results = null;

        if ($hasQuery) {
            $results = match ($type) {
                'conversations' => $this->search->paginateConversations($term),
                'contacts' => $this->search->paginateCustomers($term),
                'messages' => $this->search->paginateMessages($term),
            };
        }

        return view('helpdesk::helpdesk.search.index', [
            'term' => $term,
            'type' => $type,
            'tooShort' => $tooShort,
            'hasQuery' => $hasQuery,
            'results' => $results,
            'minLength' => GlobalSearchService::MIN_QUERY_LENGTH,
            'counts' => $hasQuery ? $this->countAll($term) : null,
        ]);
    }

    /**
     * @return array{conversations: int, contacts: int, messages: int}
     */
    private function countAll(string $term): array
    {
        return [
            'conversations' => $this->search->paginateConversations($term, 1)->total(),
            'contacts' => $this->search->paginateCustomers($term, 1)->total(),
            'messages' => $this->search->paginateMessages($term, 1)->total(),
        ];
    }
}
