<?php 

namespace Rasim\Config;

use Illuminate\Support\ServiceProvider;

class ConfigWriterServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

        $this->app->bind('Rasim\Config\ConfigWriter', function($app)
        {
           $loader = $app->getConfigLoader();
           return new Writer($loader ,$app['env'],$app['path']);
        });
        
        $this->app['config'] = $this->app->share(function($app)
        {
            return $app->make('Rasim\Config\ConfigWriter');
        });

	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array("writer");
	}

}
