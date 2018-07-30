<?php

namespace Fostam\SimpleRouter;

use Fostam\SimpleRouter\Exception\InternalApiException;

class Response {
    const HEADER_NAME   = 'HEADER_NAME';
    const HEADER_VALUES = 'HEADER_VALUES';

    const TYPE_JSON    = 'application/json';
    const TYPE_JSONAPI = 'application/vnd.api+json';
    const TYPE_PLAIN   = 'text/plain';
    const TYPE_HTML    = 'text/html';

    private $code;
    private $data;
    private $type;
    private $location;
    private $headers = [];
    private $origins = [];

    /**
     * @param int $code
     */
    public function setCode($code) {
        $this->code = $code;
    }

    /**
     * @return int
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param mixed $data
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @param string $path
     */
    public function setLocationPath($path) {
        $this->location = $path;
    }

    /**
     * @return string
     */
    public function getLocationPath() {
        return $this->location;
    }

    /**
     * @param string $header
     * @param string $value
     * @param bool   $append
     */
    public function setHeader($header, $value, $append = false) {
        $headerIdent = self::getHeaderIdent($header);
        if (!$append || !isset($this->headers[$headerIdent])) {
            $this->headers[$headerIdent] = [
                self::HEADER_NAME => $header,
                self::HEADER_VALUES => [],
            ];
        }
        $this->headers[$headerIdent][self::HEADER_VALUES][] = $value;
    }

    /**
     * @param string $header
     *
     * @return bool
     */
    public function isHeaderSet($header) {
        return isset($this->headers[self::getHeaderIdent($header)]);
    }

    /**
     * @param string $header
     *
     * @return string
     */
    private static function getHeaderIdent($header) {
        return trim(strtolower($header));
    }

    /**
     * @param $header
     */
    public function clearHeader($header) {
        unset($this->headers[self::getHeaderIdent($header)]);
    }

    /**
     *
     */
    public function clearHeaders() {
        $this->headers = [];
    }

    /**
     * @param $header
     *
     * @return array|null
     */
    public function getHeader($header) {
        $headerIdent = self::getHeaderIdent($header);
        if (!isset($this->headers[$headerIdent])) {
            return null;
        }
        return $this->headers[$headerIdent];
    }

    /**
     * @return array
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @param $key
     * @param $value
     * @param string $separator
     * @throws InternalApiException
     */
    public function addResponseData($key, $value, $separator = '.') {
        if (!isset($this->data)) {
            $this->data = [];
        }

        if (!is_array($this->data)) {
            throw new InternalApiException("addResponseData() used on non-array response data");
        }

        self::createArrayFromPath($this->data, $key, $value, $separator);
    }

    /**
     * @param string $origin
     */
    public function corsSetOrigin($origin) {
        $this->origins = [strtolower($origin)];
    }

    /**
     * @param string $origin
     */
    public function corsAddOrigin($origin) {
        $this->origins[] = strtolower($origin);
    }

    /**
     * @return array
     */
    public function corsGetOrigins() {
        return $this->origins;
    }

    /**
     * @param bool $allowed
     */
    public function corsAllowCredentials($allowed) {
        $this->setHeader('Access-Control-Allow-Credentials', $allowed ? 'true' : 'false');
    }

    /**
     * @param string $header
     */
    public function corsAddAllowedHeader($header) {
        $prevValue = '';
        if ($this->isHeaderSet('Access-Control-Allow-Headers')) {
            $prevValue = join(', ', $this->getHeader('Access-Control-Allow-Headers')[Response::HEADER_VALUES]) . ', ';
        }
        $this->setHeader('Access-Control-Allow-Headers', $prevValue . $header);
    }

    /**
     * @param array $headers
     */
    public function corsAddAllowedHeaders($headers) {
        foreach($headers as $header) {
            $this->corsAddAllowedHeader($header);
        }
    }

    /**
     * @param int $maxAgeSeconds
     */
    public function corsSetMaxAge($maxAgeSeconds) {
        $this->setHeader('Access-Control-Max-Age', intval($maxAgeSeconds));
    }

    /**
     * @param        $arr
     * @param        $path
     * @param        $value
     * @param string $separator
     */
    private static function createArrayFromPath(&$arr, $path, $value, $separator = '.') {
        $keys = explode($separator, $path);

        foreach ($keys as $key) {
            $arr = &$arr[$key];
        }

        $arr = $value;
    }
}