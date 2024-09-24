<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self blocked()
 * @method static self completed()
 * @method static self error()
 * @method static self rateLimit()
 * @method static self running()
 * @method static self stopped()
 */
final class CrawlStatus extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function values(): array
    {
        return [
            'blocked' => 'blocked',
            'completed' => 'completed',
            'error' => 'error',
            'rateLimit' => 'rate_limit',
            'running' => 'running',
            'stopped' => 'stopped',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'blocked' => 'Blocked',
            'completed' => 'Completed',
            'error' => 'Error',
            'rateLimit' => 'Rate Limit',
            'running' => 'Running',
            'stopped' => 'Stopped',
        ];
    }
}
