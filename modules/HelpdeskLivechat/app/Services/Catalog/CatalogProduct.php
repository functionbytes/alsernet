<?php

namespace Modules\HelpdeskLivechat\Services\Catalog;

use JsonSerializable;

/**
 * DTO normalizado de un producto de catálogo, independiente del CMS/feed de
 * origen. Es la moneda común entre los drivers (feed, PrestaShop, Shopify…) y
 * las capas que lo consumen: el carrusel de producto del coviewer y el bot de
 * recomendación. Mantenerlo inmutable y serializable evita fugas de estructuras
 * específicas de cada plataforma hacia el resto del módulo.
 */
final class CatalogProduct implements JsonSerializable
{
    /**
     * @param  array<int, array{label: string, value: string}>  $variants  Opciones (talla/color…) que el visitante puede elegir en el coviewer.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $imageUrl = null,
        public readonly ?string $url = null,
        public readonly ?float $price = null,
        public readonly ?string $currency = null,
        public readonly ?string $description = null,
        public readonly bool $available = true,
        public readonly array $variants = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            title: (string) ($data['title'] ?? $data['name'] ?? ''),
            imageUrl: isset($data['image_url']) ? (string) $data['image_url'] : ($data['image'] ?? null),
            url: isset($data['url']) ? (string) $data['url'] : ($data['link'] ?? null),
            price: isset($data['price']) ? (float) $data['price'] : null,
            currency: isset($data['currency']) ? (string) $data['currency'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            available: (bool) ($data['available'] ?? true),
            variants: is_array($data['variants'] ?? null) ? $data['variants'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'image_url' => $this->imageUrl,
            'url' => $this->url,
            'price' => $this->price,
            'currency' => $this->currency,
            'description' => $this->description,
            'available' => $this->available,
            'variants' => $this->variants,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
