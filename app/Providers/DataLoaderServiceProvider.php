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
     * @var SyncPromiseAdapter
     */
    private $graphQLPromiseAdapter;
    /**
     * @var WebonyxGraphQLSyncPromiseAdapter
     */
    private $dataLoaderPromiseAdapter;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->graphQLPromiseAdapter = new SyncPromiseAdapter();
        $this->dataLoaderPromiseAdapter = new WebonyxGraphQLSyncPromiseAdapter($this->graphQLPromiseAdapter);
        GraphQL::setPromiseAdapter($this->graphQLPromiseAdapter);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PromiseAdapterInterface::class, function () {
            $this->dataLoaderPromiseAdapter;
        });
        $this->app->singleton('DataLoader', function() {
            return new DataLoaderManager($this->dataLoaderPromiseAdapter);
        });
    }
}
