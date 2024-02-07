<?php

namespace Xel\Async\Http;
use DI\Container;
use HttpSoft\Message\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use HttpSoft\Message\StreamFactory;
use Xel\Async\Http\Container\Register;

final class Response
{
    private ?ResponseFactory $responseFactory = null;
    private ?StreamFactory $streamFactory = null;
    private Container $register;

    public function __construct()
    {
    }

    public function __invoke(Container $register): Response
    {
        $this->register = $register;
        return $this;
    }

    private function lazyStreamFactory()
    {
        if ($this->streamFactory === null){
            $this->streamFactory = $this->register->get('StreamFactory');
        }
        return $this->streamFactory;
    }

    private function lazyResponseFactory()
    {
        if ($this->responseFactory === null){
            $this->responseFactory = $this->register->get('ResponseFactory');
        }
        return $this->responseFactory->createResponse();
    }


    /**
     * @param array<string|int, mixed> $data
     * @param int $status
     * @param bool $print
     * @return ResponseInterface
     */
    public function json(array $data, int $status, bool $print = false):ResponseInterface
    {
        $check = $print ? json_encode($data, JSON_PRETTY_PRINT) : json_encode($data);
        $body = $this->lazystreamFactory()->createStream($check);
        return $this->lazyresponseFactory()
            ->withBody($body)
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * @param string $data
     * @param int $status
     * @return ResponseInterface
     */
    public function plain(string $data, int $status): ResponseInterface
    {
        $body = $this->lazystreamFactory()->createStream($data);
        return $this->lazyresponseFactory()
            ->withBody($body)
            ->withHeader('Content-Type', 'text/plain')
            ->withStatus($status);
    }

    /**
     * @param array<string|int, mixed> $errorMessage
     * @param int $status
     * @return ResponseInterface
     */
    public function withError(array $errorMessage, int $status): ResponseInterface
    {
        $body = $this->lazystreamFactory()->createStream(json_encode($errorMessage));
        return $this->lazyresponseFactory()
            ->withBody($body)
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}