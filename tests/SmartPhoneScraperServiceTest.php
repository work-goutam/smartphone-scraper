<?php

use App\Enums\CrawlStatus;
use App\Services\DTO\Product;
use App\Services\Scraper\CrawlResult;
use App\Services\SmartPhoneScraperService;
use App\Support\Collection;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

beforeEach(function () {});

afterEach(function () {
    // Clean up Mockery after each test
    \Mockery::close();
});

it('creates a Product DTO from an array', function () {

    $data = [
        'identifier' => 'AN-UNIQUE-NUMBER',
        'title' => 'Sample Product',
        'price' => 99.99,
        'imageUrl' => null,
        'capacityMB' => 6112,
        'colour' => 'red',
        'availabilityText' => 'In Stock',
        'isAvailable' => true,
        'shippingText' => 'Delivery',
        'shippingDate' => '2024-09-22',
    ];

    // Instantiate the DTO
    $product = new App\Services\DTO\Product($data);

    // Assert the DTO properties
    expect($product->identifier)->toEqual('AN-UNIQUE-NUMBER');
    expect($product->title)->toEqual('Sample Product');
    expect($product->price)->toEqual(99.99);
});

it('handles optional fields in Product DTO', function () {

    $data = [
        'identifier' => 'AN-UNIQUE-NUMBER',
    ];
    // Instantiate the DTO
    $product = new App\Services\DTO\Product($data);

    // Assert that optional fields are null
    expect($product->title)->toBeNull();
    expect($product->price)->toBe(0.0);
});

it('converts GB to MB in integer format', function () {

    $capacityMB = convertCapacityToMB('64GB');
    expect($capacityMB)
        ->toBeInt()
        ->toBe(65536);
});

it('get product price in correct format', function () {

    $price = getPrice(' £ 20.50');
    expect($price)
        ->toBeFloat()
        ->toBe(20.50);
});

it('get correct availability text', function () {

    $str = 'Availability: Out of Stock';
    $availabilityText = getAvailability($str);
    expect($availabilityText)->toBe('Out of Stock');
});

it('get correct availability', function () {

    $str = 'Availability: In Stock';
    $isAvailable = getIsAvailable($str);
    expect($isAvailable)->toBeTrue();
});

it('get correct shipping date', function () {

    $str = 'Delivery from 22 Oct 2024';
    $shippingDate = getShippingDate($str);
    expect($shippingDate)->toBe('2024-10-22');
});

it('returns a CrawlResult and can compare with product count and keys', function () {

    // Create some Product DTO objects
    $product1 = new Product(identifier: '1234', title: 'Product 1', price: 10.99);
    $product2 = new Product(identifier: '4568', title: 'Product 2', price: 15.49);

    // Create a CrawlResult instance
    $crawlResult = CrawlResult::make((CrawlStatus::class)::completed());

    // Add products to the CrawlResult's product collection
    $crawlResult->addProduct($product1);
    $crawlResult->addProduct($product2);

    // Assert that the products count the expected input
    expect($crawlResult->getProducts()->count())->toBe(2);
    expect($crawlResult->getProducts()->toArray())->toHaveKey('1234');
});

it('handles failed responses (403) and returns CrawlResult with blocked status', function () {

    $mockResponse = \Mockery::mock(ResponseInterface::class);
    $mockResponse->shouldReceive('getStatusCode')->andReturn(403);

    // Mock the Client and its get() method to return the mockResponse
    $mockClient = \Mockery::mock(Client::class);
    $mockClient->shouldReceive('get')->andReturn($mockResponse);

    // Inject the mocked client into the SmartPhoneScraperService
    $scraperService = new SmartPhoneScraperService($mockClient);

    // Use reflection to call the private handleFailedResponse method
    $reflection = new ReflectionClass($scraperService);
    $method = $reflection->getMethod('handleFailedResponse');
    $method->setAccessible(true);

    $crawlResult = $method->invokeArgs($scraperService, [$mockResponse]);

    // Assert that the status is blocked
    expect($crawlResult)->toBeInstanceOf(CrawlResult::class);
    expect($crawlResult->status)->toBeInstanceOf(CrawlStatus::class);
    expect($crawlResult->status->isBlocked())->toBeTrue();
});

it('successfully runs the scraper and returns a completed CrawlResult', function () {

    // Mock the ResponseInterface and set it to return a 200 status code and mock body content
    $mockStream = \Mockery::mock(StreamInterface::class);
    $mockStream->shouldReceive('__toString')->andReturn('<html><div id="pages"><div><a href="#"></a></div></div></html>');

    // Mock the ResponseInterface and set it to return a 200 status code and the mockStream as the body
    $mockResponse = \Mockery::mock(ResponseInterface::class);
    $mockResponse->shouldReceive('getStatusCode')->andReturn(200);
    $mockResponse->shouldReceive('getBody')->andReturn($mockStream);

    // Mock the Client and its send() method to return the mockResponse
    $mockClient = \Mockery::mock(Client::class);
    $mockClient->shouldReceive('send')->andReturn($mockResponse);

    // Create a SmartPhoneScraperService instance, passing in the mocked client
    $scraperService = new SmartPhoneScraperService($mockClient);

    // Run the scraper service's crawl() method
    $result = $scraperService->crawl(Collection::make());

    // Assert that the returned result is a CrawlResult
    expect($result)->toBeInstanceOf(CrawlResult::class);

    // Assert that the status is completed
    expect($result->status)->toBeInstanceOf(CrawlStatus::class);
    expect($result->status->isCompleted())->toBeTrue();

    // Assert that the status is completed but with no product
    expect($result->getProducts()->count())->toBe(0);
});

it('successfully runs the scraper and returns a stopped CrawlResult', function () {

    // Mock the ResponseInterface and set it to return a 200 status code and mock body content
    $mockStream = \Mockery::mock(StreamInterface::class);
    $mockStream->shouldReceive('__toString')->andReturn('<html><div id="pages"><a href="#"></a></div></html>');

    // Mock the ResponseInterface and set it to return a 200 status code and the mockStream as the body
    $mockResponse = \Mockery::mock(ResponseInterface::class);
    $mockResponse->shouldReceive('getStatusCode')->andReturn(200);
    $mockResponse->shouldReceive('getBody')->andReturn($mockStream);

    // Mock the Client and its send() method to return the mockResponse
    $mockClient = \Mockery::mock(Client::class);
    $mockClient->shouldReceive('send')->andReturn($mockResponse);

    // Create a SmartPhoneScraperService instance, passing in the mocked client
    $scraperService = new SmartPhoneScraperService($mockClient);

    // Run the scraper service's crawl() method
    $result = $scraperService->crawl(Collection::make());

    // Assert that the returned result is a CrawlResult
    expect($result)->toBeInstanceOf(CrawlResult::class);

    // Assert that the status is stopped
    expect($result->status)->toBeInstanceOf(CrawlStatus::class);
    expect($result->status->isStopped())->toBeTrue();
});

it('retries on 429 and returns a valid response after retries', function () {

    $client = \Mockery::mock(Client::class);
    $client->shouldReceive('send')
        ->times(2) // Expect 2 retries with 429
        ->andReturn(new Response(429, ['Retry-After' => 1])); // Return 429 response for first three calls
    $client->shouldReceive('send')
        ->once() // Expect 1 successful call
        ->andReturn(new Response(200, [], '<html>dummy content</html>'));

    $crawler = (new SmartPhoneScraperService($client))->crawl(Collection::make());

    expect($crawler->status->isStopped())->toBeTrue();
    expect($crawler->status)->toBeInstanceOf(CrawlStatus::class);

});
