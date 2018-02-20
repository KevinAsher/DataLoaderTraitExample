<?php

namespace App\Traits;

use Overblog\DataLoader\DataLoader;
use Overblog\PromiseAdapter\PromiseAdapterInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use \ReflectionClass;
use \ReflectionMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait DataLoaderTrait
{
    use DataLoaderHelpersTrait;

    static public $relationDataLoaders = [];
    static public $dataLoader;
    static protected $promiseAdapter;

    static private $LOAD = 'batchload';
    static private $LOAD_MANY = 'batchloadmany';
    static private $relationshipFnReturnTypeMap = [];


    /**
     * Handles calls to dynamic functions in the following format:
     *   batch[LOAD_TYPE][DEFINED_ELOQUENT_RELATION_FUNCTION]
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
        $relationshipFnName = self::getRelationshipFnName($name);
        $loadType = self::getLoadType($name);


        if ($name == self::$LOAD || $name == self::$LOAD_MANY) {

            $keys = $this->getKeys($arguments, $loadType);
            return self::$dataLoader->{$this->getDataLoaderFnName($loadType)}($keys);

        } elseif (in_array($relationshipFnName, array_keys(self::$relationDataLoaders)) && !empty($loadType)) {


            $eloquentRelationship = self::$relationshipFnReturnTypeMap[$relationshipFnName];
            $keys = $this->getKeys($arguments, $loadType, $eloquentRelationship);

            if ($eloquentRelationship instanceof BelongsTo) {
                $key = $keys;
                return get_class($eloquentRelationship->getRelated())::$dataLoader->load($key);
            }

            $promise = self::$relationDataLoaders[$relationshipFnName]->{$this->getDataLoaderFnName($loadType)}($keys);
            if ($eloquentRelationship instanceof HasMany || $eloquentRelationship instanceof BelongsToMany) {
                $promise = $promise->then(function ($collection) use ($eloquentRelationship) {
                    $collection = $collection[0];
                    $relatedModelInstance = $eloquentRelationship->getRelated();
                    $relatedModelDataLoader = get_class($relatedModelInstance)::$dataLoader;

                    /* Precache all the models. The collection order must be in the same order of the $keys array. */
                    foreach ($collection as $model) {
                        /* $model should be garanteed not to be null since the relationships use inner joins */
                        $relatedModelDataLoader->prime($model->getKey(), $model);
                    }

                    return $relatedModelDataLoader->loadMany(array_column($collection, $relatedModelInstance->getKeyName()));
                });
            }

            return $promise;
        } else {
            return parent::__call($name, $arguments);
        }

    }

    /**
     * Boots up the trait.
     */

    public static function bootDataLoaderTrait()
    {
        self::$promiseAdapter = app()->make(PromiseAdapterInterface::class);
        $model = new static();
        $relations = [
            'hasmany', 'hasmanythrough', 'belongstomany', 'hasone', 'belongsto', 'morphone', 'morphto', 'morphmany',
            'morphtomany'
        ];

        foreach (self::getModelClassOwnMethods() as $method) {
            $reflectionMethod = new ReflectionMethod(static::class, $method);
            if ($reflectionMethod->hasReturnType()) {
                $fnRelationType = (new ReflectionClass($reflectionMethod->getReturnType()->getName()))->getShortName();
                $fnRelationType = strtolower($fnRelationType);

                if (!in_array($fnRelationType, $relations)) continue;

                $eloquentRelationship = $reflectionMethod->invoke($model);
                $method = strtolower($method);

                self::$relationshipFnReturnTypeMap[$method] = $eloquentRelationship;
                self::$relationDataLoaders[$method] = new DataLoader(self::buildBatchLoadFn($eloquentRelationship, $fnRelationType), self::$promiseAdapter);
            }
        }

        self::$dataLoader = new DataLoader(self::buildBatchLoadFn(), self::$promiseAdapter);        
    }


    /**
     * Builds the batch function that resolves all the promises and queries the database.
     * 
     * @param  \Illuminate\Database\Eloquent\Relations\Relation|null $eloquentRelationship
     * @param  string|null  $relationName
     * @return \Closure   
     */

    private static function buildBatchLoadFn(Relation $eloquentRelationship = null, $relationName = null)
    {
        return function ($keys) use ($eloquentRelationship, $relationName) {

            $keyName = null;

            if ($eloquentRelationship && $relationName) {
                switch ($relationName) {
                    case 'hasone':
                        $keyName = self::getForeignKeyName($eloquentRelationship);
                        $collection = get_class($eloquentRelationship->getRelated())::whereIn($keyName, $keys)->get();
                        $collection = self::orderOnePerKey($collection, $keys, $keyName);
                        break;
                    case 'hasmany':
                        $keyName = self::getForeignKeyName($eloquentRelationship);
                        $collection = get_class($eloquentRelationship->getRelated())::whereIn($keyName, $keys)->get();
                        $collection = self::orderManyPerKey($collection, $keys, $keyName);
                        break;
                    case 'belongstomany':
                        $keyName = self::getForeignPivotKeyName($eloquentRelationship);
                        $pivotTable = $eloquentRelationship->getTable();
                        $foreignPivotKey = self::getQualifiedForeignPivotKeyName($eloquentRelationship);
                        $relatedPivotKey = self::getQualifiedRelatedPivotKeyName($eloquentRelationship);
                        $relatedModelInstance = $eloquentRelationship->getRelated();
                        $collection = get_class($relatedModelInstance)::join($pivotTable, $relatedModelInstance->getQualifiedKeyName(), '=',  $relatedPivotKey)
                            ->whereIn($foreignPivotKey, $keys)->get();
                        $collection = self::orderManyPerKey($collection, $keys, $keyName);

                        break;
                    default:
                        throw new \Exception("$relationName not supported", 1);
                        break;
                }
            } else {
                $collection = static::find($keys);
                $keyName = (!$collection->isEmpty()) ? $collection->first()->getKeyName() : null;
                $collection = self::orderOnePerKey($collection, $keys, $keyName);
            }

            return self::$promiseAdapter->createFulfilled($collection);
        };
    }

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

    protected static function orderManyPerKey(Collection $collection, $keys, $keyName)
    {
        $sorted = array_flip($keys);

        foreach ($keys as $key) {
            $sorted[$key] = $collection->where($keyName, $key)->all();
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

    /**
     * Loader creator helper.
     * 
     * @param  \Closure  $batchLoadFn
     * @param  string  $keyName
     * @return \Overblog\DataLoader\DataLoader
     */

    protected static function createLoader($batchLoadFn, $keyName)
    {
        return new DataLoader(function ($keys) use ($batchLoadFn, $keyName) {
            $collection = $batchLoadFn($keys);

            return self::$promiseAdapter->createFulfilled(self::orderManyPerKey($collection, $keys, $keyName));
        }, self::$promiseAdapter);
    }
}