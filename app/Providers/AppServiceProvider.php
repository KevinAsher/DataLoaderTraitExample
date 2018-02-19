<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

use GraphQL\GraphQL;
use Overblog\DataLoader\Promise\Adapter\Webonyx\GraphQL\SyncPromiseAdapter;
use Overblog\PromiseAdapter\Adapter\WebonyxGraphQLSyncPromiseAdapter;

class AppServiceProvider extends ServiceProvider
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
        Schema::defaultStringLength(191); // fix
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
        $this->app->singleton('Overblog\PromiseAdapter\PromiseAdapterInterface', function () {
            return $this->dataLoaderPromiseAdapter;
        });
        $this->app->singleton('PromiseAdapter', function () {
            return $this->graphQLPromiseAdapter;
        });
    }
}
