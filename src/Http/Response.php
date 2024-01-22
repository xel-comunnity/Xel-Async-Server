<?php

namespace Xel\Async\Http;
use HttpSoft\Message\ResponseFactory;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use HttpSoft\Message\StreamFactory;

final class Response
{
    private ResponseInterface $responseFactory;
    private StreamFactory $streamFactory;

    public function __construct()
    {
        $this->responseFactory = (new ResponseFactory())->createResponse();
        $this->streamFactory = new StreamFactory();
    }

    public static function create(): Response
    {
        return new self();
    }

    public function json(array $data, int $status, bool $print = false): MessageInterface|ResponseInterface
    {
        $check = $print ? json_encode($data, JSON_PRETTY_PRINT) : json_encode($data);
        $body = $this->streamFactory->createStream($check);

        return $this->responseFactory
            ->withBody($body)
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public function plain(string $data, int $status): MessageInterface|ResponseInterface
    {
        $body = $this->streamFactory->createStream($data);
        return $this->responseFactory
            ->withBody($body)
            ->withHeader('Content-Type', 'text/plain')
            ->withStatus($status);
    }

    public function withError(array $errorMessage, int $status): ResponseInterface
    {
        $body = $this->streamFactory->createStream(json_encode($errorMessage));
        return $this->responseFactory
            ->withBody($body)
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}