<?php

namespace Fostam\SimpleRouter;

use Fostam\SimpleRouter\Exception\InternalApiException;

/**
 * Class Route
 * @package Fostam\SimpleRouter
 */
class Route {
    const PATH            = 'PATH';
    const METHOD          = 'METHOD';
    const PROCESSOR       = 'PROCESSOR';
    const PARAM_PATTERNS  = 'PARAM_PATTERNS';

    private $validMethods = [
        Http::METHOD_GET    => true,
        Http::METHOD_POST   => true,
        Http::METHOD_DELETE => true,
        Http::METHOD_PUT    => true,
        Http::METHOD_PATCH  => true,
    ];

    private $config = [
        self::PATH           => false,
        self::METHOD         => false,
        self::PROCESSOR      => false,
        self::PARAM_PATTERNS => [],
    ];

    private $pathPattern;
    private $paramOrder = [];
    private $paramValues = false;
    private $processor;
    private $pathMatches;


    /**
     * Route constructor.
     *
     * @param $config
     *
     * @throws InternalApiException
     */
    public function __construct($config) {
        foreach ($config as $key => $value) {
            $this->setConfig($key, $value);
        }

        // build path pattern by replacing param placeholders with param patterns
        $this->pathPattern = $this->buildPathPattern($this->config[self::PATH], $this->paramOrder);
    }

    /**
     * @param           $path
     * @param           $method
     * @param mixed     $processor
     *
     * @return Route
     * @internal param $processorClass
     * @throws InternalApiException
     */
    public static function create($path, $method, $processor) {
        return new Route(
            [
                Route::PATH           => $path,
                Route::METHOD         => $method,
                Route::PROCESSOR      => $processor,
            ]
        );
    }

    /**
     * @param $key
     * @param $value
     *
     * @throws InternalApiException
     */
    public function setConfig($key, $value) {
        if (!isset($this->config[$key])) {
            throw new InternalApiException("illegal config key {$key}");
        }

        switch($key) {
            case self::PATH:
                if (!preg_match('#^/[^ ]*$#', $value)) {
                    throw new InternalApiException("illegal path value: {$value}");
                }
                break;

            case self::METHOD:
                if (!isset($this->validMethods[$value])) {
                    throw new InternalApiException("illegal method {$value}");
                }
        }

        $this->config[$key] = $value;
    }

    /**
     * @param $key
     * @return mixed
     * @throws InternalApiException
     */
    public function getConfig($key) {
        if (!isset($this->config[$key])) {
            throw new InternalApiException("illegal config key {$key}");
        }

        return $this->config[$key];
    }

    /**
     * @param $path
     * @param $method
     * @param $pathMatched
     * @return bool
     */
    public function matches($path, $method, &$pathMatched) {
        if (!preg_match('#^' . $this->pathPattern . '$#', $path, $this->pathMatches)) {
            $pathMatched = false;
            return false;
        }
        $pathMatched = true;

        if ($method === $this->config[self::METHOD]) {
            return true;
        }

        // for HEAD requests to GET routes assume match, too
        if ($method === Http::METHOD_HEAD && $this->config[self::METHOD] === Http::METHOD_GET) {
            return true;
        }

        return false;
    }

    /**
     * @throws InternalApiException
     */
    public function resolve() {
        if (!isset($this->pathMatches)) {
            throw new InternalApiException("resolve() called without match");
        }

        // assign params
        $this->paramValues = [];
        $paramCnt = 0;
        array_shift($this->pathMatches);
        foreach ($this->pathMatches as $value) {
            $this->paramValues[$this->paramOrder[$paramCnt]] = $value;
            $paramCnt++;
        }

        $this->processor = $this->provideProcessor($this->config[self::PROCESSOR]);
    }

    /**
     * @return bool
     * @throws InternalApiException
     */
    public function getParams() {
        if ($this->paramValues === false) {
            throw new InternalApiException("param values cannot be requested from non-matching route");
        }

        return $this->paramValues;
    }

    /**
     * @return Processor
     * @throws InternalApiException
     */
    public function getProcessor() {
        if (!isset($this->processor)) {
            throw new InternalApiException("processor cannot be requested from non-matching route");
        }

        return $this->processor;
    }

    /**
     * @param $processor
     * @return Processor
     * @throws InternalApiException
     */
    private function provideProcessor($processor) {
        if (is_a($processor, 'Fostam\SimpleRouter\Processor')) {
            // processor is an already instantiated Processor object
            return $processor;
        }

        if (is_string($processor)) {
            // processor is a class name with an argument-less constructor
            if (!class_exists($processor)) {
                throw new InternalApiException("processor class {$processor} does not exist");
            }
            return new $processor();
        }

        if (is_array($processor)) {
            // processor is an array consisting of a class name an another array with constructor arguments
            if (!class_exists($processor[0])) {
                throw new InternalApiException("processor class {$processor[0]} does not exist");
            }

            if (!is_array($processor[1])) {
                throw new InternalApiException("processor class arguments must be an array");
            }

            try {
                $ref = new \ReflectionClass($processor[0]);
            }
            catch (\ReflectionException $e) {
                throw new InternalApiException("error getting processor class", 0, $e);
            }

            /** @var Processor $obj */
            $obj = $ref->newInstanceArgs($processor[1]);
            return $obj;
        }

        throw new InternalApiException("no valid processor defined");
    }

    /**
     * @param string $path
     * @param array  $paramOrder
     *
     * @return string
     * @throws InternalApiException
     */
    private function buildPathPattern($path, &$paramOrder) {
        $pattern = '';
        $pos = 0;
        $argStartPos = -1;
        $openCount = 0;
        while(true) {
            $openPos = strpos($path, '{', $pos);
            $closePos = strpos($path, '}', $pos);
            $nextPos = min($openPos, $closePos);

            if ($nextPos && substr($path, $nextPos - 1, 1) == '\\') {
                $pattern .= preg_quote(substr($path, $pos, $nextPos - $pos + 1), '#');
                $pos = $nextPos + 1;
                continue;
            }

            if ($openPos === false && $closePos === false) {
                $pattern .= preg_quote(substr($path, $pos, strlen($path) - $pos + 1), '#');
                break;
            }

            if ($openPos !== false && ($closePos === false || $closePos > $openPos)) {
                if ($openCount === 0) {
                    $pattern .= preg_quote(substr($path, $pos, $openPos - $pos), '#');
                    $argStartPos = $openPos;
                }
                $openCount++;
                $pos = $openPos + 1;
            }
            else {
                $openCount--;

                if ($openCount < 0) {
                    throw new InternalApiException("too many curly brackets closed in '{$path}'");
                }

                if ($openCount === 0) {
                    // end of arg reached
                    $arg = substr($path, $argStartPos + 1, $closePos - $argStartPos - 1);
                    $argData = explode(':', $arg);
                    $name = $argData[0];
                    if (!isset($argData[1])) {
                        $argData[1] = '[^/]*';
                    }
                    $argPat = $argData[1];

                    $paramOrder[] = $name;
                    $pattern .= '(' . $argPat . ')';
                }

                $pos = $closePos + 1;
            }
        }

        if ($openCount) {
            throw new InternalApiException("curly brackets not properly closed in '{$path}'");
        }

        return $pattern;
    }
}