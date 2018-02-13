<?php

namespace App\Traits;

trait DataLoaderHelpersTrait
{
    public static function getModelClassOwnMethods() {
        $model = new static();

        return array_filter(get_class_methods($model), function ($method) {
            return !method_exists('Illuminate\Database\Eloquent\Model', $method);
        });
    }
}