<?php

namespace AlsernetShopping\Carriers;

use Address;
use Context;

interface CarrierHandlerInterface
{
    public function getId(): int;

    public function getAnalyticName(): string;

    public function isEnabled(): bool;

    public function getExtraContent(Address $address, Context $context): string;

    public function processSelection(array $data, Context $context): array;

    public function getRequiredAssets(): array;

    public function getTemplatePath(): string;

    public function cleanup(): void;

    public function validateAvailability(Context $context): array;

    public function getConfiguration(): array;
}