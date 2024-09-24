<?php

namespace App\Services;

use App\Contracts\ProxyCall;
use App\Contracts\ScrapeService;
use App\Enums\CrawlStatus;
use App\Services\DTO\Product;
use App\Services\Scraper\CrawlResult;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;

//use App\Scrape;

class SmartPhoneScraperService implements ProxyCall, ScrapeService
{
    public const BASE_URL = 'https://www.magpiehq.com/developer-challenge/smartphones';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array<string,string>
     */
    protected $options = [];

    /**
     * @var array<int, string>
     */
    protected $uniqueIds = [];

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client($this->options);
    }

    /**
     * set proxy call options
     */
    public function setProxyCallOption(): void
    {
        // $this->options['proxy'] = 'http://user:password@some-proxy-domain:port';
    }

    /**
     * Run scraper
     */
    public function crawl(): CrawlResult
    {
        $response = $this->getResponse();

        $failedResponse = $this->handleFailedResponse($response);
        if ($failedResponse !== null) {
            return $failedResponse;
        }

        $crawler = new Crawler((string) $response->getBody(), self::BASE_URL);

        $totalPages = $crawler->filter('#pages > div')->filter('a')->count();

        $status = $totalPages ? CrawlStatus::completed() : CrawlStatus::stopped();

        $crawlResult = CrawlResult::make($status);

        if ($totalPages) {
            $this->fetch($totalPages, $crawlResult);
        }

        return $crawlResult;
    }

    /**
     * Get response from a source.
     */
    private function getResponse(int $page = 1): ResponseInterface
    {
        $query = [
            'page' => $page,
        ];

        $response = $this->client->get(self::BASE_URL, [
            RequestOptions::QUERY => $query,
            RequestOptions::HEADERS => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11_7_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36',
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);

        return $response;
    }

    /**
     * Fetch content from source
     */
    private function fetch(int $totalPages, CrawlResult $crawlResult): void
    {
        for ($page = 1; $page <= $totalPages; $page++) {

            $response = $this->getResponse($page);

            if ($response->getStatusCode() === 200) {
                $this->populateProducts($response->getBody(), $page, $crawlResult);
            }
        }

    }

    /**
     * Populate products
     */
    private function populateProducts(string $body, int $page, CrawlResult $crawlResult): void
    {

        $scanUrl = self::BASE_URL.'/?page='.$page;

        $crawler = new Crawler((string) $body, $scanUrl);

        $crawler->filter('.product')->each(function (Crawler $crawler) use ($crawlResult) {

            $data = [
                'title' => $crawler->filter('.product-name')->text(),
                'price' => getPrice($crawler->filter('.my-8.text-lg')->text()),
                'imageUrl' => getFullImageUrl($crawler->filter('img')->attr('src'), self::BASE_URL),
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
     * Handle Failed Response
     */
    private function handleFailedResponse(ResponseInterface $response): ?CrawlResult
    {
        if ($response->getStatusCode() === 403) {
            return CrawlResult::make(CrawlStatus::blocked())
                ->withResponse($response);
        } elseif ($response->getStatusCode() === 404) {
            return CrawlResult::make(CrawlStatus::error())
                ->withResponse($response);
        } elseif ($response->getStatusCode() !== 200) {
            return CrawlResult::make(CrawlStatus::error())
                ->withResponse($response);
        }

        return null;
    }
}
