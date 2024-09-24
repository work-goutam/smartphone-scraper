<?php

namespace App\Contracts;

use App\Services\Scraper\CrawlResult;

interface ScrapeService
{
    public function crawl(): CrawlResult;
}
