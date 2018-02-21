<?php

namespace App\GraphQL\DataLoader;

use \ReflectionMethod;
use \ReflectionClass;
use Overblog\DataLoader\DataLoader;
use Overblog\PromiseAdapter\PromiseAdapterInterface;

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

    public function getSupportedRelations() {
        return $this->supportedRelations;
    }

    public function bootDataLoader($modelClassName, $methodName = null)
    {
        if ($methodName) {
            $model = new $modelClassName;

            $reflectionMethod = new ReflectionMethod($modelClassName, $methodName);
            if ($reflectionMethod->hasReturnType()) {

                $fnRelationType = (new ReflectionClass($reflectionMethod->getReturnType()->getName()))->getShortName();
                $fnRelationType = strtolower($fnRelationType);

                if (!in_array($fnRelationType, $this->supportedRelations)) {
                    throw new \Exception("Can't batch load models with ${fnRelationType} with model ${modelClassName}");
                }

                $eloquentRelationship = $reflectionMethod->invoke($model);
                $methodName = strtolower($methodName);

                $this->setRelationshipFnReturnType($methodName, $eloquentRelationship);
                
                $this->setDataLoader($modelClassName, $methodName, new DataLoader(
                    $this->buildBatchLoadFn($eloquentRelationship, $fnRelationType), 
                    $this->promiseAdapter
                ));

                return $this->getDataLoader($modelClassName, $methodName);
            } else {
                throw new \Exception("method batchLoad${$method} in ${modelClassName} needs to specify a return type");
            }
        }

        $this->setDataLoader($modelClassName, null, new DataLoader($this->buildBatchLoadFn(), $this->promiseAdapter));

        return $this->getDataLoader($modelClassName);
    }

    public function getRelationshipFnReturnType($methodName) {
        return $this->relationshipFnReturnTypeMap[$methodName];
    }

    public function setRelationshipFnReturnType($methodName, $eloquentRelationship) {
        $this->relationshipFnReturnTypeMap[$methodName] = $eloquentRelationship;
    }

    public function getDataLoader($modelClassName, $methodName = null) {
        if ($methodName) {
            return isset($this->relationDataLoaders[$modelClassName][$methodName]) 
                ? $this->relationDataLoaders[$modelClassName][$methodName]
                : $this->bootDataLoader($modelClassName, $methodName);
        }

        return isset($this->relationDataLoaders[$modelClassName])
            ? $this->relationDataLoaders[$modelClassName]
            : $this->bootDataLoader($modelClassName);
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

    private static function buildBatchLoadFn(Relation $eloquentRelationship = null, $relationName = null)
    {
        return function ($keys) use ($eloquentRelationship, $relationName) {

            $keyName = null;

            if ($eloquentRelationship && $relationName) {
                switch ($relationName) {
                    case 'belongsto':
                        return get_class($eloquentRelationship->getRelated())::$dataLoader->loadMany($keys);
                    case 'hasone':
                        $keyName = self::getHasOneOrManyForeignKeyName($eloquentRelationship);
                        $collection = get_class($eloquentRelationship->getRelated())::whereIn($keyName, $keys)->get();
                        $collection = self::orderOnePerKey($collection, $keys, $keyName);
                        break;
                    case 'hasmany':
                        $keyName = self::getHasOneOrManyForeignKeyName($eloquentRelationship);
                        $collection = get_class($eloquentRelationship->getRelated())::whereIn($keyName, $keys)->get();
                        $collection = self::orderManyPerKey($collection, $keys, $keyName);
                        break;
                    case 'belongstomany':
                        $keyName = self::getBelongsToManyForeignKey($eloquentRelationship);
                        $pivotTable = $eloquentRelationship->getTable();
                        $foreignPivotKey = self::getBelongsToManyQualifiedForeignPivotKeyName($eloquentRelationship);
                        $relatedPivotKey = self::getBelongsToManyQualifiedRelatedPivotKeyName($eloquentRelationship);
                        $relatedModelInstance = $eloquentRelationship->getRelated();

                        $collection = get_class($relatedModelInstance)::join($pivotTable, $relatedModelInstance->getQualifiedKeyName(), '=', $relatedPivotKey)
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
}
