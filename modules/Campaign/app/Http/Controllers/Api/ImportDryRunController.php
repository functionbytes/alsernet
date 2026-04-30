<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\Csv\Reader;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\CampaignSubscriber;

class ImportDryRunController extends Controller
{
    public function dryRun(Request $request): JsonResponse
    {
        $data = $request->validate([
            'list_uid' => ['required', 'string', 'exists:campaign_maillists,uid'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
            'mapping' => ['required', 'array'],
            'mapping.email' => ['required', 'string'],
        ]);

        $list = CampaignMaillist::where('uid', $data['list_uid'])->firstOrFail();
        $file = $request->file('file');
        $mapping = $data['mapping'];

        $reader = Reader::createFromPath($file->getRealPath(), 'r');
        $reader->setHeaderOffset(0);
        $records = $reader->getRecords();

        $valid = 0;
        $invalid = 0;
        $duplicates = 0;
        $errors = [];
        $sample = [];

        $seenEmails = [];

        foreach ($records as $offset => $row) {
            $email = $this->extractValue($row, $mapping['email'] ?? 'email');
            $email = CampaignSubscriber::normalizeEmail(trim((string) $email));

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid++;
                $errors[] = "Row {$offset}: invalid email ({$email})";

                continue;
            }

            if (isset($seenEmails[$email])) {
                $duplicates++;
                $errors[] = "Row {$offset}: duplicate email in file ({$email})";

                continue;
            }
            $seenEmails[$email] = true;

            $exists = CampaignSubscriber::where('email', $email)->exists();
            if ($exists) {
                $duplicates++;
            }

            $valid++;

            if (count($sample) < 5) {
                $sample[] = [
                    'email' => $email,
                    'first_name' => $this->extractValue($row, $mapping['first_name'] ?? null),
                    'last_name' => $this->extractValue($row, $mapping['last_name'] ?? null),
                    'already_exists' => $exists,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_rows' => $valid + $invalid + $duplicates,
                'valid' => $valid,
                'invalid' => $invalid,
                'duplicates' => $duplicates,
                'errors' => array_slice($errors, 0, 20),
                'sample' => $sample,
            ],
        ]);
    }

    private function extractValue(array $row, ?string $key): mixed
    {
        if ($key === null) {
            return null;
        }

        return $row[$key] ?? null;
    }
}
