<?php

namespace App\Contracts;

interface Arrayable
{
    /**
     * Get the instance as an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(): array;
}
