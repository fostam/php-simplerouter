<?php

namespace Fostam\SimpleRouter\Exception;

class InternalApiException extends ApiException {
    protected function getDefaultCode() {
        return 500;
    }
}