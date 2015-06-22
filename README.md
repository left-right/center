# Center
An innovative CMS for Laravel 5. 

(walkthrough video to come)

### Installation
1. create a valid database connection

2. In Terminal:

    composer require left-right/center:dev-master

3. add these to $providers in config/app.php

    		'LeftRight\Center\CenterServiceProvider',
			'Illuminate\Html\HtmlServiceProvider',
			'Maatwebsite\Excel\ExcelServiceProvider',

4. add these to $aliases in config/app.php

			'Form'      => 'Illuminate\Html\FormFacade',
			'HTML'      => 'Illuminate\Html\HtmlFacade',
			'Excel'     => 'Maatwebsite\Excel\Facades\Excel',

5. add this to $middleware in App/Http/Kernel.php

			'LeftRight\Center\Middleware\Permissions',

6. add this to $routeMiddleware in App/Http/Kernel.php

			'user' => 'LeftRight\Center\Middleware\User',

7. add this to $commands in App/Console/Kernel.php

			'LeftRight\Center\Console\Refresh',

8. add this to your composer.json under autoload
			"files": [
				"vendor/left-right/center/src/middleware/Permissions.php",
				"vendor/left-right/center/src/console/Refresh.php"
			],

9. run the following commands
			php artisan vendor:publish
			php artisan center:refresh

10. browse to your project's /center directory to log in

