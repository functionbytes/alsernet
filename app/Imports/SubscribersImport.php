<?php

namespace App\Imports;

use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\Subscriber\Subscriber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Exception;

class SubscribersImport implements ToCollection, WithHeadingRow
{
    private $progressCallback;
    private $invalidRecordCallback;
    private $totalRows;
    private $processed = 0;
    private $failed = 0;

    public function __construct($progressCallback = null, $totalRows = 0, $invalidRecordCallback = null)
    {
        $this->progressCallback = $progressCallback;
        $this->invalidRecordCallback = $invalidRecordCallback;
        $this->totalRows = $totalRows;
    }

    public function collection(Collection $rows)
    {
        $batchSize = max(1, config('app.import_batch_size', 1000));

        $rows->chunk($batchSize)->each(function ($batch) {
            DB::beginTransaction();

            //try {
                foreach ($batch as $row) {

                    // Habilitar logging de consultas SQL para depuración
                    DB::enableQueryLog();

                    $email = !empty($row['email']) ? strtolower(trim($row['email'])) : null;

                    if ($email) {
                        // Construcción de $data asegurando que no haya valores vacíos
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
                            'created_at' => !empty($row['created']) ? $this->formatDate($row['created']) : $this->formatDate(now()),
                            'updated_at' => !empty($row['updated']) ? $this->formatDate($row['updated']) : $this->formatDate(now()),
                        ];

                        // Intentar crear o actualizar el suscriptor
                        try {
                            $subscriber = Subscriber::updateOrCreate(['email' => $email], $data);
                            Log::info("Subscriber creado o actualizado correctamente. ID: " . $subscriber->id);
                        } catch (\Exception $e) {
                            Log::error("Error creando Subscriber: " . $e->getMessage());
                            return;
                        }

                        if ($subscriber && $subscriber->id) {
                            // Construcción de $dataCustomer asegurando valores no vacíos
                            $dataCustomer = [
                                'firstname' => !empty($row['firstname']) ? strtoupper($row['firstname']) : null,
                                'lastname' => !empty($row['lastname']) ? strtoupper($row['lastname']) : null,
                                'management' => !empty($row['management']) ? strtolower(trim($row['management'])) : null,
                                'customer' => !empty($row['network']) ? strtolower(trim($row['network'])) : null,
                                'subscriber_id' => $subscriber->id,
                                'birthday_at' => !empty($row['birthday']) ? $this->formatDate($row['birthday']) : null,
                                'created_at' => !empty($row['created']) ? $this->formatDate($row['created']) : $this->formatDate(now()),
                                'updated_at' => !empty($row['updated']) ? $this->formatDate($row['updated']) : $this->formatDate(now()),
                            ];

                            // Intentar crear o actualizar el Customer
                            try {
                                $customer = Customer::updateOrCreate(['subscriber_id' => $subscriber->id], $dataCustomer);
                                Log::info("Customer creado o actualizado correctamente", ['id' => $customer->id]);
                            } catch (\Exception $e) {
                                Log::error("Error creando Customer: " . $e->getMessage());
                            }
                        } else {
                            Log::error("Subscriber no se creó correctamente para email: " . $email);
                        }
                    }

                    Log::info(DB::getQueryLog());


                    $this->processed++;

                }

                DB::commit();
            //} catch (Exception $e) {
             //   DB::rollBack();
             //   $this->failed++;
              ///  if (!is_null($this->invalidRecordCallback)) {
             ///       ($this->invalidRecordCallback)(null, ['error' => $e->getMessage()]);
              //  }
            //}

            // Callback de progreso
            if (!is_null($this->progressCallback)) {
                ($this->progressCallback)($this->processed, $this->totalRows, $this->failed, "Procesado: {$this->processed}/{$this->totalRows}");
            }
        });

        // Callback de finalización
        if (!is_null($this->progressCallback)) {
            ($this->progressCallback)($this->processed, $this->totalRows, $this->failed, "Importación finalizada.");
        }
    }

    private function formatDate($date)
    {
        try {
            return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
        } catch (Exception $e) {
            return null;
        }
    }
}
