<?php

namespace Xel\Async\Http\Utility;
use Psr\Http\Message\ServerRequestInterface;
use Xel\Async\Http\Request;


function requestMethodChecker(string $method): bool
{
    return match (true) {
        $method == "POST" => true,
        $method == "PUT" => true,
        $method == "PATCH" => true,
        default => false,
    };
}

function CheckContentType(ServerRequestInterface $request): false|Request
{
    $contentType = $request->getHeaderLine('Content-Type');
    return match (true) {
        str_contains($contentType , 'multipart/form-data') => checkMultipartContentType($request) ,
        str_contains($contentType , "application/x-www-form-urlencoded") => checkUrlEncodeContentType($request),
        str_contains($contentType , "application/json") => checkJsonContentType($request),
        str_contains($contentType , "text/plain") => checkPlainContentType($request),
        default => false,
    };
}

function checkMultipartContentType(ServerRequestInterface $request): Request
{
    return new Request($request->getParsedBody(), $request->getUploadedFiles());
}

function checkUrlEncodeContentType(ServerRequestInterface $request): Request
{
    return new Request($request->getParsedBody());
}


function checkJsonContentType(ServerRequestInterface $request): Request
{
    $data = json_decode($request->getBody()->getContents());
    return new Request((array)$data);

}

function checkPlainContentType(ServerRequestInterface $request): Request
{
    return new Request([$request->getBody()->getContents()]);

}

