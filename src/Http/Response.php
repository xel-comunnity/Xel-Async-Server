<?php

namespace Xel\Async\Http;
use DI\Container;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;

final class Response
{
    private ?ResponseInterface $responseFactory = null;
    private Container $register;

    public function __construct()
    {

    }

    public function __invoke(Container $register): Response
    {
        $this->register = $register;
        return $this;
    }

    private function lazyResponseFactory(): ?ResponseInterface
    {
        if ($this->responseFactory === null){
            /** @var Psr17Factory $responseFactory */
            $responseFactory = $this->register->get('ResponseFactory');
            $this->responseFactory = $responseFactory->createResponse();
        }
        return $this->responseFactory;
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
        return $this->lazyresponseFactory()
            ->withBody(Stream::create($check))
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
        return $this->lazyresponseFactory()
            ->withBody(Stream::create($data))
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
        return $this->lazyresponseFactory()
            ->withBody(Stream::create($errorMessage))
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}