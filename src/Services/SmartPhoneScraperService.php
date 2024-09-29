<?php

namespace App\Services;

use App\Services\DTO\Product;
use App\Services\Scraper\CrawlResult;
use Symfony\Component\DomCrawler\Crawler;

class SmartPhoneScraperService extends BaseScraperService
{
    public const BASE_URL = 'https://www.magpiehq.com/developer-challenge/smartphones';

    /**
     * Get BASE URL
     */
    public function getBaseUrl(): string
    {
        return self::BASE_URL;
    }

    /**
     * Get total pages
     */
    public function getTotalPages(string $body): int
    {

        $crawler = new Crawler((string) $body, $this->getBaseUrl());

        return $crawler->filter('#pages > div')->filter('a')->count();
    }

    /**
     * Populate products
     */
    protected function populateProducts(string $body, int $page, CrawlResult $crawlResult): void
    {
        $scanUrl = $this->getBaseUrl().'/?page='.$page;

        $crawler = new Crawler((string) $body, $scanUrl);

        $crawler->filter('.product')->each(function (Crawler $crawler) use ($crawlResult) {

            $data = [
                'title' => $crawler->filter('.product-name')->text(),
                'price' => getPrice($crawler->filter('.my-8.text-lg')->text()),
                'imageUrl' => getFullImageUrl($crawler->filter('img')->attr('src'), $this->getBaseUrl()),
                'capacityMB' => convertCapacityToMB($crawler->filter('.product-capacity')->text()),
                'availabilityText' => getAvailability($crawler->filter('.my-4.text-sm.block.text-center')->first()->text()),
                'isAvailable' => getIsAvailable($crawler->filter('.my-4.text-sm.block.text-center')->first()->text()),
                'shippingText' => $crawler->filter('.my-4.text-sm.block.text-center')->last()->text(),
                'shippingDate' => getShippingDate($crawler->filter('.my-4.text-sm.block.text-center')->last()->text()),
            ];

            $crawler->filter('span[data-colour]')->each(function (Crawler $node) use ($crawlResult, $data) {
                $colour = $node->attr('data-colour');
                $identifier = sha1($data['title'].$data['capacityMB'].$colour);

                if (! in_array($identifier, $this->uniqueIds)) {

                    $crawlResult->addProduct(new Product($data + [
                        'identifier' => $identifier,
                        'colour' => $colour,
                    ]));

                    $this->uniqueIds[] = $identifier;
                }

            });

        });
    }

    /**
     * Check if more pages exist
     */
    protected function hasMorePages(?string $body): bool
    {
        // add logic to check the existance of more pages
        return true;
    }
}
