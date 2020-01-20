# Szamlazz package development and install
[Szamlázz.hu API documentaion](https://docs.szamlazz.hu/#introduction)

[Laravel package development](https://laravel.com/docs/6.x/packages)
## Install
* GBL package install
```php
    composer require gbl/szamlazz
```
* Guzzle HTTP client install
```php
    composer require guzzlehttp/guzzle
```
## Settings
* Add  this variable to .env file
```php
    SZAMLAZZ_AGENT_KEY=
    SZAMLAZZ_USER=
    SZAMLAZZ_PASSWORD=
```
* Last steps publishing vendor
```php
    php artisan vendor:publish --provider="Gbl\Szamlazz\SzamlazzServiceProvider"
```
## Licence
[MIT](https://choosealicense.com/licenses/mit/)