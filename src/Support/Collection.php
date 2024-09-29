<?php

namespace App\Support;

use App\Contracts\Arrayable;

class Collection implements Arrayable
{
    /**
     * The items contained in the collection.
     *
     * @var array<int|string, mixed>
     */
    protected $items = [];

    /**
     * Create a new collection.
     *
     * @param  array<int|string, mixed>  $items
     * @return void
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @param  array<int|string, mixed>  $items
     * @return self
     */
    public static function make(array $items = [])
    {
        return new self($items);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        return $this->all();
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array<int|string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Put an item in the collection by key.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return $this
     */
    public function put($key, $value)
    {
        $this->offsetSet($key, $value);

        return $this;
    }

    /**
     * Count the number of items in the collection.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get an item from the collection by key.
     *
     * @param  int|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        return value($default);
    }
}
