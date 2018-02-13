<?php

namespace App\Traits;

use ErrorException;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

trait RelationshipsTrait
{
    public static function relationships()
    {

        $model = new static;

        $relationships = [];

        foreach ((new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class != get_class($model) ||
                !empty($method->getParameters()) ||
                $method->getName() == __FUNCTION__) {
                continue;
            }

            try {
                $return = $method->invoke($model);

                if ($return instanceof Relation) {
                    $relationships[$method->getName()] = [
                        'type' => (new ReflectionClass($return))->getShortName(),
                        'model' => (new ReflectionClass($return->getRelated()))->getName()
                    ];
                }
            } catch (ErrorException $e) {
            }
        }

        return $relationships;
    }

    public static function getPropertiesFromMethods()
    {
        $model = new static;

        $methods = get_class_methods($model);
        if ($methods) {
            sort($methods);
            foreach ($methods as $method) {
                if (!method_exists('Illuminate\Database\Eloquent\Model', $method)
                    && !Str::startsWith($method, 'get')) {
                    //Use reflection to inspect the code, based on Illuminate/Support/SerializableClosure.php
                    $reflection = new \ReflectionMethod($model, $method);
                    $file = new \SplFileObject($reflection->getFileName());
                    $file->seek($reflection->getStartLine() - 1);
                    $code = '';
                    while ($file->key() < $reflection->getEndLine()) {
                        $code .= $file->current();
                        $file->next();
                    }
                    $code = trim(preg_replace('/\s\s+/', '', $code));
                    $begin = strpos($code, 'function(');
                    $code = substr($code, $begin, strrpos($code, '}') - $begin + 1);
                    foreach (array(
                        'hasMany',
                        'hasManyThrough',
                        'belongsToMany',
                        'hasOne',
                        'belongsTo',
                        'morphOne',
                        'morphTo',
                        'morphMany',
                        'morphToMany'
                    ) as $relation) {
                        $search = '$this->' . $relation . '(';
                        if ($pos = stripos($code, $search)) {
                            //Resolve the relation's model to a Relation object.
                            $relationObj = $model->$method();
                            if ($relationObj instanceof Relation) {
                                $relatedModel = '\\' . get_class($relationObj->getRelated());
                                $relations = ['hasManyThrough', 'belongsToMany', 'hasMany', 'morphMany', 'morphToMany'];
                                if (in_array($relation, $relations)) {
                                    //Collection or array of models (because Collection is Arrayable)
                                    var_dump([
                                        $method,
                                        static::getCollectionClass($relatedModel) . '|' . $relatedModel . '[]',
                                    ]);
                                        
                                } elseif ($relation === "morphTo") {
                                    // Model isn't specified because relation is polymorphic
                                    var_dump([
                                        $method,
                                        '\Illuminate\Database\Eloquent\Model|\Eloquent',
                                        
                                    ]);
                                } else {
                                    //Single model is returned
                                    var_dump([
                                        $method,
                                        $relatedModel,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Determine a model classes' collection type.
     *
     * @see http://laravel.com/docs/eloquent-collections#custom-collections
     * @param string $className
     * @return string
     */
    private static function getCollectionClass($className)
    {
        // Return something in the very very unlikely scenario the model doesn't
        // have a newCollection() method.
        if (!method_exists($className, 'newCollection')) {
            return '\Illuminate\Database\Eloquent\Collection';
        }
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new $className;
        return '\\' . get_class($model->newCollection());
    }
}