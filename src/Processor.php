<?php

namespace Fostam\SimpleRouter;

use Fostam\SimpleRouter\Exception\UserApiException;

/**
 * Class Processor
 * @package Fostam\SimpleRouter
 */
abstract class Processor {
    private $path;
    private $method;
    private $params;
    /** @var Response **/
    private $responseObj;
    private $jsonPostData;

    /**
     * Processor constructor.
     *
     * @param string $path
     * @param string $method
     * @param string[] $params
     * @param Response $responseObj
     */
    public function init($path, $method, $params, Response $responseObj) {
        $this->path = $path;
        $this->method = $method;
        $this->params = $params;
        $this->responseObj = $responseObj;
    }

    /**
     */
    abstract public function execute();

    /**
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @return Response
     */
    public function getResponseObject() {
        return $this->responseObj;
    }

    /**
     * @param string $type
     */
    public function setResponseType($type) {
        $this->responseObj->setType($type);
    }

    /**
     * @param int $code
     */
    protected function setResponseCode($code) {
        $this->responseObj->setCode($code);
    }

    /**
     * @param mixed $data
     */
    protected function setResponseData($data) {
        $this->responseObj->setData($data);
    }

    /**
     * @return string[]
     */
    protected function getPathParams() {
        return $this->params;
    }

    /**
     * @param $param
     *
     * @return string|false
     */
    protected function getPathParam($param) {
        if (!isset($this->params[$param])) {
            return false;
        }

        return $this->params[$param];
    }

    /**
     * @return string[]
     */
    protected function getQueryParams() {
        return $_GET;
    }

    /**
     * @param $param
     *
     * @return string|false
     */
    protected function getQueryParam($param) {
        if (!isset($_GET[$param])) {
            return false;
        }

        return $_GET[$param];
    }

    /**
     * @return string[]
     */
    protected function getPostParams() {
        return $_POST;
    }

    /**
     * @param string $param
     *
     * @return string|false
     */
    protected function getPostParam($param) {
        if (!isset($_POST[$param])) {
            return false;
        }

        return $_POST[$param];
    }

    /**
     * @return mixed
     * @throws UserApiException
     */
    protected function getJSONPostData() {
        if (isset($this->jsonPostData)) {
            return $this->jsonPostData;
        }

        $response = '';
        $fh = fopen('php://input', 'r');
        while($line = fgets($fh)) {
            $response .= $line;
        }
        $this->jsonPostData = json_decode($response, true);
        if ($this->jsonPostData === null) {
            throw new UserApiException("invalid json", Http::CODE_BAD_REQUEST);
        }
        return $this->jsonPostData;
    }
}