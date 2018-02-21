<?php

namespace App\GraphQL\DataLoader;

trait DataLoaderHelper
{
    /**
     * Returns a new array mapping each key of the array to a Collection according to the
     * $keyname of each item in the $collection parameter. The returned array order is in 
     * the same order of the $keys parameter.
     * 
     * @param  \Illuminate\Support\Collection  $collection 
     * @param  array  s$keys
     * @param  string  $keyName
     * @return array
     */

    protected static function orderManyPerKey($collection, $keys, $keyName)
    {
        $sorted = array_flip($keys);

        foreach ($collection as $item) {
            $index = $item->{$keyName};

            if (!is_array($sorted[$index])) {
                $sorted[$index] = [];
            }

            $sorted[$index][] = $item;
        }

        foreach ($sorted as $key => $index) {
            if (!is_array($item)) {
                $sorted[$key] = [];
            }
        }

        return array_values($sorted);
    }

    /**
     * Returns a new array mapping each key of the array to an item in the $collection parameter according 
     * to the $keyname of each item. The returned array order is in the same order of the $keys parameter.
     * 
     * @param  \Illuminate\Support\Collection  $collection 
     * @param  array  s$keys
     * @param  string  $keyName
     * @return array
     */

    protected static function orderOnePerKey(Collection $collection, $keys, $keyName)
    {
        $sorted = array_flip($keys);

        foreach ($keys as $key) {
            $sorted[$key] = $collection->where($keyName, $key)->first();
        }

        return array_values($sorted);
    }
}