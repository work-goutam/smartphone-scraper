<?php

namespace App\Services\Scraper;

use App\Enums\CrawlStatus;
use App\Services\DTO\Product;
use App\Support\Collection;
use Psr\Http\Message\ResponseInterface;

final class CrawlResult
{
    private Collection $products;

    public CrawlStatus $status;

    public ?int $totalCount;

    public ?Collection $config = null;

    public bool $hasMorePage = false;

    public ResponseInterface $response;

    private function __construct(CrawlStatus $status, ?int $totalCount = null)
    {
        $this->products = Collection::make();
        $this->status = $status;
        $this->totalCount = $totalCount;
    }

    public static function make(CrawlStatus $status, ?int $totalCount = null): CrawlResult
    {
        return new self($status, $totalCount);
    }

    /**
     * @return Collection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @return $this
     */
    public function addProduct(Product $product): CrawlResult
    {
        $this->products->put($product->identifier, $product);
        $this->totalCount++;

        return $this;
    }

    /**
     * @return $this
     */
    public function withResponse(ResponseInterface $response): CrawlResult
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return $this
     */
    public function withConfig(?Collection $config = null)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return $this
     */
    public function withHasMorePage(bool $hasMorePage): CrawlResult
    {
        $this->hasMorePage = $hasMorePage;

        return $this;
    }
}
