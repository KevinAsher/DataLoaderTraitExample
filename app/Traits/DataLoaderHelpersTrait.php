<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

trait DataLoaderHelpersTrait
{
    public static function getModelClassOwnMethods()
    {
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

    /**
     * Monkey patch function. Function was renamed from getPlainForeignKey to getForeignKeyName.
     * Commit: https://github.com/laravel/framework/commit/294c006288ee182eeddf3c6deb23a35032a9219d#diff-c0acf9e1f52186e33654e8b466773f77
     * 
     * @param  Illuminate\Database\Eloquent\Relations\HasOneOrMany $relation
     * @return string
     */

    private static function getForeignKeyName(HasOneOrMany $relation)
    {
        if (method_exists($relation, 'getPlainForeignKey')) {
            return $relation->getPlainForeignKey();
        } else {
            return $relation->getForeignKeyName();
        }
    }

    /**
     * Monkey patch function. Function didn't exist in older versions of laravel 5.
     * 
     * @param  Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
     * @return string
     */

    private static function getForeignPivotKeyName(BelongsToMany $relation)
    {
        if (method_exists($relation, 'getForeignPivotKeyName')) {
            return $relation->getForeignPivotKeyName();
        } else {
            return explode('.', $relation->getForeignKey())[1];
        }
    }

    /**
     * Monkey patch function. Function didn't exist in older versions of laravel 5.
     * 
     * @param  Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
     * @return string
     */

    private static function getQualifiedForeignPivotKeyName(BelongsToMany $relation)
    {
        if (method_exists($relation, 'getQualifiedForeignPivotKeyName')) {
            return $relation->getQualifiedForeignPivotKeyName();
        } else {
            return $relation->getForeignKey();
        }
    }

    /**
     * Monkey patch function. Function didn't exist in older versions of laravel 5.
     * 
     * @param  Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
     * @return string
     */

    private static function getQualifiedRelatedPivotKeyName(BelongsToMany $relation)
    {
        if (method_exists($relation, 'getQualifiedRelatedPivotKeyName')) {
            return $relation->getQualifiedRelatedPivotKeyName();
        } else {
            return $relation->getOtherKey();
        }
    }
}