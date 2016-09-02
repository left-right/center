# Center
An innovative CMS for Laravel 5. 

(walkthrough video to come)

### Installation
1. create a valid database connection

2. In Terminal:

    composer require left-right/center:dev-master

3. add this to $providers in config/app.php

        LeftRight\Center\CenterServiceProvider::class,

5. add this to $middleware in App/Http/Kernel.php

		\LeftRight\Center\Middleware\Permissions::class,

6. add this to $routeMiddleware in App/Http/Kernel.php

		'user' => \LeftRight\Center\Middleware\User::class,

7. run the following commands
		
		php artisan vendor:publish
		php artisan center:refresh

8. browse to your project's /center route to log in

