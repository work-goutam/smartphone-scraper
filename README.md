## Smartphone Scraper Assignment

This is smartphone scraper application which scrapes data from a given url and save data to a JSON file.

## Prerequisite

I'm using Xampp,Composer for setup.

- PHP version 8.2

## Installation

Clone the repo locally:

```sh
git clone https://github.com/work-goutam/smartphone-scraper.git
cd smartphone-scraper
```

Install PHP dependencies:

```sh
composer install
```

You're ready to go!

## Run the script

To run the scraper, run:

```sh
 php src/Scrape.php
```

## Running tests

To run the tests, run:

```sh
composer test
```

## Check code vulnerability

To run static analysis tool phpstan, run:

```sh
composer analyse
```

## Check code style/format

To run pint, run:

```sh
composer fix
```

## Todo and Improvements

- Script Timeout
- Retry mechanism
