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
        return str_replace('batchload', '', $name);
    }

    private function getKeys($arguments, Relation $eloquentRelationship = null)
    {
        if (empty($arguments)) {
            if ($eloquentRelationship instanceof HasMany) {
                $keys = [ $this->{self::getHasOneOrManyParentKeyName($eloquentRelationship)} ];
            } elseif ($eloquentRelationship instanceof HasOne) {
                $keys = $this->{self::getHasOneOrManyParentKeyName($eloquentRelationship)};
            } elseif ($eloquentRelationship instanceof BelongsTo) {
                $keys = $this->{self::getBelongsToForeignKey($eloquentRelationship)};
            } elseif ($eloquentRelationship instanceof BelongsToMany) {
                $keys = [ $this->getKey() ];
            }
        } else {
            $keys = $arguments[0]; // this could be and array of int or an int
        }

        return $keys;
    }

    private function getDataLoaderFnName($keys)
    {
        if (is_array($keys)) {
            return 'loadMany';
        }
        return 'load';
    }

    /**
     * Wrapper function. From laravel 5.3 to 5.4, a function was renamed.
     * 
     * @param  Illuminate\Database\Eloquent\Relations\HasOneOrMany $relation
     * @return string
     */

    private static function getHasOneOrManyForeignKeyName(HasOneOrMany $relation)
    {
        if (method_exists($relation, 'getPlainForeignKey')) {
            return $relation->getPlainForeignKey();
        } else {
            // for laravel 5.4+
            return $relation->getForeignKeyName();
        }
    }

    /**
     * Monkey patch function. Function didn't exist in older versions of laravel 5.
     * 
     * @param  Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
     * @return string
     */

    // private static function compatibleGetForeignPivotKeyName(BelongsToMany $relation)
    // {
    //     if (method_exists($relation, 'getForeignPivotKeyName')) {
    //         // laravel 5.5+
    //         return $relation->getForeignPivotKeyName();
    //     } elseif (method_exists($relation, 'getQualifiedForeignKeyName')) {
    //         // laravel 5.4
    //         return explode('.', $relation->getQualifiedForeignKeyName())[1];
    //     } else {
    //         // laravel 5.2 - 5.3
    //         return explode('.', $relation->getForeignKey())[1];
    //     }
    // }

    /**
     * Monkey patch function. Function didn't exist in older versions of laravel 5.
     * 
     * @param  Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
     * @return string
     */

    private static function getBelongsToManyQualifiedForeignPivotKeyName(BelongsToMany $relation)
    {
        if (method_exists($relation, 'getQualifiedForeignPivotKeyName')) {
            // laravel 5.5+
            return $relation->getQualifiedForeignPivotKeyName();
        } elseif (method_exists($relation, 'getQualifiedForeignKeyName')) {
            // laravel 5.4
            return $relation->getQualifiedForeignKeyName();
        } else {
            // laravel 5.2 - 5.3
            return $relation->getForeignKey();
        }
    }

    /**
     * Monkey patch function. Function didn't exist in older versions of laravel 5.
     * 
     * @param  Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
     * @return string
     */

    private static function getBelongsToManyQualifiedRelatedPivotKeyName(BelongsToMany $relation)
    {
        if (method_exists($relation, 'getQualifiedRelatedPivotKeyName')) {
            // laravel 5.5+
            return $relation->getQualifiedRelatedPivotKeyName();
        } elseif (method_exists($relation, 'getQualifiedRelatedKeyName')) {
            // laravel 5.4
            return $relation->getQualifiedRelatedKeyName();            
        } else {
            // laravel 5.2 - 5.3
            return $relation->getOtherKey();
        }
    }



    private static function getHasOneOrManyParentKeyName(HasOneOrMany $relation)
    {
        // laravel 5.2+
        return last(explode('.', $relation->getQualifiedParentKeyName()));
    }

    private static function getBelongsToForeignKey(BelongsTo $relation)
    {
        // laravel 5.2+
        return $relation->getForeignKey();
    }

    private static function getBelongsToManyForeignKey(BelongsToMany $relation)
    {
        if (method_exists($relation, 'getForeignKey')) {
            // laravel 5.2 - 5.3        
            return last(explode('.', $relation->getForeignKey()));
        } elseif (method_exists($relation, 'getQualifiedForeignKeyName')) {
            // laravel 5.4
            return last(explode('.', $relation->getQualifiedForeignKeyName()));
        } else {
            // laravel 5.5+
            clock($relation->getForeignPivotKeyName());
            return $relation->getForeignPivotKeyName();
        }        
    }
}