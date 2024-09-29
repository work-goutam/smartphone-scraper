<?php

namespace App\Services;

use App\Services\Scraper\CrawlResult;

class ScrapeLogSaver
{
    public function __construct(
        private CrawlResult $crawlResult
    ) {}

    /**
     * save output to a file
     */
    public function saveToFile(string $fileName): void
    {

        /** @var \App\Services\DTO\Product[] $products */
        $products = $this->crawlResult->getProducts()->toArray();

        $data = json_encode(array_map(function ($item) {
            /** @var \App\Services\DTO\Product $item */
            return $item->toArray();
        }, array_values($products)), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        file_put_contents($fileName, $data);
    }
}
