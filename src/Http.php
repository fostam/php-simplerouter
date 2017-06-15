<?php

namespace Fostam\SimpleRouter;

abstract class Http {
    // methods
    const METHOD_GET     = 'GET';
    const METHOD_POST    = 'POST';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_PUT     = 'PUT';
    const METHOD_PATCH   = 'PATCH';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_HEAD    = 'HEAD';
    const METHOD_TRACE   = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';

    // codes

    // 1xx Informational
    const CODE_CONTINUE            = 100;
    const CODE_SWITCHING_PROTOCOLS = 101;
    const CODE_PROCESSING          = 102;

    // 2xx Successful
    const CODE_OK                            = 200;
    const CODE_CREATED                       = 201;
    const CODE_ACCEPTED                      = 202;
    const CODE_NON_AUTHORITATIVE_INFORMATION = 203;
    const CODE_NO_CONTENT                    = 204;
    const CODE_RESET_CONTENT                 = 205;
    const CODE_PARTIAL_CONTENT               = 206;
    const CODE_MULTI_STATUS                  = 207;
    const CODE_ALREADY_REPORTED              = 208;
    const CODE_IM_USED                       = 226;

    // 3xx Redirection
    const CODE_MULTIPLE_CHOICES     = 300;
    const CODE_MOVED_PERMANENTLY    = 301;
    const CODE_FOUND                = 302;
    const CODE_SEE_OTHER            = 303;
    const CODE_NOT_MODIFIED         = 304;
    const CODE_USE_PROXY            = 305;
    const CODE_RESERVED             = 306;
    const CODE_TEMPORARY_REDIRECT   = 307;
    const CODE_PERMANENTLY_REDIRECT = 308;

    // 4xx Client Error
    const CODE_BAD_REQUEST                     = 400;
    const CODE_UNAUTHORIZED                    = 401;
    const CODE_PAYMENT_REQUIRED                = 402;
    const CODE_FORBIDDEN                       = 403;
    const CODE_NOT_FOUND                       = 404;
    const CODE_METHOD_NOT_ALLOWED              = 405;
    const CODE_NOT_ACCEPTABLE                  = 406;
    const CODE_PROXY_AUTHENTICATION_REQUIRED   = 407;
    const CODE_REQUEST_TIMEOUT                 = 408;
    const CODE_CONFLICT                        = 409;
    const CODE_GONE                            = 410;
    const CODE_LENGTH_REQUIRED                 = 411;
    const CODE_PRECONDITION_FAILED             = 412;
    const CODE_REQUEST_ENTITY_TOO_LARGE        = 413;
    const CODE_REQUEST_URI_TOO_LONG            = 414;
    const CODE_UNSUPPORTED_MEDIA_TYPE          = 415;
    const CODE_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const CODE_EXPECTATION_FAILED              = 417;
    const CODE_I_AM_A_TEAPOT                   = 418;
    const CODE_UNPROCESSABLE_ENTITY            = 422;
    const CODE_LOCKED                          = 423;
    const CODE_FAILED_DEPENDENCY               = 424;
    const CODE_UPGRADE_REQUIRED                = 426;
    const CODE_PRECONDITION_REQUIRED           = 428;
    const CODE_TOO_MANY_REQUESTS               = 429;
    const CODE_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;

    // 5xx Server Error
    const CODE_INTERNAL_SERVER_ERROR                = 500;
    const CODE_NOT_IMPLEMENTED                      = 501;
    const CODE_BAD_GATEWAY                          = 502;
    const CODE_SERVICE_UNAVAILABLE                  = 503;
    const CODE_GATEWAY_TIMEOUT                      = 504;
    const CODE_VERSION_NOT_SUPPORTED                = 505;
    const CODE_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;
    const CODE_INSUFFICIENT_STORAGE                 = 507;
    const CODE_LOOP_DETECTED                        = 508;
    const CODE_NOT_EXTENDED                         = 510;
    const CODE_NETWORK_AUTHENTICATION_REQUIRED      = 511;
}