<?php

namespace Fostam\SimpleRouter\Exception;

abstract class ApiException extends \Exception {
    public function __construct($message = null, $code = 0, \Exception $previous = null) {
        if ($code === 0) {
            $code = $this->getDefaultCode();
        }

        parent::__construct($message, $code, $previous);
    }

    abstract protected function getDefaultCode();
}