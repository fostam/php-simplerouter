# fostam/simplerouter

Simple PHP router, mainly for building API backends. 

## Features
- Easy configuration
- Response and error handling
- Parameter input from URL, GET/POST parameters or JSON payload
- CORS support

## Install
The easiest way to install SimpleRouter is by using [composer](https://getcomposer.org/): 

```
$> composer require fostam/simplerouter
```

## Usage

```php
<?php

include "vendor/autoload.php";

$router = new Fostam\SimpleRouter\Router();

$router->createRoute('/user/{id}', Fostam\SimpleRouter\Http::METHOD_GET, '\MyProject\GetUser');
$router->createRoute('/user', Fostam\SimpleRouter\Http::METHOD_POST, '\MyProject\CreateUser');

$router->resolve();
$router->sendResult();