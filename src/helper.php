<?php

/**
 * return the product price
 *
 * @return float product price
 */
function getPrice(string $price): float
{
    return (float) str_replace(['£', ' '], '', $price);
}

/**
 * Builds full image URL from the source.
 */
function getFullImageUrl(?string $src, string $baseUrl): ?string
{
    return ! is_null($src) ? $baseUrl.str_replace('..', '', $src) : null;
}

/**
 * Converts device capacity to MB.
 */
function convertCapacityToMB(string $capacity): int
{
    if (strpos($capacity, 'GB') !== false) {
        $capacityGB = (int) str_replace('GB', '', $capacity);

        return $capacityGB * 1024;
    }

    return (int) str_replace('MB', '', $capacity);
}

/**
 * Extracts the availability text.
 */
function getAvailability(string $availability): string
{
    return strpos($availability, 'Availability: ') !== false ? str_replace('Availability: ', '', $availability) : $availability;
}

/**
 * Check if available.
 */
function getIsAvailable(string $availability): bool
{
    return strpos($availability, 'In Stock') !== false;
}

/**
 * Extracts the shipping date from the shipping text.
 */
function getShippingDate(?string $shippingText): ?string
{
    if (is_null($shippingText)) {
        return null;
    }

    // long date formats, e.g: 25 Sep 2024
    if (preg_match('/(\d{1,2}(?:st|nd|rd|th)? \w+ \d{4})/', $shippingText, $matches) ||
        preg_match('/(\d{1,2} \w+ \d{4})/', $shippingText, $matches)) {

        $timestamp = strtotime($matches[1]);

        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    // date format (YYYY-MM-DD), e.g: 2024-09-20
    if (preg_match('/\d{4}-\d{2}-\d{2}/', $shippingText, $matches)) {
        return $matches[0];
    }

    // other date formats, e.g : "tomorrow"
    if (stripos($shippingText, 'tomorrow') !== false) {
        return date('Y-m-d', strtotime('tomorrow'));
    }

    return null;
}
