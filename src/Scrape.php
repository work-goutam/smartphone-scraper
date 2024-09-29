<?php

namespace App;

use App\Services\ScrapeLogSaver;
use App\Services\SmartPhoneScraperService;
use App\Support\Collection;

require 'vendor/autoload.php';

class Scrape
{
    public function run(): void
    {
        $crawlResult = (new SmartPhoneScraperService)->crawl(Collection::make());

        (new ScrapeLogSaver($crawlResult))->saveToFile('output.json');
    }
}

$scrape = new Scrape;
$scrape->run();
echo 'Process completed';
