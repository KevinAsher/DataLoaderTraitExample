<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Overblog\DataLoader\Promise\Adapter\Webonyx\GraphQL\SyncPromiseAdapter;
use GraphQL\Executor\Promise\Promise;

class PromiseFunctionality extends Controller
{
    public function index()
    {
        // $dataLoader = new DataLoader(function ($keys) {
        //     return 1;
        // }, $promiseAdapter);
        
        // $promise = new Promise();
        // $promise->then(
        //     function($value) {
        //         echo "Success!";
        //     },
        //     function($reason) {
        //         echo "The promise was rejected!";
        //     }
        // );
        true;

        echo "yolo";
        echo "nice";
    }
}
