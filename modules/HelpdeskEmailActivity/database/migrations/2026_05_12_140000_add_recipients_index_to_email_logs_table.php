<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        if (! Schema::hasColumn('email_logs', 'recipients_index')) {
            Schema::table('email_logs', function (Blueprint $table) {
                $table->text('recipients_index')->nullable()->after('bcc_addresses');
            });
        }

        $this->backfillRecipientsIndex();

        $this->addIndexIfMissing('email_logs_recipients_index_fulltext', function (Blueprint $table) {
            $table->fullText('recipients_index', 'email_logs_recipients_index_fulltext');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table) {
            if ($this->indexExists('email_logs_recipients_index_fulltext')) {
                $table->dropFullText('email_logs_recipients_index_fulltext');
            }

            if (Schema::hasColumn('email_logs', 'recipients_index')) {
                $table->dropColumn('recipients_index');
            }
        });
    }

    private function backfillRecipientsIndex(): void
    {
        DB::table('email_logs')
            ->whereNull('recipients_index')
            ->select(['id', 'to_addresses', 'cc_addresses', 'bcc_addresses'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $emails = collect([$row->to_addresses, $row->cc_addresses, $row->bcc_addresses])
                        ->flatMap(fn ($json) => is_array($decoded = json_decode((string) ($json ?? '[]'), true)) ? $decoded : [])
                        ->filter()
                        ->implode(' ');

                    DB::table('email_logs')->where('id', $row->id)->update(['recipients_index' => $emails]);
                }
            });
    }

    private function addIndexIfMissing(string $name, callable $callback): void
    {
        if (! $this->indexExists($name)) {
            Schema::table('email_logs', $callback);
        }
    }

    private function indexExists(string $name): bool
    {
        return collect(Schema::getIndexes('email_logs'))->contains(fn ($index) => $index['name'] === $name);
    }
};
