<?php namespace LeftRight\Center;
	
use Illuminate\Support\ServiceProvider;

class CenterServiceProvider extends ServiceProvider {
	
	public function register()
	{
		$this->mergeConfigFrom(
			__DIR__.'/config.php', 'courier'
		);		
	}

	public function boot()
	{
		$this->loadViewsFrom(__DIR__.'/views', 'center');
		$this->loadTranslationsFrom(__DIR__.'/translations', 'center');
		include __DIR__.'/routes.php';
	}
	
}