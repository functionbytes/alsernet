<?php

namespace Modules\Ecommerce\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Ecommerce\Models\Order;

class OrderExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    public function __construct(private readonly array $filters = []) {}

    public function query(): Builder
    {
        return Order::query()
            ->with('customer')
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($this->filters['start_date'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($this->filters['end_date'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest();
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function headings(): array
    {
        return ['ID', 'Codigo', 'Cliente', 'Email', 'Estado', 'Pago', 'Subtotal', 'Envio', 'Impuesto', 'Descuento', 'Total', 'Metodo pago', 'Fecha'];
    }

    public function map($order): array
    {
        return [
            $order->id,
            $order->code,
            $order->customer->name ?? 'Invitado',
            $order->customer->email ?? '',
            $order->status instanceof \BackedEnum ? $order->status->value : ($order->status ?? ''),
            $order->payment_status ?? '',
            $order->sub_total,
            $order->shipping_amount,
            $order->tax_amount,
            $order->discount_amount,
            $order->total,
            $order->payment_method ?? '',
            $order->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
