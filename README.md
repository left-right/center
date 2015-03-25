# Center
An innovative CMS for Laravel 5

(video to come)

### Installation
composer require left-right/center:dev-master
add the following to providers in config/app.php
```
'LeftRight\Center\CenterServiceProvider',
'Illuminate\Html\HtmlServiceProvider',
```
add the following to $routeMiddleware in App/Http/Kernel.php
```
'user' => 'LeftRight\Center\Middleware\User',
'admin' => 'LeftRight\Center\Middleware\Admin',
'programmer' => 'LeftRight\Center\Middleware\Programmer',
```

### Package development workflow in Laravel 5
With the removal of the Workbench, I've tried using illuminate/html with limited success. The easiest
thing for me is after running composer update from the project root, simply running
```
rm -rf vendor/left-right/center/
sudo ln -s ~/Sites/center vendor/left-right/center
```
