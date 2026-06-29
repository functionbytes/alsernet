<?php

namespace Modules\EcommercePayment\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\EcommercePayment\Models\Payment;

class PaymentsExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    public function __construct(private array $filters = []) {}

    public function query(): Builder
    {
        $query = Payment::query()->with('order');

        if (isset($this->filters['status']) && $this->filters['status'] !== '') {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['search']) && $this->filters['search'] !== '') {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('charge_id', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('code', 'like', "%{$search}%")
                            ->orWhere('token', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($this->filters['date_from']) && $this->filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (isset($this->filters['date_to']) && $this->filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        return $query->latest();
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Referencia',
            'Orden',
            'Cliente',
            'Monto',
            'Comision',
            'Moneda',
            'Canal',
            'Estado',
            'Fecha',
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->id,
            $payment->charge_id,
            $payment->order?->code ?? '-',
            $payment->order?->customer?->name ?? '-',
            number_format($payment->amount, 2),
            $payment->payment_fee ? number_format($payment->payment_fee, 2) : '-',
            $payment->currency,
            $payment->payment_channel,
            $payment->status->label(),
            $payment->created_at->format('d/m/Y H:i'),
        ];
    }
}
