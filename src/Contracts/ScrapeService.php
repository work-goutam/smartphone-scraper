<?php

namespace App\Contracts;

use App\Services\Scraper\CrawlResult;
use App\Support\Collection;

interface ScrapeService
{
    public function crawl(Collection $config): CrawlResult;
}
