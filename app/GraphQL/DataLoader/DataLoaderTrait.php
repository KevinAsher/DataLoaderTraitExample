<?php

namespace App\GraphQL\DataLoader;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Overblog\DataLoader\DataLoader;


trait DataLoaderTrait { 
    use EloquentRelationHelper;

    protected static $batchFnPrefix = 'batchload'; 
    
    /**
     * Handles calls to dynamic functions in the following format:
     *   batchLoad[DEFINED_ELOQUENT_RELATION_FUNCTION]
     *   or
     *   batchLoad
     * 
     * Limitations: The DEFINED_ELOQUENT_RELATION_FUNCTION must specify the return type
     * Supported relation types:
     *  - HasMany   (one to many)
     *  - BelongsTo (one to many inverse)
     *  - BelongsToMany (many to many & inverse)
     *  - HasOne (one to one)
     */

    public function __call($name, $arguments)
    {
        $name = strtolower($name);
                
        if (self::hasRequiredPrefix($name)){
            $relationshipFnName = self::getRelationshipFnName($name);
            
            if (empty($relationshipFnName)) {
                $keys = $this->getKeysForDataLoader($arguments);

                return app('DataLoader')->getDataLoaderAndBootIfDosentExist(self::class)->{self::getDataLoaderFnName($keys)}($keys);

            } else {
                $eloquentRelationship = app('DataLoader')->getRelationshipFnReturnType(self::class, $relationshipFnName);
                $keys = $this->getKeysForDataLoader($arguments, $eloquentRelationship);
                $promise = app('DataLoader')->getDataLoaderAndBootIfDosentExist(self::class, $relationshipFnName)->{self::getDataLoaderFnName($keys)}($keys);
                if ($eloquentRelationship instanceof HasMany || $eloquentRelationship instanceof BelongsToMany) {
                    $promise = $promise->then(function ($collection) use ($eloquentRelationship) {
                        $collection = $collection[0];
                        $relatedModelInstance = $eloquentRelationship->getRelated();
                        $relatedModelDataLoader = app('DataLoader')->getDataLoaderAndBootIfDosentExist(get_class($relatedModelInstance));

                    /* Precache all the models. The collection order must be in the same order of the $keys array. */
                        foreach ($collection as $model) {
                        /* $model should be garanteed not to be null since the relationships use inner joins */
                            $relatedModelDataLoader->prime($model->getKey(), $model);
                        }

                        return $relatedModelDataLoader->loadMany(array_column($collection, $relatedModelInstance->getKeyName()));
                    });
                }

                return $promise;
            }
        } else {
            return parent::__call($name, $arguments);
        }

    }

    private static function getRelationshipFnName($name)
    {
        return str_replace(self::$batchFnPrefix, '', $name);
    }

    private static function hasRequiredPrefix($methodName)
    {
        return strpos($methodName, self::$batchFnPrefix) !== false;
    }
    
    private function getKeysForDataLoader($arguments, Relation $eloquentRelationship = null)
    {
        if (empty($arguments)) {
            if ($eloquentRelationship instanceof HasMany) {
                $keys = [$this->{self::getHasOneOrManyParentKeyName($eloquentRelationship)}];
            } elseif ($eloquentRelationship instanceof HasOneOrMany) {
                $keys = $this->{self::getHasOneOrManyParentKeyName($eloquentRelationship)};
            } elseif ($eloquentRelationship instanceof BelongsTo) {
                $keys = $this->{self::getBelongsToForeignKey($eloquentRelationship)};
            } elseif ($eloquentRelationship instanceof BelongsToMany) {
                $keys = [$this->getKey()];
            }
        } else {
            $keys = $arguments[0]; // this could be and array of int or an int
        }

        return $keys;
    }

    private static function getDataLoaderFnName($keys)
    {
        if (is_array($keys)) {
            return 'loadMany';
        }
        return 'load';
    }

    /**
     * Loader creator helper.
     * 
     * @param  \Closure  $batchLoadFn
     * @param  string  $keyName
     * @return \Overblog\DataLoader\DataLoader
     */

    public function createDataLoaderOnce($name, $keyName, $batchLoadFn)
    {
        $dataLoader = app('DataLoader')->getDataLoader(self::class, $name);

        if (!$dataLoader) {
            $promiseAdapter = app('DataLoader')->getPromiseAdapter();
            $dataLoader = new DataLoader(function ($keys) use ($batchLoadFn, $keyName, $promiseAdapter) {
                $collection = $batchLoadFn($keys);

                return $promiseAdapter->createFulfilled(app('DataLoader')->orderManyPerKey($collection, $keys, $keyName));
            }, $promiseAdapter);

            app('DataLoader')->setDataLoader(self::class, $name, $dataLoader);
        }
        
        return $dataLoader;
    }
}