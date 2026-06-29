<?php

namespace Modules\Document\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentProduct;
use Modules\Document\Entities\DocumentSource;
use Modules\Document\Entities\DocumentStatus;
use Modules\Document\Entities\DocumentType;
use Modules\Document\Services\DocumentTypeService;

class MigrateRequestDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-request-documentss {--force : Skip confirmation} {--limit= : Limit number of documents to migrate} {--uid= : Migrate and sync specific document by UID} {--type= : Migrate and sync documents of specific type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate documents from webadmin_mysql with products from Prestashop, media from legacy, and customer data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Confirmation for full migration
        if (! $this->option('force')) {
            if (! $this->confirm('This will delete existing documents and migrate fresh data. Continue?')) {
                return;
            }
        }

        try {
            $this->info('🔄 Starting complete document migration...');
            $this->line('');

            // Step 0: Clean existing data and reset autoincrement
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('📍 STEP 0: Cleaning existing data');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line('');
            $this->cleanExistingData();
            $this->line('');

            // Step 1: Migrate documents (includes products, media, and customer data from Prestashop)
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('📍 STEP 1: Migrating documents from webadmin_mysql');
            $this->info('   (includes products from Prestashop, media, and customer data)');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line('');
            $migrationStats = $this->migrateDocuments();

            // Final Summary
            $this->line('');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('✅ MIGRATION COMPLETE');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            $this->line('');
            $this->info('📄 Documents Migrated (with products, media & customer data):');
            $this->info("  ✓ Migrated: {$migrationStats['migrated']}");
            $this->warn("  ⊘ Skipped: {$migrationStats['skipped']}");
            $this->info("  Total: {$migrationStats['total']}");

            $this->line('');
            $this->comment('💡 Next steps:');
            $this->comment('  • Review migrated documents in /settings/documents/');

        } catch (\Exception $e) {
            $this->error('❌ Migration failed: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return 1;
        }

        return 0;
    }

