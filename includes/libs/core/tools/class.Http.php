<?php
namespace lib\core\tools {
    class Http {
        const V_1_0 = "1.0";
        const V_1_1 = "1.1";
        const CODE_100 = "100 Continue";
        const CODE_101 = "101 Switching Protocols";
        const CODE_102 = "102 Processing";
        const CODE_200 = "200 OK";
        const CODE_201 = "201 Created";
        const CODE_202 = "202 Accepted";
        const CODE_203 = "203 Non-Authoritative Information";
        const CODE_204 = "204 No Content";
        const CODE_205 = "205 Reset Content";
        const CODE_206 = "206 Partial Content";
        const CODE_207 = "207 Multi-Status";
        const CODE_210 = "210 Content Different";
        const CODE_300 = "300 Multiple Choices";
        const CODE_301 = "301 Moved Permanently";
        const CODE_302 = "302 Moved Temporarily";
        const CODE_303 = "303 See Other";
        const CODE_304 = "304 Not Modified";
        const CODE_305 = "305 Use Proxy";
        const CODE_307 = "307 Temporary Redirect";
        const CODE_308 = "308 Permanent Redirect";
        const CODE_310 = "310 Too many Redirects";
        const CODE_400 = "400 Bad Request";
        const CODE_401 = "401 Unauthorized";
        const CODE_402 = "402 Payment Required";
        const CODE_403 = "403 Forbidden";
        const CODE_404 = "404 Not Found";
        const CODE_405 = "405 Method Not Allowed";
        const CODE_406 = "406 Not Acceptable";
        const CODE_407 = "407 Proxy Authentication Required";
        const CODE_408 = "408 Request Timeout";
        const CODE_409 = "409 Conflict";
        const CODE_410 = "410 Gone";
        const CODE_411 = "411 Length Required";
        const CODE_412 = "412 Precondition Failed";
        const CODE_413 = "413 Request Entity Too Large";
        const CODE_414 = "414 Request-URI Too Long";
        const CODE_415 = "415 Unsupported Media Type";
        const CODE_416 = "416 Requested range unsatisfiable";
        const CODE_417 = "417 Expectation Failed";
        const CODE_422 = "422 Unprocessable Entity";
        const CODE_500 = "500 Internal Server Error";
        const CODE_501 = "501 Not Implemented";
        const CODE_502 = "502 Bad Gateway";
        const CODE_503 = "503 Service Unavailable";
        const CODE_504 = "504 Gateway Time-out";
        const GET = 'GET';
        const POST = 'POST';
        const PUT = 'PUT';
        const PATCH = 'PATCH';
        const DELETE = 'DELETE';
    }
}