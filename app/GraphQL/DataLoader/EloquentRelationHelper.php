<?php

namespace App\GraphQL\DataLoader;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

trait EloquentRelationHelper
{
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
     * Wrapper function. From laravel 5.3 to 5.4, a function was renamed.
     * 
     * @param  Illuminate\Database\Eloquent\Relations\BelongsTo $relation
     * @return string
     */

    private static function getBelongsToParentKeyName(BelongsTo $relation)
    {
        if (method_exists($relation, 'getOwnerKey')) {
            // laravel 5.4+
            return $relation->getOwnerKey();
        }
        // laravel 5.2 - 5.3
        return $relation->getOtherKey();
    }

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