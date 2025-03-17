<?php

namespace App\Imports;

use App\Exports\Suscribers\SubscribersFailedExport;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\Subscriber\Subscriber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubscribersImport implements ToCollection, WithHeadingRow
{
    private $progressCallback;
    private $invalidRecordCallback;
    private $processed = 0;
    private $failed = 0;
    private $total = null;
    private $failedRecords = [];

    public function __construct($progressCallback = null, $invalidRecordCallback = null)
    {
        $this->progressCallback = $progressCallback;
        $this->invalidRecordCallback = $invalidRecordCallback;
    }

    public function collection(Collection $rows)
    {
        DB::disableQueryLog(); // ✅ Evita sobrecarga de memoria

        $batchSize = 1000; // ✅ Procesa en lotes de 1000 registros

        if (is_null($this->total)) {
            $this->total = $rows->count(); // ✅ Se obtiene el total dinámicamente
        }

        $rows->chunk($batchSize)->each(function ($batch) {
            DB::beginTransaction();

            try {
                foreach ($batch as $row) {
                    $email = !empty($row['email']) ? strtolower(trim($row['email'])) : null;

                    if ($email) {
                        $data = [
                            'firstname' => !empty($row['firstname']) ? strtoupper(trim($row['firstname'])) : null,
                            'lastname' => !empty($row['lastname']) ? strtoupper(trim($row['lastname'])) : null,
                            'email' => $email,
                            'parties' => !empty($row['parties']) ? strtolower(trim($row['parties'])) : '0',
                            'commercial' => !empty($row['commercial']) ? strtolower(trim($row['commercial'])) : '0',
                            'lang_id' => !empty($row['lang']) ? strtolower(trim($row['lang'])) : null,
                            'birthday_at' => !empty($row['birthday']) ? $this->formatDate($row['birthday']) : null,
                            'check_at' => !empty($row['check']) ? $this->formatDate($row['check']) : null,
                            'unsubscriber_at' => !empty($row['unsubscriber']) ? $this->formatDate($row['unsubscriber']) : null,
                            'created_at' => !empty($row['created']) ? $this->formatDate($row['created']) : now(),
                            'updated_at' => !empty($row['updated']) ? $this->formatDate($row['updated']) : now(),
                        ];

                        try {
                            $subscriber = Subscriber::updateOrCreate(['email' => $email], $data);
                            if ($subscriber) {
                                $this->processed++;
                            }
                        } catch (\Exception $e) {
                            $this->failed++;
                            Log::error("Error creando Subscriber: " . $e->getMessage());

                            $row['error'] = $e->getMessage();
                            $this->failedRecords[] = $row;
                            continue;
                        }

                        if ($subscriber && $subscriber->id) {
                            $dataCustomer = [
                                'firstname' => !empty($row['firstname']) ? strtoupper($row['firstname']) : null,
                                'lastname' => !empty($row['lastname']) ? strtoupper($row['lastname']) : null,
                                'email' => $email,
                                'available' => 1,
                                'management' => !empty($row['management']) ? strtolower(trim($row['management'])) : null,
                                'customer' => !empty($row['customer']) ? strtolower(trim($row['customer'])) : null,
                                'subscriber_id' => $subscriber->id,
                                'birthday_at' => !empty($row['birthday']) ? $this->formatDate($row['birthday']) : null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            try {
                                Customer::updateOrCreate(['email' => $email], $dataCustomer);
                            } catch (\Exception $e) {
                                Log::error("Error creando Customer: " . $e->getMessage());
                                $row['error'] = $e->getMessage();
                                $this->failedRecords[] = $row;
                                continue;
                            }
                        }
                    }
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                $this->failed++;
            } finally {

                $percentage = ($this->total > 0) ? intval(($this->processed * 100) / $this->total) : 0;

                if (!is_null($this->progressCallback)) {
                    ($this->progressCallback)(
                        $this->processed,
                        $this->total,
                        $this->failed,
                        "Procesado: {$this->processed}/{$this->total}",
                        $percentage
                    );
                }
            }

            if (!is_null($this->progressCallback)) {
                $percentage = ($this->total > 0) ? intval(($this->processed * 100) / $this->total) : 0;

                Log::info("Importación en progreso: {$this->processed}/{$this->total} ({$percentage}%) - Fallidos: {$this->failed}");

                ($this->progressCallback)(
                    $this->processed,
                    $this->total,
                    $this->failed,
                    "Procesado: {$this->processed}/{$this->total}",
                    $percentage
                );
            }

            DB::disconnect();
            gc_collect_cycles();
        });

        return $this->exportFailedRecords();


//        if (!is_null($this->progressCallback)) {
//            $percentage = ($this->total > 0) ? intval(($this->processed * 100) / $this->total) : 100;
//
//            ($this->progressCallback)(
//                $this->processed,
//                $this->total,
//                $this->failed,
//                "Importación finalizada. Procesado: {$this->processed}/{$this->total} ({$percentage}%) - Fallidos: {$this->failed}",
//                $percentage
//            );
//        }
    }

    public function exportFailedRecords(): ?BinaryFileResponse
    {
        if (!empty($this->failedRecords)) {
            $fileName = 'subscribers_failed_' . now()->format('Y-m-d_His') . '.xlsx';
            return Excel::download(new SubscribersFailedExport($this->failedRecords), $fileName);
        }

        return null;
    }

    public function getFailedRecords()
    {
        return $this->failedRecords;
    }

    private function formatDate($date)
    {
        if (!$date) {
            return null;
        }

        // Formatos posibles
        $formatos = [
            'd/m/Y H:i:s',   // 14/04/2019 10:52
            'd/m/Y H:i',   // 14/04/2019 10:52:00
            'd/m/Y',       // 17/04/1996
            'Y-m-d H:i:s', // 2025-03-02 14:30:00 (en caso de recibir timestamps)
            'Y-m-d H:i',      // 2025-03-02
            'Y-m-d'        // 2025-03-02
        ];

        foreach ($formatos as $formato) {
            try {
                $parsedDate = Carbon::createFromFormat($formato, $date);

                // Verificar que el año sea válido (>= 1000)
                if ($parsedDate->year >= 1000) {
                    return $parsedDate->format('Y-m-d');
                }
            } catch (Exception $e) {
                continue; // Si falla, sigue con el siguiente formato
            }
        }

        return null; // Si ningún formato coincide, retorna null
    }

}
