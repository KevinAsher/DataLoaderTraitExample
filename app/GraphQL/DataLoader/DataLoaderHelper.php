<?php

namespace App\GraphQL\DataLoader;

use Illuminate\Database\Eloquent\Collection;


trait DataLoaderHelper
{
    /**
     * Returns a new array mapping each key of the array to a Collection according to the
     * $keyname of each item in the $collection parameter. The returned array order is in 
     * the same order of the $keys parameter.
     * 
     * @param  \Illuminate\Support\Collection  $collection 
     * @param  array  $keys
     * @param  string  $keyName
     * @return array
     */

    public function orderManyPerKey(Collection $collection, $keys, $keyName)
    {
        $sorted = array_flip($keys);

        foreach ($collection as $item) {
            if (isset($item->pivot)) {
                $index = $item->pivot->{$keyName};                
            } else {
                $index = $item->{$keyName};                
            }

            if (!is_array($sorted[$index])) {
                $sorted[$index] = [];
            }

            $sorted[$index][] = $item;
        }

        foreach ($sorted as $key => $item) {
            if (is_numeric($item)) {
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
     * @param  array  $keys
     * @param  string  $keyName
     * @return array
     */

    public function orderOnePerKey(Collection $collection, $keys, $keyName)
    {
        $sorted = array_flip($keys);

        foreach ($collection as $item) {
            $index = $item->{$keyName};
            $sorted[$index] = $item;
        }

        foreach ($sorted as $key => $item) {
            if (is_numeric($item)) {
                $sorted[$key] = null;
            }
        }

        return array_values($sorted);
    }
}