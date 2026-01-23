<?php

namespace Modules\HelpdeskChat\Services\Contacts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\HelpdeskChat\Models\Contacts\Contact;

class ContactImportService
{
    /**
     * Parse CSV file and return headers and sample data.
     */
    public function parseCSV(string $filePath): array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \Exception('Could not open file');
        }

        // Read header row
        $headers = fgetcsv($handle);

        if ($headers === false || empty($headers)) {
            fclose($handle);
            throw new \Exception('CSV file is empty or has no headers');
        }

        // Clean headers
        $headers = array_map('trim', $headers);

        // Read sample rows (first 5 for preview)
        $sampleRows = [];
        $count = 0;

        while (($row = fgetcsv($handle)) !== false && $count < 5) {
            $sampleRows[] = array_combine($headers, $row);
            $count++;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'sample_rows' => $sampleRows,
            'total_rows' => $this->countCSVRows($filePath) - 1, // Exclude header
        ];
    }

    /**
     * Count total rows in CSV file.
     */
    protected function countCSVRows(string $filePath): int
    {
        $count = 0;
        $handle = fopen($filePath, 'r');

        while (fgets($handle) !== false) {
            $count++;
        }

        fclose($handle);

        return $count;
    }

    /**
     * Preview import with field mapping.
     */
    public function previewImport(string $filePath, array $fieldMapping, int $accountId, array $options = []): array
    {
        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle);
        $headers = array_map('trim', $headers);

        $preview = [
            'valid' => [],
            'invalid' => [],
            'duplicates' => [],
            'stats' => [
                'total' => 0,
                'valid' => 0,
                'invalid' => 0,
                'duplicates' => 0,
                'new' => 0,
                'updates' => 0,
            ],
        ];

        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $preview['stats']['total']++;

            $data = array_combine($headers, $row);
            $mappedData = $this->mapFields($data, $fieldMapping);

            // Validate
            $validator = Validator::make($mappedData, [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone_number' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                $preview['invalid'][] = [
                    'row' => $rowNumber,
                    'data' => $mappedData,
                    'errors' => $validator->errors()->toArray(),
                ];
                $preview['stats']['invalid']++;

                continue;
            }

            // Check for duplicates
            $existing = $this->findExistingContact($accountId, $mappedData);

            if ($existing) {
                $preview['duplicates'][] = [
                    'row' => $rowNumber,
                    'data' => $mappedData,
                    'existing_contact' => [
                        'id' => $existing->id,
                        'name' => $existing->name,
                        'email' => $existing->email,
                        'phone_number' => $existing->phone_number,
                    ],
                    'action' => $options['update_existing'] ?? false ? 'update' : 'skip',
                ];
                $preview['stats']['duplicates']++;

                if ($options['update_existing'] ?? false) {
                    $preview['stats']['updates']++;
                }
            } else {
                $preview['valid'][] = [
                    'row' => $rowNumber,
                    'data' => $mappedData,
                ];
                $preview['stats']['valid']++;
                $preview['stats']['new']++;
            }

            // Limit preview to first 100 rows
            if ($rowNumber > 100) {
                break;
            }
        }

        fclose($handle);

        return $preview;
    }

    /**
     * Process import with options.
     */
    public function processImport(string $filePath, array $fieldMapping, int $accountId, array $options = []): array
    {
        return DB::transaction(function () use ($filePath, $fieldMapping, $accountId, $options) {
            $handle = fopen($filePath, 'r');
            $headers = fgetcsv($handle);
            $headers = array_map('trim', $headers);

            $results = [
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];

            $labelIds = $options['label_ids'] ?? [];
            $updateExisting = $options['update_existing'] ?? false;

            while (($row = fgetcsv($handle)) !== false) {
                try {
                    $data = array_combine($headers, $row);
                    $mappedData = $this->mapFields($data, $fieldMapping);

                    // Validate
                    $validator = Validator::make($mappedData, [
                        'name' => 'required|string|max:255',
                        'email' => 'nullable|email|max:255',
                        'phone_number' => 'nullable|string|max:50',
                    ]);

                    if ($validator->fails()) {
                        $results['errors']++;

                        continue;
                    }

                    // Check for existing contact
                    $existing = $this->findExistingContact($accountId, $mappedData);

                    if ($existing && $updateExisting) {
                        // Update existing contact
                        $existing->update($mappedData);
                        $results['updated']++;

                        if (! empty($labelIds)) {
                            $existing->labels()->syncWithoutDetaching($labelIds);
                        }
                    } elseif ($existing) {
                        // Skip duplicate
                        $results['skipped']++;
                    } else {
                        // Create new contact
                        $contact = Contact::create(array_merge(
                            ['account_id' => $accountId],
                            $mappedData
                        ));
                        $results['imported']++;

                        if (! empty($labelIds)) {
                            $contact->labels()->attach($labelIds);
                        }
                    }
                } catch (\Exception $e) {
                    $results['errors']++;
                }
            }

            fclose($handle);

            return $results;
        });
    }

    /**
     * Map CSV fields to contact fields.
     */
    protected function mapFields(array $data, array $fieldMapping): array
    {
        $mapped = [];

        foreach ($fieldMapping as $csvColumn => $contactField) {
            if ($contactField && isset($data[$csvColumn])) {
                $value = trim($data[$csvColumn]);

                if ($value !== '') {
                    $mapped[$contactField] = $value;
                }
            }
        }

        return $mapped;
    }

    /**
     * Find existing contact by email or phone.
     */
    protected function findExistingContact(int $accountId, array $data): ?Contact
    {
        $query = Contact::where('account_id', $accountId);

        if (! empty($data['email'])) {
            $query->where('email', $data['email']);

            return $query->first();
        }

        if (! empty($data['phone_number'])) {
            $query->where('phone_number', $data['phone_number']);

            return $query->first();
        }

        return null;
    }

    /**
     * Get available contact fields for mapping.
     */
    public function getAvailableFields(): array
    {
        return [
            'name' => ['label' => 'Name', 'required' => true],
            'email' => ['label' => 'Email', 'required' => false],
            'phone_number' => ['label' => 'Phone Number', 'required' => false],
            'company_name' => ['label' => 'Company Name', 'required' => false],
            'bio' => ['label' => 'Bio', 'required' => false],
            'city' => ['label' => 'City', 'required' => false],
            'country' => ['label' => 'Country', 'required' => false],
        ];
    }

    /**
     * Generate CSV template for download.
     */
    public function generateTemplate(): string
    {
        $fields = array_keys($this->getAvailableFields());
        $filename = storage_path('app/temp/contact_import_template.csv');

        // Ensure temp directory exists
        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $handle = fopen($filename, 'w');
        fputcsv($handle, $fields);

        // Add sample row
        fputcsv($handle, [
            'John Doe',
            'john@example.com',
            '+1234567890',
            'Acme Corp',
            'Sample bio',
            'New York',
            'USA',
        ]);

        fclose($handle);

        return $filename;
    }
}