    /**
     * Sync a single document by UID
     */
    private function syncSingleDocument(string $uid): int
    {
        $this->info("🔄 Synchronizing document: {$uid}");
        $this->line('');

        $document = Document::where('uid', $uid)->first();

        if (! $document) {
            $this->error("❌ Document not found: {$uid}");

            return 1;
        }

        try {
            $this->info('📄 Document found:');
            $this->info("  • UID: {$document->uid}");
            $this->info("  • Type: {$document->documentType?->name}");
            $this->info("  • Customer: {$document->customer_email}");
            $this->line('');

            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('📍 Syncing document fields');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            if ($this->syncDocument($document, true)) {
                $this->line('');
                $this->info('✅ Synchronization complete!');
                $this->info('  • required_documents: '.json_encode($document->required_documents));
                $this->info('  • uploaded_documents: '.count($document->uploaded_documents ?? []).' files');

                return 0;
            } else {
                $this->error('❌ Synchronization failed');

                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Sync documents by type
     */
    private function syncDocumentsByType(string $type): int
    {
        $this->info("🔄 Synchronizing documents of type: {$type}");
        $this->line('');

        $documents = Document::whereHas('documentType', function ($query) use ($type) {
            $query->where('slug', $type);
        })->get();

        if ($documents->isEmpty()) {
            $this->error("❌ No documents found of type: {$type}");

            return 1;
        }

        $this->info("Found {$documents->count()} document(s) of type: {$type}");
        $this->line('');

        $bar = $this->output->createProgressBar($documents->count());
        $bar->start();

        $synced = 0;
        $skipped = 0;

        foreach ($documents as $document) {
            if ($this->syncDocument($document, false)) {
                $synced++;
            } else {
                $skipped++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->line('');
        $this->info('✅ Synchronization complete!');
        $this->info("  ✓ Synced: {$synced}");
        $this->warn("  ⊘ Skipped: {$skipped}");

        return 0;
    }

    /**
     * Migrate documents from webadmin_mysql database
     */
    private function migrateDocuments(): array
    {
        $sourceDb = DB::connection('webadmin_mysql');
        $sourceDb->getPdo(); // Test connection

        // Get type mappings
        $typeMap = DocumentType::pluck('id', 'slug')->toArray();
        $sourceMap = DocumentSource::pluck('id', 'key')->toArray();
        $statusMap = DocumentStatus::pluck('id', 'key')->toArray();

        // Get available lang IDs to validate against
        $validLangIds = DB::connection('mysql')->table('langs')->pluck('id')->toArray();

        $defaultStatusId = $statusMap['awaiting_documents'] ?? 2;

        $this->info('📊 Querying source database...');

        $query = $sourceDb->table('request_documents')->orderBy('id');

        // Apply limit if specified
        if ($this->option('limit')) {
            $limit = (int) $this->option('limit');
            $query->limit($limit);
            $this->info("Limiting to {$limit} documents");
        }

        $sourceRecords = $query->get();

        $total = $sourceRecords->count();
        $this->info("Found {$total} records to migrate");

        if ($total === 0) {
            $this->warn('No records found to migrate');

            return ['migrated' => 0, 'skipped' => 0, 'total' => 0];
        }

        $this->line('');

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $migrated = 0;
        $skipped = 0;
        $errors = [];
        $statusStats = [
            'awaiting_documents' => 0,
            'received' => 0,
            'approved' => 0,
        ];

        foreach ($sourceRecords as $source) {
            try {
                // Initialize with empty values (will be fetched from Prestashop)
                $customerFirstname = null;
                $customerLastname = null;
                $customerEmail = null;
                $customerDni = null;
                $customerCompany = null;
                $validationUpload = null;
                $langId = 1; // Default lang_id
                $orderReference = null;
                $orderDate = null;

                $typeSlug = strtolower($source->type ?? '');

                // Map type names (gun -> corta, etc)
                $typeMappings = ['gun' => 'corta'];
                if (isset($typeMappings[$typeSlug])) {
                    $typeSlug = $typeMappings[$typeSlug];
                }

                if (! isset($typeMap[$typeSlug])) {
                    $errors[] = "Record {$source->id}: Unknown type '{$source->type}'";
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                $documentTypeId = $typeMap[$typeSlug];

                $statusId = $defaultStatusId;
                $validationStatus = 'pending';

                if (isset($source->upload_at) && $source->upload_at) {
                    // Document has been uploaded
                    if ($source->proccess === 1 || $source->proccess === '1') {
                        // Approved
                        $statusId = $statusMap['approved'] ?? 5;
                        $validationStatus = 'approved';
                        $validationUpload = 1;
                    } else {
                        // Uploaded but not approved yet
                        $statusId = $statusMap['received'] ?? 3;
                        $validationStatus = 'received';
                        $validationUpload = 0;
                    }
                } else {
                    // Uploaded but not approved yet
                    $statusId = $statusMap['pending'] ?? 3;
                    $validationStatus = 'pending';
                    $validationUpload = 0;
                }

                // Fetch customer data if customer_id exists (using MySQL CLI to bypass IP restrictions)
                if ($source->customer_id) {
                    try {
                        $customerData = $this->fetchPrestashopCustomer($source->customer_id);
                        if ($customerData) {
                            $customerFirstname = $customerData['firstname'];
                            $customerLastname = $customerData['lastname'];
                            $customerEmail = $customerData['email'];
                            $customerCompany = $customerData['company'];
                            $customerDni = $customerData['vat_number'];
                        }
                    } catch (\Exception $e) {
                        // Continue - Prestashop data might not be available
                    }
                }

                // Fetch order data if order_id exists
                if ($source->order_id) {
                    try {
                        $orderData = $this->fetchPrestashopOrder($source->order_id);

                        // If order doesn't exist in Prestashop, skip this document
                        if (! $orderData) {
                            $errors[] = "Record {$source->id}: Order {$source->order_id} not found in Prestashop";
                            $skipped++;
                            $bar->advance();

                            continue;
                        }

                        // Update with order data
                        if ($orderData['id_lang']) {
                            // Validate that the lang_id exists in our langs table
                            if (in_array($orderData['id_lang'], $validLangIds)) {
                                $langId = $orderData['id_lang'];
                            } else {
                                // If lang doesn't exist, use default (Español = 1)
                                $langId = 1;
                            }
                        }
                        if ($orderData['reference']) {
                            $orderReference = $orderData['reference'];
                        }
                        if ($orderData['date_add']) {
                            $orderDate = $orderData['date_add'];
                        }
                    } catch (\Exception $e) {
                        // Continue - Order data might not be available
                    }
                }

                $documentData = [
                    'id' => $source->id,
                    'uid' => $source->uid,
                    'type_id' => $documentTypeId,
                    'source_id' => 3,
                    'sync_id' => 2,
                    'load_id' => 2,
                    'upload_id' => 1, // Always automatic (1) for migrations
                    'status_id' => $statusId,
                    'validation_status' => $validationStatus,
                    'lang_id' => $langId,
                    'customer_id' => $source->customer_id ?? null,
                    'customer_firstname' => $customerFirstname,
                    'customer_lastname' => $customerLastname,
                    'customer_email' => $customerEmail,
                    'customer_dni' => $customerDni,
                    'customer_company' => $customerCompany,
                    'order_id' => $source->order_id ?? null,
                    'order_reference' => $orderReference ?? $source->order_reference ?? null,
                    'order_date' => $orderDate ?? $source->order_date ?? null,
                    'cart_id' => $source->cart_id ?? null,
                    'confirmed_at' => $source->upload_at ?? null,
                    'reminder_at' => $source->reminder_at ?? null,
                    'current_stage' => 1,
                    'total_stages' => 1,
                    'created_at' => $source->created_at,
                    'updated_at' => $source->updated_at,
                ];

                $document = Document::updateOrCreate(
                    ['uid' => $source->uid],
                    $documentData
                );

                $migrated++;

                // Create products from Prestashop order immediately
                if ($source->order_id && $document) {
                    try {
                        $this->createDocumentProductsFromPrestashop($document, $source->order_id);
                    } catch (\Exception $e) {
                        // Log error but don't fail the document migration
                        $errors[] = "Document {$source->uid}: Product creation failed - {$e->getMessage()}";
                    }
                }

                // Migrate media from legacy database immediately
                if ($document) {
                    try {
                        $this->createDocumentMediaFromLegacy($document, $source->id);
                    } catch (\Exception $e) {
                        // Silently fail - media migration is not critical for document creation
                        // $errors[] = "Document {$source->uid}: Media migration failed - {$e->getMessage()}";
                    }
                }

                $statusKey = array_search($statusId, $statusMap);
                if ($statusKey && isset($statusStats[$statusKey])) {
                    $statusStats[$statusKey]++;
                }
            } catch (\Exception $e) {
                $errors[] = "Record {$source->id}: {$e->getMessage()}";
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if (! empty($errors)) {
            $this->line('');
            $this->warn('⚠️  Errors encountered:');
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->error("  • {$error}");
            }
            if (count($errors) > 10) {
                $this->error('  ... and '.(count($errors) - 10).' more');
            }
        }

        return [
            'migrated' => $migrated,
            'skipped' => $skipped,
            'total' => $total,
            'statusStats' => $statusStats,
        ];
    }

    /**
     * Migrate document media files metadata
     */
    private function migrateMedia(): array
    {
        try {
            $sourceDb = DB::connection('webadmin_mysql');
            $sourceDb->getPdo(); // Test connection

            $this->info('📊 Querying source database...');

            // Get all media records for documents
            $sourceRecords = $sourceDb->table('media')
                ->where('model_type', 'App\\Models\\Order\\Document')
                ->orderBy('id')
                ->get();

            $total = $sourceRecords->count();
            $this->info("Found {$total} media records to migrate");

            if ($total === 0) {
                $this->warn('No records found to migrate');

                return ['migrated' => 0, 'skipped' => 0, 'total' => 0];
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $migrated = 0;
            $skipped = 0;
            $errors = [];

            foreach ($sourceRecords as $source) {
                try {
                    // Get the UID from the legacy document
                    $legacyDocument = $sourceDb->table('request_documents')
                        ->where('id', $source->model_id)
                        ->first();

                    if (! $legacyDocument) {
                        $errors[] = "Record {$source->id}: Legacy document {$source->model_id} not found";
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    // Find the migrated document by UID
                    $document = Document::where('uid', $legacyDocument->uid)->first();

                    if (! $document) {
                        $errors[] = "Record {$source->id}: Migrated document with UID {$legacyDocument->uid} not found";
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    // Check if this media already exists in the target database
                    $existingMedia = DB::table('media')
                        ->where('uuid', $source->uuid)
                        ->first();

                    if ($existingMedia) {
                        // Update existing record instead of creating duplicate
                        DB::table('media')
                            ->where('uuid', $source->uuid)
                            ->update([
                                'model_type' => Document::class,
                                'model_id' => $document->id,
                                'collection_name' => 'additional_attachments',
                                'name' => $source->name,
                                'file_name' => $source->file_name,
                                'mime_type' => $source->mime_type,
                                'disk' => $source->disk,
                                'conversions_disk' => $source->conversions_disk,
                                'size' => $source->size,
                                'manipulations' => $source->manipulations,
                                'custom_properties' => $source->custom_properties,
                                'generated_conversions' => $source->generated_conversions,
                                'responsive_images' => $source->responsive_images,
                                'order_column' => $source->order_column,
                                'created_at' => $source->created_at,
                                'updated_at' => $source->updated_at,
                            ]);
                    } else {
                        // Insert new media record
                        DB::table('media')->insert([
                            'model_type' => Document::class,
                            'model_id' => $document->id,
                            'uuid' => $source->uuid,
                            'collection_name' => 'additional_attachments',
                            'name' => $source->name,
                            'file_name' => $source->file_name,
                            'mime_type' => $source->mime_type,
                            'disk' => $source->disk,
                            'conversions_disk' => $source->conversions_disk,
                            'size' => $source->size,
                            'manipulations' => $source->manipulations,
                            'custom_properties' => $source->custom_properties,
                            'generated_conversions' => $source->generated_conversions,
                            'responsive_images' => $source->responsive_images,
                            'order_column' => $source->order_column,
                            'created_at' => $source->created_at,
                            'updated_at' => $source->updated_at,
                        ]);
                    }

                    $migrated++;
                } catch (\Exception $e) {
                    $errors[] = "Record {$source->id}: {$e->getMessage()}";
                    $skipped++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            if (! empty($errors)) {
                $this->line('');
                $this->warn('⚠️  Errors encountered:');
                foreach (array_slice($errors, 0, 10) as $error) {
                    $this->error("  • {$error}");
                }
                if (count($errors) > 10) {
                    $this->error('  ... and '.(count($errors) - 10).' more');
                }
            }

            return ['migrated' => $migrated, 'skipped' => $skipped, 'total' => $total];
        } catch (\Exception $e) {
            $this->error('❌ Media migration failed: '.$e->getMessage());

            return ['migrated' => 0, 'skipped' => 0, 'total' => 0];
        }
    }

    /**
     * Migrate document products
     */
    private function migrateProducts(): array
    {
        try {
            $this->info('📊 Querying migrated documents...');

            // Get all migrated documents that have an order_id
            $documents = Document::whereNotNull('order_id')
                ->orderBy('id')
                ->get();

            $total = $documents->count();
            $this->info("Found {$total} documents with orders");

            if ($total === 0) {
                $this->warn('No documents with orders found');

                return ['migrated' => 0, 'skipped' => 0, 'total' => 0];
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $migrated = 0;
            $skipped = 0;
            $errors = [];

            foreach ($documents as $document) {
                try {
                    // Get products from Prestashop for this order
                    $prestashopProducts = $this->fetchPrestashopOrderProducts($document->order_id);

                    if (empty($prestashopProducts)) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    // Create document products from Prestashop order products
                    foreach ($prestashopProducts as $product) {
                        try {
                            DocumentProduct::updateOrCreate(
                                [
                                    'document_id' => $document->id,
                                    'product_id' => $product['id_product'],
                                ],
                                [
                                    'product_name' => $product['product_name'],
                                    'product_reference' => $product['product_reference'],
                                    'quantity' => $product['product_quantity'],
                                    'price' => $product['product_price'],
                                ]
                            );
                            $migrated++;
                        } catch (\Exception $e) {
                            $errors[] = "Document {$document->uid}: Product {$product['id_product']}: {$e->getMessage()}";
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "Document {$document->uid}: {$e->getMessage()}";
                    $skipped++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            if (! empty($errors)) {
                $this->line('');
                $this->warn('⚠️  Errors encountered:');
                foreach (array_slice($errors, 0, 10) as $error) {
                    $this->error("  • {$error}");
                }
                if (count($errors) > 10) {
                    $this->error('  ... and '.(count($errors) - 10).' more');
                }
            }

            return ['migrated' => $migrated, 'skipped' => $skipped, 'total' => $total];
        } catch (\Exception $e) {
            $this->error('❌ Product migration failed: '.$e->getMessage());

            return ['migrated' => 0, 'skipped' => 0, 'total' => 0];
        }
    }

    /**
     * Sync a single document (populate required_documents and uploaded_documents)
     */
    private function syncDocument(Document $document, bool $force = false): bool
    {
        // Skip if already synced and not forcing
        if (! $force && ! empty($document->required_documents) && ! empty($document->uploaded_documents)) {
            return false;
        }

        try {
            // Ensure document has a valid type_id
            if (! $document->type_id) {
                $generalType = DocumentType::where('slug', 'general')->first();
                if ($generalType) {
                    $document->type_id = $generalType->id;
                } else {
                    return false;
                }
            }

            // Get the document type slug and generate required_documents
            $typeSlug = $document->documentType?->slug ?? 'general';
            $requiredDocs = DocumentTypeService::getRequiredDocuments($typeSlug);
            $document->required_documents = $requiredDocs;

            // Generate uploaded_documents from current media
            $uploadedDocs = [];
            foreach ($document->getMedia('documents') as $media) {
                $docType = $media->getCustomProperty('document_type', 'documento');
                $uploadedDocs[$docType] = [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'size' => $media->size,
                    'url' => $media->getUrl(),
                    'created_at' => $media->created_at->format('Y-m-d H:i:s'),
                ];
            }
            $document->uploaded_documents = $uploadedDocs;

            // Save the document
            $document->save();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sync document fields (required_documents and uploaded_documents)
     */
    private function syncDocumentFields(): array
    {
        $documents = Document::all();
        $total = $documents->count();

        $this->info("Synchronizing {$total} documents...");

        if ($total === 0) {
            $this->warn('No documents found to sync');

            return ['synced' => 0, 'skipped' => 0];
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $synced = 0;
        $skipped = 0;

        foreach ($documents as $document) {
            if ($this->syncDocument($document, false)) {
                $synced++;
            } else {
                $skipped++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return ['synced' => $synced, 'skipped' => $skipped];
    }

    /**
     * Update customer emails from Prestashop
     */
    private function updateCustomerEmails(): array
    {
        try {
            $documents = Document::all();
            $total = $documents->count();

            $this->info("Updating emails for {$total} documents...");

            if ($total === 0) {
                $this->warn('No documents found to update');

                return ['updated' => 0, 'skipped' => 0, 'total' => 0];
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $updated = 0;
            $skipped = 0;
            $errors = [];

            foreach ($documents as $document) {
                try {
                    // Only update if document has a customer_id
                    if (! $document->customer_id) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    // Fetch customer email from Prestashop
                    $email = DB::connection('prestashop')
                        ->table('aalv_customer')
                        ->where('id_customer', $document->customer_id)
                        ->value('email');

                    if ($email && $email !== $document->customer_email) {
                        $document->customer_email = $email;
                        $document->save();
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Document {$document->id}: {$e->getMessage()}";
                    $skipped++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            if (! empty($errors)) {
                $this->line('');
                $this->warn('⚠️  Errors encountered:');
                foreach (array_slice($errors, 0, 5) as $error) {
                    $this->error("  • {$error}");
                }
                if (count($errors) > 5) {
                    $this->error('  ... and '.(count($errors) - 5).' more');
                }
            }

            return ['updated' => $updated, 'skipped' => $skipped, 'total' => $total];

        } catch (\Exception $e) {
            $this->error('❌ Email update failed: '.$e->getMessage());

            return ['updated' => 0, 'skipped' => 0, 'total' => 0];
        }
    }

    /**
     * Clean existing documents and reset autoincrement counters
     */
    private function cleanExistingData(): void
    {
        try {
            $this->info('🧹 Removing existing documents...');

            // Disable foreign key constraints
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');

            // Get current counts before deletion
            $docCount = Document::count();
            $prodCount = DocumentProduct::count();

            if ($docCount > 0) {
                Document::truncate();
                $this->line("  ✓ Deleted {$docCount} documents");
            } else {
                $this->line('  ✓ No documents to delete');
            }

            if ($prodCount > 0) {
                DocumentProduct::truncate();
                $this->line("  ✓ Deleted {$prodCount} document products");
            } else {
                $this->line('  ✓ No document products to delete');
            }

            // Clean media associated with our new Document model
            $mediaCount = DB::table('media')
                ->where('model_type', Document::class)
                ->count();

            if ($mediaCount > 0) {
                DB::table('media')
                    ->where('model_type', Document::class)
                    ->delete();
                $this->line("  ✓ Deleted {$mediaCount} document media");
            } else {
                $this->line('  ✓ No document media to delete');
            }

            // Reset autoincrement counters
            $this->info('🔄 Resetting autoincrement counters...');
            DB::statement('ALTER TABLE documents AUTO_INCREMENT = 1');
            $this->line('  ✓ Documents autoincrement reset');

            DB::statement('ALTER TABLE document_products AUTO_INCREMENT = 1');
            $this->line('  ✓ Document products autoincrement reset');

            // Re-enable foreign key constraints
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            $this->info('✅ Data cleanup completed successfully');

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            $this->error('❌ Data cleanup failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Create document products from Prestashop order
     */
    private function createDocumentProductsFromPrestashop(Document $document, $orderId): void
    {
        $prestashopProducts = $this->fetchPrestashopOrderProducts($orderId);

        if (empty($prestashopProducts)) {
            return;
        }

        foreach ($prestashopProducts as $product) {
            try {
                DocumentProduct::updateOrCreate(
                    [
                        'document_id' => $document->id,
                        'product_id' => $product['id_product'],
                    ],
                    [
                        'product_name' => strtoupper($product['product_name'] ?? ''),
                        'product_reference' => strtoupper($product['product_reference'] ?? ''),
                        'quantity' => $product['product_quantity'],
                        'price' => $product['product_price'],
                    ]
                );
            } catch (\Exception $e) {
                // Log but continue
                throw new \Exception("Failed to create product {$product['id_product']}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Migrate media from manager database for a specific document
     */
    private function createDocumentMediaFromLegacy(Document $document, $legacyDocumentId): void
    {
        try {

            $sourceDb = DB::connection('mysql');
            $sourceDb->getPdo(); // Test connection

            // Get media records for this specific document from OLD model (App\Models\Order\Document)
            $sourceRecords = $sourceDb->table('media')
                ->where('model_type', 'App\\Models\\Order\\Document')
                ->where('model_id', $legacyDocumentId)
                ->orderBy('id')
                ->get();

            if ($sourceRecords->isEmpty()) {
                return;
            }

            foreach ($sourceRecords as $source) {
                try {
                    // Copy media record from old model to new model, keeping the same media ID
                    // This preserves the file folder structure (/storage/media/{id}/...)
                    DB::connection('mysql')->table('media')->updateOrInsert(
                        ['id' => $source->id],  // Match by ID to preserve folder structure
                        [
                            'model_type' => Document::class,
                            'model_id' => $document->id,
                            'uuid' => $source->uuid,
                            'collection_name' => 'additional_attachments',
                            'name' => $source->name,
                            'file_name' => $source->file_name,
                            'mime_type' => $source->mime_type,
                            'disk' => $source->disk,
                            'conversions_disk' => $source->conversions_disk,
                            'size' => $source->size,
                            'manipulations' => $source->manipulations,
                            'custom_properties' => $source->custom_properties,
                            'generated_conversions' => $source->generated_conversions,
                            'responsive_images' => $source->responsive_images,
                            'order_column' => $source->order_column,
                            'created_at' => $source->created_at,
                            'updated_at' => now(),
                        ]
                    );
                } catch (\Exception $e) {
                    // Log but continue - don't fail media migration for one record
                    throw new \Exception("Failed to migrate media {$source->id}: {$e->getMessage()}");
                }
            }
        } catch (\Exception $e) {
            // Re-throw to be caught by the parent method
            throw $e;
        }
    }

    /**
     * Fetch Prestashop customer data using MySQL CLI to bypass IP restrictions
     */
    private function fetchPrestashopCustomer($customerId): ?array
    {
        $config = config('prestashop');

        $query = "SELECT c.firstname, c.lastname, c.email, ca.vat_number, ca.company
                  FROM aalv_customer as c
                  LEFT JOIN aalv_address as ca ON c.id_customer = ca.id_customer AND ca.deleted = 0
                  WHERE c.id_customer = {$customerId}
                  LIMIT 1";

        try {
            $output = shell_exec("mysql -h {$config['host']} -u {$config['username']} -p'{$config['password']}' {$config['database']} -sN -e \"".addslashes($query).'" 2>/dev/null');

            if ($output) {
                $parts = explode("\t", trim($output));
                // Should have at least firstname and lastname (parts 0,1)
                if (count($parts) >= 2) {
                    return [
                        'firstname' => ! empty($parts[0]) ? $parts[0] : null,
                        'lastname' => ! empty($parts[1]) ? $parts[1] : null,
                        'email' => ! empty($parts[2]) ? $parts[2] : null,
                        'vat_number' => (isset($parts[3]) && ! empty($parts[3])) ? $parts[3] : null,
                        'company' => (isset($parts[4]) && ! empty($parts[4])) ? $parts[4] : null,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return null;
    }

    /**
     * Fetch Prestashop order data using MySQL CLI to bypass IP restrictions
     */
    private function fetchPrestashopOrder($orderId): ?array
    {
        $config = config('prestashop');

        $query = "SELECT id_lang, reference, date_add FROM aalv_orders WHERE id_order = {$orderId} LIMIT 1";

        try {
            $output = shell_exec("mysql -h {$config['host']} -u {$config['username']} -p'{$config['password']}' {$config['database']} -sN -e \"".addslashes($query).'" 2>/dev/null');

            if ($output) {
                $parts = explode("\t", trim($output));
                // Should return: id_lang, reference, date_add
                if (count($parts) >= 1) {
                    return [
                        'id_lang' => (is_numeric($parts[0])) ? (int) $parts[0] : null,
                        'reference' => ! empty($parts[1]) ? $parts[1] : null,
                        'date_add' => ! empty($parts[2]) ? $parts[2] : null,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return null;
    }

    /**
     * Fetch order products from Prestashop
     */
    private function fetchPrestashopOrderProducts($orderId): array
    {
        $config = config('prestashop');

        $query = "SELECT od.product_id, od.product_name, od.product_reference, od.product_quantity, od.product_price
                  FROM aalv_order_detail as od
                  WHERE od.id_order = {$orderId}";

        try {
            $output = shell_exec("mysql -h {$config['host']} -u {$config['username']} -p'{$config['password']}' {$config['database']} -sN -e \"".addslashes($query).'" 2>/dev/null');

            if (! $output) {
                return [];
            }

            $products = [];
            $lines = explode("\n", trim($output));

            foreach ($lines as $line) {
                if (empty($line)) {
                    continue;
                }

                $parts = explode("\t", $line);
                if (count($parts) >= 5) {
                    $products[] = [
                        'id_product' => (int) $parts[0],
                        'product_name' => $parts[1] ?? null,
                        'product_reference' => $parts[2] ?? null,
                        'product_quantity' => (int) $parts[3],
                        'product_price' => (float) $parts[4],
                    ];
                }
            }

            return $products;
        } catch (\Exception $e) {
            // Silently fail
            return [];
        }
    }
}
