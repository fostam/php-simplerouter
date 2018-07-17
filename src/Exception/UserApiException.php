<?php

namespace Fostam\SimpleRouter\Exception;

class UserApiException extends ApiException {
    protected function getDefaultCode() {
        return 400;
    }
}