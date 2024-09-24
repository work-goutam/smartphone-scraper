<?php

namespace App;

use App\Services\ScrapeLogSaver;
use App\Services\SmartPhoneScraperService;

require 'vendor/autoload.php';

class Scrape
{
    /*
    private array $products = [];
    */

    public function run(): void
    {
        /*
        $document = ScrapeHelper::fetchDocument('https://www.magpiehq.com/developer-challenge/smartphones');

        file_put_contents('output.json', json_encode($this->products));
        */
        $crawlResult = (new SmartPhoneScraperService)->crawl();

        (new ScrapeLogSaver)->saveToFile($crawlResult, 'output.json');
    }
}

$scrape = new Scrape;
$scrape->run();
echo 'Process completed';
