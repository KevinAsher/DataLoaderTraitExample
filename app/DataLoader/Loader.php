<?php
namespace App\DataLoader;

use Overblog\DataLoader\DataLoader;
use Overblog\PromiseAdapter\PromiseAdapterInterface;

abstract class Loader {

    /**
     * @var PromiseAdapterInterface
     */
    protected $promiseAdapter = null;
    protected $dataLoader;

    public function __construct(
        PromiseAdapterInterface $promiseAdapter
    ) {
        $this->promiseAdapter = $promiseAdapter;
        $this->dataLoader = new DataLoader(function ($keys) {
            // $keys = array_map(function ($key) {
            //     return is_array($key) ? $key[0] : $key;
            // }, $keys);
           
            return call_user_func([$this, 'batchLoad'], $keys);
        }, $promiseAdapter);
    }

    public function load($key) {
        return $this->dataLoader->load($key);
    }

    public function loadMany($keys) {
        return $this->dataLoader->loadMany($keys);
    }
    
    public function prime($key, $value) {
        return $this->dataLoader->prime($key, $value);
    }

    abstract function batchLoad($keys);
}
