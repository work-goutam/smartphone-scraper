<?php

use App\Enums\CrawlStatus;
use App\Services\DTO\Product;
use App\Services\ScrapeLogSaver;
use App\Services\Scraper\CrawlResult;

it('save output to a file', function () {

    // Create some Product DTO objects
    $product = new Product(identifier: '1234', title: 'Owsome Product', price: 10.99);

    // Create a CrawlResult instance
    $crawlResult = CrawlResult::make((CrawlStatus::class)::completed());

    // Add product to the CrawlResult's product collection
    $crawlResult->addProduct($product);

    $jsonFile = 'dummy.json';

    (new ScrapeLogSaver($crawlResult))->saveToFile($jsonFile);
    $content = file_get_contents($jsonFile);

    // Assert that the products count the expected input
    $this->assertFileExists($jsonFile);
    expect($content)->toContain('Owsome Product');
    unlink($jsonFile);
});
