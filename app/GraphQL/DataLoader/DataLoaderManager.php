<?php

namespace App\GraphQL\DataLoader;

use \ReflectionMethod;
use \ReflectionClass;
use Overblog\DataLoader\DataLoader;
use Overblog\PromiseAdapter\PromiseAdapterInterface;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DataLoaderManager
{
    use DataLoaderHelper, EloquentRelationHelper;

    protected $relationDataLoaders = [];
    protected $modelDataLoaders = [];
    protected $relationshipFnReturnTypeMap = [];
    protected $promiseAdapter = [];
    protected $supportedRelations = ['hasmany', 'belongstomany', 'hasone', 'belongsto'];

    public function __construct(PromiseAdapterInterface $promiseAdapter)
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    public function getPromiseAdapter() {
        return $this->promiseAdapter;
    }

    public function getSupportedRelations() {
        return $this->supportedRelations;
    }

    public function bootDataLoader($modelClassName, $methodName = null)
    {
        if ($methodName) {
            $methodName = strtolower($methodName);

            $eloquentRelationship = $this->getRelationshipFnReturnType($modelClassName, $methodName);
                
            $this->setDataLoader($modelClassName, $methodName, new DataLoader(
                $this->buildBatchLoadFn($modelClassName, $eloquentRelationship), 
                $this->promiseAdapter
            ));

            return $this->getDataLoader($modelClassName, $methodName);
        }

        $this->setDataLoader($modelClassName, null, new DataLoader($this->buildBatchLoadFn($modelClassName), $this->promiseAdapter));
        return $this->getDataLoader($modelClassName);
    }

    public function getRelationshipFnReturnType($modelClassName, $methodName) {
        if (isset($this->relationshipFnReturnTypeMap[$modelClassName][$methodName])) {
            return $this->relationshipFnReturnTypeMap[$modelClassName][$methodName];            
        }

        $model = new $modelClassName;

        $reflectionMethod = new ReflectionMethod($modelClassName, $methodName);
        if ($reflectionMethod->hasReturnType()) {

            $fnRelationType = (new ReflectionClass($reflectionMethod->getReturnType()->getName()))->getShortName();
            $fnRelationType = strtolower($fnRelationType);

            if (!in_array($fnRelationType, $this->supportedRelations)) {
                throw new \Exception("Can't batch load models with $fnRelationType with model $modelClassName");
            }

            $this->relationshipFnReturnTypeMap[$modelClassName][$methodName] = $reflectionMethod->invoke($model);
            
            return $this->relationshipFnReturnTypeMap[$modelClassName][$methodName];
        } else {
            throw new \Exception("batch method on relationship $methodName in $modelClassName needs to specify a return type");
        }
    }

    public function clearRelationshipInitialConstraints($eloquentRelationship) {
        $builder = $eloquentRelationship->getQuery()->getQuery();
        switch(true) {
            case $eloquentRelationship instanceof BelongsTo:
            case $eloquentRelationship instanceof BelongsToMany:            
                $builder->wheres = array_slice($builder->wheres, 2);
                break;
            case $eloquentRelationship instanceof HasOne:
            case $eloquentRelationship instanceof HasMany:
                $builder->wheres = array_slice($builder->wheres, 3);
                break;
        }

        return $eloquentRelationship;
    }

    public function getDataLoader($modelClassName, $methodName = null) {
        if ($methodName) {
            return isset($this->relationDataLoaders[$modelClassName][$methodName]) 
                ? $this->relationDataLoaders[$modelClassName][$methodName]
                : null;
        }
        return isset($this->modelDataLoaders[$modelClassName])
            ? $this->modelDataLoaders[$modelClassName]
            : null;
    }

    public function getDataLoaderAndBootIfDosentExist($modelClassName, $methodName = null) {
        if ($methodName) {
            return $this->getDataLoader($modelClassName, $methodName) ?: $this->bootDataLoader($modelClassName, $methodName);
        }
        return $this->getDataLoader($modelClassName) ? : $this->bootDataLoader($modelClassName);        
    }

    public function setDataLoader($modelClassName, $methodName = null, DataLoader $dataLoader) {
        if ($methodName) {
            $this->relationDataLoaders[$modelClassName][$methodName] = $dataLoader;
        } else {
            $this->modelDataLoaders[$modelClassName] = $dataLoader;
        }
    }

    /**
     * Builds the batch function that resolves all the promises and queries the database.
     * 
     * @param  \Illuminate\Database\Eloquent\Relations\Relation|null $eloquentRelationship
     * @param  string|null  $relationName
     * @return \Closure   
     */

    protected function buildBatchLoadFn($modelClassName, Relation $eloquentRelationship = null)
    {
        return function ($keys) use ($modelClassName, $eloquentRelationship) {

            $keyName = null;

            if ($eloquentRelationship) {
                $eloquentRelationship = $this->clearRelationshipInitialConstraints($eloquentRelationship);
                switch (true) {
                    case $eloquentRelationship instanceof BelongsTo:
                        $keyName = self::getBelongsToParentKeyName($eloquentRelationship);
                        $collection = $eloquentRelationship->whereIn($keyName, $keys)->get();
                        $modelDataLoader = $this->getDataLoaderAndBootIfDosentExist(get_class($eloquentRelationship->getRelated()));                        
                        $collection->each(function ($model) use ($modelDataLoader) {
                            $modelDataLoader->prime($model->getKey(), $model);
                        });
                        $collection = $this->orderOnePerKey($collection, $keys, $keyName);
                        
                        return $modelDataLoader->loadMany($keys);

                    case $eloquentRelationship instanceof HasOne:
                        $keyName = self::getHasOneOrManyForeignKeyName($eloquentRelationship);
                        $collection = $eloquentRelationship->whereIn($keyName, $keys)->get();
                        $collection = $this->orderOnePerKey($collection, $keys, $keyName);
                        break;
                    case $eloquentRelationship instanceof HasMany:
                        $keyName = self::getHasOneOrManyForeignKeyName($eloquentRelationship);
                        $collection = $eloquentRelationship->whereIn($keyName, $keys)->get();
                        $collection = $this->orderManyPerKey($collection, $keys, $keyName);
                        break;
                    case $eloquentRelationship instanceof BelongsToMany:
                        $keyName = self::getBelongsToManyForeignKey($eloquentRelationship);
                        $foreignPivotKey = self::getBelongsToManyQualifiedForeignPivotKeyName($eloquentRelationship);

                        $collection = $eloquentRelationship->whereIn($foreignPivotKey, $keys)->get();                        

                        $collection = $this->orderManyPerKey($collection, $keys, $keyName);
                        
                        break;
                    default:
                        throw new \Exception("relation " . get_class($eloquentRelationship) . " not supported", 1);
                        break;
                }
            } else {
                $collection = $modelClassName::find($keys);
                $keyName = (!$collection->isEmpty()) ? $collection->first()->getKeyName() : null;
                $collection = $this->orderOnePerKey($collection, $keys, $keyName);
            }

            return $this->promiseAdapter->createFulfilled($collection);
        };
    }
}
