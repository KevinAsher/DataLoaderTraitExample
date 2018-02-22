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
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $graphQLPromiseAdapter = new SyncPromiseAdapter();
        GraphQL::setPromiseAdapter($graphQLPromiseAdapter);
        $this->app->singleton(PromiseAdapterInterface::class, function () use ($graphQLPromiseAdapter) {
            return new WebonyxGraphQLSyncPromiseAdapter($graphQLPromiseAdapter);
        });
        $this->app->singleton('DataLoader', function($app) {
            return new DataLoaderManager($app->make(PromiseAdapterInterface::class));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            WebonyxGraphQLSyncPromiseAdapter::class,
            DataLoaderManager::class
        ];
    }
}
