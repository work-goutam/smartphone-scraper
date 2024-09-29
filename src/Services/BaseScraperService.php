<?php

namespace App\Services;

use App\Contracts\ProxyCall;
use App\Contracts\ScrapeService;
use App\Enums\CrawlStatus;
use App\Services\Scraper\CrawlResult;
use App\Support\Collection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;

abstract class BaseScraperService implements ProxyCall, ScrapeService
{
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

    protected int $currentPage = 1;

    protected int $maxRetries = 3; // Maximum number of retries

    protected int $backoffTime = 2; // Default backoff time in seconds

    /**
     * @var array<int,string>
     */
    protected $fail = [];

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
    public function crawl(Collection $config): CrawlResult
    {
        $this->currentPage = is_numeric($config->get('page', 1)) ? (int) $config->get('page', 1) : 1;

        $response = $this->getResponse($this->currentPage);

        $failedResponse = $this->handleFailedResponse($response);
        if ($failedResponse !== null) {
            return $failedResponse;
        }

        $totalPages = $this->getTotalPages($response->getBody());

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

        $attempts = 0;
        $response = null;

        while ($attempts < $this->maxRetries) {
            try {
                $request = new Request(
                    'GET',
                    $this->getBaseUrl().'?'.http_build_query($query)
                );

                // Make the request
                $response = $this->client->send($request, [
                    RequestOptions::HEADERS => [
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11_7_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36',
                    ],
                    RequestOptions::HTTP_ERRORS => false,
                ]);

                // If the response status is not 429 (Too Many Requests), return it
                if ($response->getStatusCode() !== 429) {
                    return $response;
                }

                // Handle 429 and retry
                $attempts++;
                $retryAfter = $this->getRetryAfter(new RequestException('429', $request, $response));

                // Wait before retrying
                sleep($retryAfter);

            } catch (RequestException $e) {
                // Check if it's a 429 (Too Many Requests)
                if ($e->getResponse() && $e->getResponse()->getStatusCode() === 429) {
                    $attempts++;
                    $retryAfter = $this->getRetryAfter($e);

                    // Wait before retrying
                    sleep($retryAfter);

                    continue;
                }

                // If it's a different exception, rethrow it
                throw $e;
            }
        }

        // Fallback: If retries are exhausted, return a valid ResponseInterface
        return $response ?: new Response(403, [], 'Max retries exhausted');

    }

    /**
     * Determine the retry-after time, either from the Retry-After header or use a default backoff.
     */
    private function getRetryAfter(RequestException $e): int
    {
        $response = $e->getResponse();

        if ($response instanceof ResponseInterface) {
            $retryAfterHeader = $response->getHeader('Retry-After');

            if (! empty($retryAfterHeader)) {
                return (int) $retryAfterHeader[0];
            }
        }

        // Use exponential backoff if no Retry-After header is provided
        return $this->backoffTime;
    }

    /**
     * Fetch content from source
     */
    private function fetch(int $totalPages, CrawlResult $crawlResult): void
    {
        $response = null;

        while ($this->currentPage <= $totalPages) {

            $response = $this->getResponse($this->currentPage);

            if ($response->getStatusCode() === 200) {
                $this->populateProducts($response->getBody(), $this->currentPage, $crawlResult);

            } else {
                $this->fail[] = $this->getBaseUrl().'?'.http_build_query([
                    'page' => $this->currentPage,
                ]);
            }

            $this->currentPage++;
        }

        //handle has more pages scenario
        //if the crawler is on last page check for new links and set config for future use

        $morePages = $this->hasMorePages((string) $response?->getBody());

        if ($totalPages > 1 && $this->currentPage > ($totalPages + 1) && $morePages === true) {
            $crawlResult->withHasMorePage($morePages)
                ->withConfig(Collection::make([
                    'page' => $totalPages + 1,
                ]));
        }

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

    abstract public function getBaseUrl(): string;

    abstract public function getTotalPages(string $body): int;

    abstract protected function populateProducts(string $body, int $page, CrawlResult $crawlResult): void;

    abstract protected function hasMorePages(string $body): bool;
}
