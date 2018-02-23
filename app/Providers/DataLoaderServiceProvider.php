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
    protected $dataLoaderPromiseAdapter;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        $graphQLPromiseAdapter = new SyncPromiseAdapter();
        $this->dataLoaderPromiseAdapter = new WebonyxGraphQLSyncPromiseAdapter($graphQLPromiseAdapter);
        GraphQL::setPromiseAdapter($graphQLPromiseAdapter);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(DataLoaderManager::class, function() {           
            return new DataLoaderManager($this->dataLoaderPromiseAdapter);
        });
    }
}
