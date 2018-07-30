<?php

namespace Fostam\SimpleRouter;

use Fostam\SimpleRouter\Exception\InternalApiException;
use Fostam\SimpleRouter\Exception\UserApiException;

/**
 * Class Router
 * @package Fostam\SimpleRouter
 */
class Router {
    const OPT_REQUEST_PATH_PREFIX   = 'OPT_REQUEST_PATH_PREFIX';
    const OPT_RESPONSE_TYPE_DEFAULT = 'OPT_RESPONSE_TYPE';
    const OPT_KEY_ERROR_MESSAGE     = 'OPT_KEY_ERROR_MESSAGE';
    const OPT_KEY_ERROR_CODE        = 'OPT_KEY_ERROR_CODE';
    const OPT_INTERNAL_ERROR_MSG    = 'OPT_INTERNAL_ERROR_MSG';
    const OPT_CORS_PERMISSIVE       = 'OPT_CORS_PERMISSIVE';

    private $opts = [
        self::OPT_REQUEST_PATH_PREFIX   => '',
        self::OPT_RESPONSE_TYPE_DEFAULT => Response::TYPE_HTML,
        self::OPT_KEY_ERROR_MESSAGE     => 'error.message',
        self::OPT_KEY_ERROR_CODE        => 'error.code',
        self::OPT_INTERNAL_ERROR_MSG    => 'internal server error',
        self::OPT_CORS_PERMISSIVE       => false,
    ];

    private $path;
    private $method;
    private $processor;
    private $params;
    /** @var Route[] */
    private $routes = [];
    private $responseObj;


    /**
     * Handler constructor.
     */
    public function __construct() {
        $this->responseObj = new Response();
    }

