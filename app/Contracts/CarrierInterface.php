<?php

namespace App\Contracts\Carriers;

interface CarrierInterface
{
    /**
     * Crear solicitud de recogida
     */
    public function createPickup(array $data): array;

    /**
     * Cancelar recogida
     */
    public function cancelPickup(string $pickupCode): bool;

    /**
     * Obtener estado de envío
     */
    public function getTrackingStatus(string $trackingNumber): array;

    /**
     * Generar etiqueta de envío
     */
    public function generateLabel(array $data): string;

    /**
     * Validar dirección
     */
    public function validateAddress(array $address): bool;

    /**
     * Obtener tarifas
     */
    public function getRates(array $package): array;

    /**
     * Obtener horarios disponibles
     */
    public function getAvailableTimeSlots(string $postalCode, string $date): array;
}

