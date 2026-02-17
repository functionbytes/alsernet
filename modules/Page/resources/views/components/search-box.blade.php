@props([
    'placeholder' => 'Search pages...',
    'action' => route('pages.index'),
    'value' => request('search', ''),
    'apiEndpoint' => '/api/v1/pages/search/quick',
    'minLength' => 2,
    'debounceMs' => 300,
])

<div x-data="searchBox({
    apiEndpoint: '{{ $apiEndpoint }}',
    minLength: {{ $minLength }},
    debounceMs: {{ $debounceMs }},
})" class="search-box-wrapper">
    <form method="GET" action="{{ $action }}" @submit.prevent="handleSubmit" class="search-box-form">
        <div class="relative">
            <input
                type="text"
                name="search"
                x-model="searchQuery"
                @input="handleInput"
                @focus="showResults = true"
                @keydown.escape="showResults = false"
                @keydown.arrow-down.prevent="navigateDown"
                @keydown.arrow-up.prevent="navigateUp"
                @keydown.enter.prevent="selectResult"
                placeholder="{{ $placeholder }}"
                value="{{ $value }}"
                class="search-box-input w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                autocomplete="off"
            />

            <!-- Search Icon -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>

            <!-- Loading Spinner -->
            <div x-show="isLoading" class="absolute inset-y-0 right-10 flex items-center pr-3">
                <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <!-- Results Dropdown -->
        <div
            x-show="showResults && (results.length > 0 || (searchQuery.length >= minLength && !isLoading && results.length === 0))"
            @click.away="showResults = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="absolute z-50 mt-2 w-full bg-white rounded-lg shadow-lg border border-gray-200 max-h-96 overflow-y-auto"
        >
            <!-- Results -->
            <template x-if="results.length > 0">
                <ul class="py-2">
                    <template x-for="(result, index) in results" :key="result.id">
                        <li>
                            <a
                                :href="result.url"
                                @mouseenter="selectedIndex = index"
                                :class="{'bg-blue-50': selectedIndex === index}"
                                class="block px-4 py-3 hover:bg-blue-50 transition-colors cursor-pointer border-b border-gray-100 last:border-0"
                            >
                                <div class="font-medium text-gray-900" x-html="result.highlighted_title || result.title"></div>
                                <div class="text-sm text-gray-500 mt-1" x-text="result.slug"></div>
                            </a>
                        </li>
                    </template>
                </ul>
            </template>

            <!-- No Results -->
            <template x-if="results.length === 0 && searchQuery.length >= minLength && !isLoading">
                <div class="px-4 py-8 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="mt-2">No pages found</p>
                    <p class="text-sm mt-1">Try a different search term</p>
                </div>
            </template>

            <!-- View All Results Link -->
            <template x-if="results.length > 0">
                <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
                    <button type="submit" class="text-sm text-blue-600 hover:text-blue-800 font-medium w-full text-center">
                        View all results for "<span x-text="searchQuery"></span>"
                    </button>
                </div>
            </template>
        </div>
    </form>
</div>

@push('scripts')
<script>
function searchBox(config) {
    return {
        searchQuery: '',
        results: [],
        showResults: false,
        isLoading: false,
        selectedIndex: -1,
        debounceTimer: null,
        apiEndpoint: config.apiEndpoint,
        minLength: config.minLength,
        debounceMs: config.debounceMs,

        init() {
            // Initialize with current search value if exists
            this.searchQuery = '{{ $value }}';
        },

        handleInput() {
            // Clear previous timer
            clearTimeout(this.debounceTimer);

            // Reset results if query is too short
            if (this.searchQuery.length < this.minLength) {
                this.results = [];
                this.showResults = false;
                return;
            }

            // Debounce the search
            this.debounceTimer = setTimeout(() => {
                this.performSearch();
            }, this.debounceMs);
        },

        async performSearch() {
            if (this.searchQuery.length < this.minLength) {
                return;
            }

            this.isLoading = true;

            try {
                const response = await fetch(`${this.apiEndpoint}?q=${encodeURIComponent(this.searchQuery)}`);
                const data = await response.json();

                if (data.success) {
                    this.results = data.data || [];
                    this.showResults = true;
                    this.selectedIndex = -1;
                }
            } catch (error) {
                console.error('Search error:', error);
                this.results = [];
            } finally {
                this.isLoading = false;
            }
        },

        navigateDown() {
            if (this.selectedIndex < this.results.length - 1) {
                this.selectedIndex++;
            }
        },

        navigateUp() {
            if (this.selectedIndex > 0) {
                this.selectedIndex--;
            }
        },

        selectResult() {
            if (this.selectedIndex >= 0 && this.selectedIndex < this.results.length) {
                window.location.href = this.results[this.selectedIndex].url;
            } else {
                this.handleSubmit();
            }
        },

        handleSubmit() {
            if (this.searchQuery.length >= this.minLength) {
                const form = this.$el.querySelector('form');
                form.submit();
            }
        }
    };
}
</script>
@endpush

@push('styles')
<style>
.search-box-wrapper {
    position: relative;
    width: 100%;
}

.search-box-input::placeholder {
    color: #9ca3af;
}

.search-box-input:focus {
    outline: none;
}

mark {
    background-color: #fef3c7;
    color: #92400e;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-weight: 500;
}
</style>
@endpush
