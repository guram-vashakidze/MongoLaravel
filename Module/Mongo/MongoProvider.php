<?php

namespace App\Module\Mongo;

use Illuminate\Support\ServiceProvider;

class MongoProvider extends ServiceProvider
{

    protected $defer = true;
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MongoSetting::class, function ()
        {
            return new MongoSetting();
        });
        $this->app->singleton(MongoModel::class, function () {
            return new MongoModel(app('App\Module\Mongo\MongoSetting'));
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind('mongo', '\App\Module\Mongo\MongoModel');
    }

    public function provides() {
        return [MongoModel::class,MongoSetting::class];
    }
}
