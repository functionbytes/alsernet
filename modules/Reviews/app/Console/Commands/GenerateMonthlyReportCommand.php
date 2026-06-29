<?php

namespace Modules\Reviews\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Modules\Reviews\Enums\ReportStatus;
use Modules\Reviews\Mail\ReviewReportMail;
use Modules\Reviews\Models\GeneratedReport;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Services\ReviewReportService;

class GenerateMonthlyReportCommand extends Command
{
    protected $signature = 'reviews:generate-monthly-report {--month=} {--year=}';

    protected $description = 'Generate monthly PDF report for all users with Google connections';

    public function __construct(
        private readonly ReviewReportService $reportService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $month = (int) ($this->option('month') ?: now()->subMonth()->month);
        $year = (int) ($this->option('year') ?: now()->subMonth()->year);

        $this->info("Generating monthly reports for {$year}-{$month}...");

        $userIds = ReviewGoogleConnection::query()
            ->active()
            ->distinct()
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            $this->info('No users with active Google connections found.');

            return self::SUCCESS;
        }

        $users = User::query()->whereIn('id', $userIds)->get();
        $generated = 0;

        foreach ($users as $user) {
            try {
                $filePath = $this->reportService->generateMonthlyPdf($user, $year, $month);

                $report = GeneratedReport::create([
                    'user_id' => $user->id,
                    'report_type' => 'monthly_summary',
                    'format' => 'pdf',
                    'filename' => "reporte-mensual-{$year}-{$month}.pdf",
                    'filepath' => $filePath,
                    'filters' => ['year' => $year, 'month' => $month],
                    'status' => ReportStatus::Completed,
                    'completed_at' => now(),
                ]);

                Mail::to($user->email)->queue(new ReviewReportMail($report, storage_path("app/{$filePath}")));

                $generated++;
                $this->info("Report generated for {$user->email}");
            } catch (\Throwable $e) {
                $this->error("Failed for {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Monthly reports generated for {$generated} users.");

        return self::SUCCESS;
    }
}
