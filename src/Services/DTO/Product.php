<?php

namespace App\Services\DTO;

final class Product extends AbstractDataTransferObject
{
    public string $identifier;

    public ?string $title = null;

    public ?float $price = 0.0;

    public ?string $imageUrl = null;

    public ?int $capacityMB = 0;

    public ?string $colour = null;

    public ?string $availabilityText = null;

    public bool $isAvailable = false;

    public ?string $shippingText = null;

    public ?string $shippingDate = null;

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'price' => $this->price,
            'imageUrl' => $this->imageUrl,
            'capacityMB' => $this->capacityMB,
            'colour' => $this->colour,
            'availabilityText' => $this->availabilityText,
            'isAvailable' => $this->isAvailable,
            'shippingText' => $this->shippingText,
            'shippingDate' => $this->shippingDate,
        ];
    }
}
