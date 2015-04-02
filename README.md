# Center
An innovative CMS for Laravel 5. 

(walkthrough video to come)

### Installation
In Terminal:
```
composer require left-right/center:dev-master
```
add these to $providers in config/app.php
```
'LeftRight\Center\CenterServiceProvider',
'Illuminate\Html\HtmlServiceProvider',
'Maatwebsite\Excel\ExcelServiceProvider',
```
add these to $aliases in config/app.php
```
'Form'      => 'Illuminate\Html\FormFacade',
'HTML'      => 'Illuminate\Html\HtmlFacade',
'Excel'     => 'Maatwebsite\Excel\Facades\Excel',
```
add these to $middleware in App/Http/Kernel.php
```
'LeftRight\Center\Middleware\Permissions',
```
add these to $middleware in App/Http/Kernel.php
```
'user' => 'LeftRight\Center\Middleware\User',
```
In Terminal, again:
```
php artisan vendor:publish
```
