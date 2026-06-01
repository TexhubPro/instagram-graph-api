<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Laravel;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use TexHub\InstagramGraphApi\Config;
use TexHub\InstagramGraphApi\Instagram as InstagramClient;

class InstagramServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/instagram.php', 'instagram');

        $this->app->singleton(Config::class, function ($app): Config {
            return Config::fromArray((array) $app['config']->get('instagram', []));
        });

        $this->app->singleton(InstagramClient::class, function ($app): InstagramClient {
            return new InstagramClient($app->make(Config::class));
        });

        $this->app->alias(InstagramClient::class, 'instagram');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/instagram.php' => $this->app->configPath('instagram.php'),
            ], 'instagram-config');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [Config::class, InstagramClient::class, 'instagram'];
    }
}
