<?php

namespace App\Services;

use App\Services\Scraper\CrawlResult;

class ScrapeLogSaver
{
    /**
     * save output to a file
     */
    public function saveToFile(CrawlResult $result, string $fileName): void
    {

        /** @var \App\Services\DTO\Product[] $products */
        $products = $result->getProducts()->toArray();

        $data = json_encode(array_map(function ($item) {
            /** @var \App\Services\DTO\Product $item */
            return [
                'title' => $item->title,
                'price' => $item->price,
                'imageUrl' => $item->imageUrl,
                'capacityMB' => $item->capacityMB,
                'colour' => $item->colour,
                'availabilityText' => $item->availabilityText,
                'isAvailable' => $item->isAvailable,
                'shippingText' => $item->shippingText,
                'shippingDate' => $item->shippingDate,
            ];
        }, array_values($products)), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        file_put_contents($fileName, $data);
    }
}