    /**
     * @param string $path
     * @param string $method
     *
     * @throws InternalApiException
     * @throws UserApiException
     */
    public function resolve($path = '', $method = '') {
        try {
            $this->initEnv($path, $method);

            if ($this->method === Http::METHOD_OPTIONS) {
                $this->collectOptions();
                return;
            }

            $this->resolvePath();

            /* @var Processor $processor */
            $processor = $this->processor;
            $processor->init($this->path, $this->method, $this->params, $this->responseObj);
            $processor->execute();

            // set default response type
            if (!$this->responseObj->getType()) {
                $this->responseObj->setType($this->opts[self::OPT_RESPONSE_TYPE_DEFAULT]);
            }

            // set content type header based on response type, unless the header had been set already
            if (!$this->responseObj->isHeaderSet('Content-Type') && !is_null($this->responseObj->getData())) {
                $this->responseObj->setHeader('Content-Type', $this->responseObj->getType());
            }

            // set location
            if ($location = $this->responseObj->getLocationPath()) {
                $this->responseObj->setHeader('Location', $this->opts[self::OPT_REQUEST_PATH_PREFIX] . $location);
            }

            // set default return code
            if (is_null($this->responseObj->getCode())) {
                if (is_null($this->responseObj->getData())) {
                    $this->responseObj->setCode(Http::CODE_NO_CONTENT);
                }
                else {
                    $this->responseObj->setCode(Http::CODE_OK);
                }
            }
        }
        catch (InternalApiException $e) {
            $this->responseObj->setCode($e->getCode());
            if ($this->getReponseType() === Response::TYPE_JSON) {
                $this->responseObj->addResponseData($this->opts[self::OPT_KEY_ERROR_CODE], $this->responseObj->getCode());
                $this->responseObj->addResponseData($this->opts[self::OPT_KEY_ERROR_MESSAGE], $this->opts[self::OPT_INTERNAL_ERROR_MSG]);
            }
            throw $e;
        }
        catch (UserApiException $e) {
            $this->responseObj->setCode($e->getCode());
            if ($this->getReponseType() === Response::TYPE_JSON) {
                $this->responseObj->addResponseData($this->opts[self::OPT_KEY_ERROR_CODE], $this->responseObj->getCode());
                $this->responseObj->addResponseData($this->opts[self::OPT_KEY_ERROR_MESSAGE], $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * @return Response
     */
    public function getResponseObject() {
        return $this->responseObj;
    }

    /**
     *
     */
    public function sendResult() {
        // set headers from options
        if ($this->opts[self::OPT_CORS_PERMISSIVE]) {
            // origin
            if (isset($_SERVER['HTTP_ORIGIN'])) {
                $this->getResponseObject()->corsAddOrigin($_SERVER['HTTP_ORIGIN']);
            }
            else {
                $this->getResponseObject()->corsSetOrigin('*');
            }

            // headers
            $headers = ['content-type', 'authorization', 'accept', 'origin', 'x-requested-with'];
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                foreach(explode(',', $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']) as $header) {
                    $header = strtolower(trim($header));
                    if (!in_array($header, $headers)) {
                        $headers[] = $header;
                    }
                }
            }
            $this->getResponseObject()->corsAddAllowedHeaders($headers);

            // credentials
            $this->getResponseObject()->corsAllowCredentials(true);
        }

        // set origin
        if (!$this->responseObj->isHeaderSet('Access-Control-Allow-Origin')) {
            if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $this->responseObj->corsGetOrigins())) {
                $this->responseObj->setHeader('Access-Control-Allow-Origin', $_SERVER['HTTP_ORIGIN']);
            }
            else if (in_array('*', $this->responseObj->corsGetOrigins())) {
                $this->responseObj->setHeader('Access-Control-Allow-Origin', '*');
            }
        }

        // clear PHP's default Content-Type header, if not set explicitly
        if (!$this->responseObj->isHeaderSet('Content-Type')) {
            header('Content-Type:');
        }

        // send response code
        http_response_code($this->responseObj->getCode());

        // send headers
        foreach ($this->responseObj->getHeaders() as $headerData) {
            foreach ($headerData[Response::HEADER_VALUES] as $value) {
                header($headerData[Response::HEADER_NAME] . ': ' . $value);
            }
        }

        // send data, unless it's a HEAD request
        if ($this->method !== Http::METHOD_HEAD) {
            $this->sendData();
        }
    }

    /**
     * @param string $option
     * @param string $value
     *
     * @return $this
     * @throws InternalApiException
     */
    public function setOption($option, $value) {
        if (!isset($this->opts[$option])) {
            throw new InternalApiException("illegal option {$option}");
        }

        if ($option === self::OPT_RESPONSE_TYPE_DEFAULT && !is_string($value)) {
            throw new InternalApiException("illegal response type {$value}");
        }

        $this->opts[$option] = $value;
        return $this;
    }

    /**
     * @param Route $route
     *
     * @return Route
     */
    public function addRoute(Route $route) {
        $this->routes[] = $route;
        return $route;
    }

    /**
     * @param array $routeConfig
     *
     * @return Route
     * @throws InternalApiException
     */
    public function importRoute($routeConfig) {
        $route = new Route($routeConfig);
        $this->routes[] = $route;
        return $route;
    }

    /**
     * @param string    $path
     * @param string    $method
     * @param mixed     $processor
     *
     * @return Route
     * @throws InternalApiException
     */
    public function createRoute($path, $method, $processor) {
        $route = Route::create($path, $method, $processor);
        $this->routes[] = $route;
        return $route;
    }

    /**
     * @param string $requestPath
     * @param string $requestMethod
     *
     * @throws InternalApiException
     */
    private function initEnv($requestPath, $requestMethod) {
        // path
        if ($requestPath) {
            $path = $requestPath;
        }
        else if (isset($_SERVER['SCRIPT_NAME'])) {
            $path = $_SERVER['SCRIPT_NAME'];
        }
        else {
            throw new InternalApiException("request path not set");
        }

        if ($this->opts[self::OPT_REQUEST_PATH_PREFIX]) {
            if (!preg_match("#^" . preg_quote($this->opts[self::OPT_REQUEST_PATH_PREFIX], '#') . '(/.+)$#', $path, $matches)) {
                throw new InternalApiException("path {$path} not prefixed by {$this->opts[self::OPT_REQUEST_PATH_PREFIX]}/");
            }
            $this->path = $matches[1];
        }
        else {
            $this->path = $path;
        }

        // method
        if ($requestMethod) {
            $this->method = $requestMethod;
        }
        else if (isset($_SERVER['REQUEST_METHOD'])) {
            $this->method = $_SERVER['REQUEST_METHOD'];
        }
        else {
            throw new InternalApiException("request method not set");
        }
    }

    /**
     * @throws UserApiException
     * @throws InternalApiException
     */
    private function resolvePath() {
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if ($route->matches($this->path, $this->method, $pathMatched)) {
                // route is matching
                $route->resolve();
                $this->params = $route->getParams();
                $this->processor = $route->getProcessor();
                return;
            }

            if ($pathMatched) {
                // path has matched, so we collect this route's method
                $allowedMethods[] = $route->getConfig(Route::METHOD);
            }
        }

        // no match found
        if ($allowedMethods) {
            $this->responseObj->setHeader('Allow', join(', ', $allowedMethods));
            throw new UserApiException("method not allowed", Http::CODE_METHOD_NOT_ALLOWED);
        }
        else {
            throw new UserApiException("not found", Http::CODE_NOT_FOUND);
        }
    }

    /**
     * @throws InternalApiException
     */
    private function collectOptions() {
        $this->responseObj->setCode(Http::CODE_OK);

        // collect methods
        $allowedMethods = [];
        foreach ($this->routes as $route) {
            $route->matches($this->path, $this->method, $pathMatched);
            if ($pathMatched) {
                $allowedMethods[] = $route->getConfig(Route::METHOD);
            }
        }
        $this->responseObj->setHeader('Access-Control-Allow-Methods', join(', ', $allowedMethods));

    }

    /**
     *
     */
    private function sendData() {
        if (is_null($this->responseObj->getData())) {
            return;
        }

        if ($this->getReponseType() === Response::TYPE_JSON || $this->getReponseType() === Response::TYPE_JSONAPI) {
            print json_encode($this->responseObj->getData());
        }
        else {
            print $this->responseObj->getData();
        }
    }

    /**
     * @return string
     */
    private function getReponseType() {
        if (!is_null($this->responseObj->getType())) {
            return $this->responseObj->getType();
        }

        return $this->opts[self::OPT_RESPONSE_TYPE_DEFAULT];
    }
}