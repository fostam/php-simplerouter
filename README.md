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

This is how a typical "handler" would look like:

```php
<?php

include "vendor/autoload.php";

use Fostam\SimpleRouter\Response;
use Fostam\SimpleRouter\Router;
use Fostam\SimpleRouter\Http;
use Fostam\SimpleRouter\Exception\InternalApiException;
use Fostam\SimpleRouter\Exception\UserApiException;

$router = new Router();
try {
    // all URLs must be prefixed with this string
    $router->setOption(Router::OPT_REQUEST_PATH_PREFIX, '/myproject/api');

    // send permissive cross origin resource sharing headers
    $router->setOption(Router::OPT_CORS_PERMISSIVE, true);
    
    // by default, send the response with application/json content type
    $router->setOption(Router::OPT_RESPONSE_TYPE_DEFAULT, Response::TYPE_JSON);

    // routes
    $router->createRoute('/users', Http::METHOD_GET, \MyProject\GetUsers::class);
    $router->createRoute('/users/{id}', Http::METHOD_GET, \MyProject\GetUsers::class);
    $router->createRoute('/users', Http::METHOD_POST, \MyProject\CreateUser::class);

    // resolve
    $router->resolve();
}
catch (UserApiException $e) {
    // error messages are automatically passed back as JSON
}
catch (InternalApiException $e) {
    // a generic error message is passed back as JSON; trigger the actual errors
    // (e.g. a failed database query) that should not be publicly visible, but
    // logged internally
    error_log($e);
}

$router->sendResult();
```

This could be a (very simplified) processor class for getting users:

```php
class GetUsers extends Processor {
    public function execute() {
        $userID = $this->getPathParam('id');
            
        if (!is_numeric($userID)) {
            throw new UserApiException('userID must be a number', Http::CODE_BAD_REQUEST, $e);
        }

        try {
            $data = $this->myUserModel->getUser(intval($userID));
        } catch (\Exception $e) {
            throw new InternalApiException("error getting data for user {$userID}, Http::CODE_INTERNAL_SERVER_ERROR, $e);
        }

        $this->setResponseData(
            [
                'data' => $data
            ]
        );
    }
}
```

