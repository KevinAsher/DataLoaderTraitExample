<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use GraphQL\GraphQL;
use Overblog\DataLoader\Promise\Adapter\Webonyx\GraphQL\SyncPromiseAdapter;
use Overblog\PromiseAdapter\Adapter\WebonyxGraphQLSyncPromiseAdapter;
use Overblog\PromiseAdapter\PromiseAdapterInterface;
use App\GraphQL\DataLoader\DataLoaderManager;

class DataLoaderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PromiseAdapterInterface::class, function () {
            $graphQLPromiseAdapter = new SyncPromiseAdapter();
            $dataLoaderPromiseAdapter = new WebonyxGraphQLSyncPromiseAdapter($graphQLPromiseAdapter);
            GraphQL::setPromiseAdapter($graphQLPromiseAdapter);
            return $dataLoaderPromiseAdapter;
        });
        $this->app->singleton('DataLoader', function($app) {
            return new DataLoaderManager($app->make(PromiseAdapterInterface::class));
        });
    }
}
