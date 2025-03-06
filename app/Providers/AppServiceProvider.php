<?php

namespace App\Providers;

use App\Models\Post;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerSearchClient();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootSearchable();
    }

    private function registerSearchClient(): void
    {
        $this->app->bind(Client::class, function ($app) {
            return ClientBuilder::create()
                ->setHosts($app['config']->get('services.search.hosts'))
                ->build();
        });
    }

    private function bootSearchable(): void
    {
        Post::bootSearchable();
    }
}
