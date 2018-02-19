<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait DataLoaderHelpersTrait
{
    public static function getModelClassOwnMethods() {
        $model = new static();

        return array_filter(get_class_methods($model), function ($method) {
            return !method_exists('Illuminate\Database\Eloquent\Model', $method);
        });
    }

    private static function getRelationshipFnName($name)
    {
        $loadType = self::$LOAD_MANY;
        $relationshipName = str_replace($loadType, '', $name);
        if ($relationshipName == $name) {
            $loadType = self::$LOAD;
            $relationshipName = str_replace($loadType, '', $name);
        }

        return $relationshipName;
    }

    private static function getLoadType($name)
    {
        if (strpos($name, self::$LOAD_MANY) !== false) {
            return self::$LOAD_MANY;
        } elseif (strpos($name, self::$LOAD) !== false) {
            return self::$LOAD;
        }

        return null;
    }


    private function getKeys($arguments, $loadType, Relation $eloquentRelationship = null)
    {
        if (empty($arguments)) {
            if ($eloquentRelationship instanceof HasMany || $eloquentRelationship instanceof HasOne) {
                $keys = $this->getKey();

                if ($loadType == self::$LOAD_MANY) {
                    $keys = [$this->getKey()];
                }
            } else if ($eloquentRelationship instanceof BelongsTo) {
                $keys = $this->{$eloquentRelationship->getForeignKey()};
            } else if ($eloquentRelationship instanceof BelongsToMany) {
                $keys = [$this->getKey()];
            }
        } else {
            $keys = $arguments[0]; // this could be and array of int or an int
        }
        return $keys;
    }

    private function getDataLoaderFnName($loadType)
    {
        return str_replace('batch', '', $loadType);
    }

}