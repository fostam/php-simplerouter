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
            throw new InternalApiException("error getting data for user {$userID}", Http::CODE_INTERNAL_SERVER_ERROR, $e);
        }

        $this->setResponseData(
            [
                'data' => $data
            ]
        );
    }
}
```

## Reference
### Router
#### setOption()
`Router setOption($option, $value)`
Set a router option. The router object is returned to allow chaining.
Following options are available:

| Option                            | Value                                  | Default Value           |
|-----------------------------------|----------------------------------------|-------------------------|
| Router::OPT_REQUEST_PATH_PREFIX   | prefix that is truncated from the path | false                   |
| Router::OPT_RESPONSE_TYPE_DEFAULT | default response type                  | Response::TYPE_HTML     |
| Router::OPT_KEY_ERROR_MESSAGE     | key in result JSON for error message   | "error.message"         |
| Router::OPT_KEY_ERROR_CODE        | key in result JSON for error code      | "error.code"            |
| Router::OPT_INTERNAL_ERROR_MSG    | error message text for internal errors | "internal server error" |
| Router::OPT_CORS_PERMISSIVE       | permissive CORS mode                   | false                   |

#### addRoute()
`Route addRoute(Route $route)`

Add a `Route` object to the list of routes. For convenience, the route object is returned to allow direct calling of `Route` methods.

#### createRoute()
`Route createRoute(string $path, string $method, mixed $processor)`

Creates a `Route` object from the given parameters, adds it to the list of routes and returns it. See the
`Route` documentation for a description of the parameters.

#### resolve()
`void resolve(string $path = '', string $method = '')`

Resolve the given path/method and execute the matching processor.
If `$path` is empty, `$_SERVER['SCRIPT_NAME']` is used as path.
If `$method` is empty, `$_SERVER['REQUEST_METHOD']` is used as method.

#### sendResult()
`void sendResult()`

Sends the result. This include the HTTP status code, HTTP headers (including content type) and the body.
Content type header and body format depend on the configured response type.

#### getResponseObject()
`Response getResponseObject()`

Get the `Response` object. The response object represents what is sent when calling `sendResult()`.

### Route
#### Constructor
`Route __construct(array $config)`

The constructor is the preferred way of creating a route when the route configuration comes from
a dynamic source, e.g. from a database.

It builds a route object with the given associative array `$config`. The following
configuration keys are available:

| Key              | Value                                 | Mandatory |
|------------------|---------------------------------------|-----------|
| Route::PATH      | path used for matching                | yes       |
| Route::METHOD    | method used for matching              | yes       |
| Route::PROCESSOR | processor that is executed on a match | yes       |

The *path* is the endpoint that defines when this route matches. When comparing to the actual path,
the `Router::OPT_REQUEST_PATH_PREFIX` is prefixed, if configured.

The *method* is the HTTP method that needs to match along with the path, e.g. `Http::METHOD_GET` or
`Http::METHOD_POST`.

If both path and method match, the *processor* is called. There are three different possibilities to
define the processor:
- A class name - this is suitable if the class has a constructor without arguments, because the instantiation
is done when the class is used
- An array consisting of a class name and another array with the constructor parameters - this method can be
used when the constructors needs arguments
- An object - this is suitable when the processor object is created in advance

In either case, the class/object passed is required to extend the abstract `Processor` class.

#### create()
`Route ::create(string $path, string $method, mixed $processor)`

Static method to create a `Route` object from parameters. The `create()` method is the preferred way of creating
a route when the route definitions are hardwired in PHP. Refer to the constructor for a description of the
parameters.

### Processor
#### getPath()
`string getPath()`

Get the path this processor is configured for.

#### getMethod()
`string getMethod()`

Get the HTTP method this processor is configured for.

#### getResponseObject()
`Response getResponseObject()`

Get the response object that will be used to create the response after the processor has finished.

#### setResponseCode()
`void setResponseCode(int $code)`

Set the HTTP response code, e.g. `Http::CODE_OK` (_200_).

Shorthand for `getResponseObject()->setCode($code)`.

#### setResponseType()
`void setResponseType(string $type)`

Set the HTTP response type, e.g. `Response::TYPE_JSON`. This can be either one of the `TYPE_` constants from the
`Response` class, or any valid MIME type string, e.g. _application/xml_.
If no response type is set, the router will send the default response type (`Router::OPT_RESPONSE_TYPE_DEFAULT`), 
if set. If no default is set, `text/html` is sent.

Shorthand for `getResponseObject()->setType($type)`.

#### setResponseData()
`void setResponseData(mixed $data)`

Set the body data. This can be either an array for the `Response::TYPE_JSON` response type, or a string
for `Response::TYPE_HTML` or any other response type.

Shorthand for `getResponseObject()->setData($data)`.

#### getPathParams()
`string[] getPathParams()`

Get all path parameter values as key/values pairs.

Example:
```php
$router->createRoute('/test/{id}/user/{name}', Http::METHOD_GET, Test::class);
$router->resolve('/test/123/user/john', Http::METHOD_GET);
```

A call to `getPathParams()` in the `Test` processor call will return:
```php
array(2) {
  'id' =>
  string(3) "123"
  'name' =>
  string(4) "john"
}
```

#### getPathParam()
`string getPathParam(string $param)`

Get the path parameter value for a single parameter. For the example above, `getPathParam('name')` would return `john`.

#### getQueryParams()
`string[] getQueryParams()`

Return the query parameters as key/value pairs. This is equivalent to PHP's `$_GET` superglobal.

#### getQueryParam()
`string getQueryParam(string $param)`

Get the query parameter value for a single parameter.

#### getPostParams()
`string[] getPostParams()`

Return the POST parameters as key/value pairs. This is equivalent to PHP's `$_POST` superglobal.

#### getPostParam()
`string getPostParam(string $param)`

Get the POST parameter value for a single parameter.

#### getJSONPostData()
`mixed getJSONPostData()`

When the body of a POST, PUT or PATCH call contains a JSON string, it's structure can be retrieved
as a PHP array.

Example:
```bash
curl 'https://example.com/test' -X POST -d '{"id": 123, "name": "john"}'
```

A call to `getJSONPostData()` will return this array:
```php
array(2) {
  'id' =>
  int(123)
  'name' =>
  string(4) "john"
}
```

### Response
#### setCode()
`void setCode(int $code)`

Set the HTTP response code, e.g. `Http::CODE_OK` (_200_).

#### getCode()
`int getCode()`

Get the current response code.

#### setType()
`void setType(string $type)`

Set the HTTP response type, e.g. `Response::TYPE_JSON`. This can be either one of the `TYPE_` constants from the
`Response` class, or any valid MIME type string, e.g. _application/xml_.
If no response type is set, the router will send the default response type (`Router::OPT_RESPONSE_TYPE_DEFAULT`), 
if set. If no default is set, `text/html` is sent.

#### getType()
`int getType()`

Get the current response code.

#### setData()
`void setData(mixed $data)`

Set the body data. This can be either an array for the `Response::TYPE_JSON` response type, or a string
for `Response::TYPE_HTML` or any other response type.

#### getData()
`mixed getData()`

Get the current data.

#### setHeader()
`void setHeader(string $header, string $value, bool $append = false)`

Set the HTTP header `$header` to the value `$value`. If `$append` is `true` and the header
had been set before, an additional header line for the same header is added.

#### isHeaderSet()
`bool isHeaderSet(string $header)`

Returns whether the header `$header` has been set or not.

#### getHeader()
`array getHeader(string $header)`

Get the header `$header`. An array with the following elements is returned:
- `Response::HEADER_NAME`: the name of the header, e.h. _Content-Type_
- `Response::HEADER_VALUES`: an array of strings, representing the values

#### getHeaders()
`array getHeaders()`

Get all headers. It returns an array of entries as returned by `getHeader()`.

#### clearHeader()
`void clearHeader(string $header)`

Clear the header `$header`.

#### clearHeaders()
`void clearHeaders()`

Clear all headers.

#### addResponseData()
`void addResponseData(string $key, string $value, string $separator = '.')`

Adds data to the response array (for the JSON response type). Sub-array keys can be separated by the `$separator`
string.
 
Example:
```php
addResponseData('data.user.name', 'John')
```
would set this value:
```json
{
  "data": {
    "user": {
      "name": "John"
    }
  }
}
```

#### corsSetOrigin()
`void corsSetOrigin(string $origin)`

Set the _Access-Control-Allow-Origin_ to `$origin`, e.g. `corsSetOrigin('*')` to allow all origins.

#### corsAddOrigin()
`void corsAddOrigin(string $origin)`

If multiple origins are required, an origin can be added, e.g. `corsAddOrigin('example.org')`. The list of origins
is compared to the _Origin_ request header. On a match, the _Access-Control-Allow-Origin_ is set to the matching origin.

#### corsGetOrigins()
`array corsGetOrigins()`

Get the list of allowed origins.

#### corsAllowCredentials()
`void corsAllowCredentials(bool $allowed)`

Used to set the _Access-Control-Allow-Credentials_ header.

#### corsAddAllowedHeader()
`void corsAddAllowedHeader(string $header)`

Add a header to the list of allowed headers used for _Access-Control-Allow-Headers_.

#### corsAddAllowedHeaders()
`void corsAddAllowedHeaders(string[] $headers)`

Set an array of allowed headers.

#### corsSetMaxAge()
`void corsSetMaxAge(int $maxAgeSeconds)`

Set the maximum age in seconds used for the _Access-Control-Max-Age_ header.
