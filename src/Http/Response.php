<?php

namespace Xel\Async\Http;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Response
{
    private Container $register;

    public function __invoke(Container $register): Response
    {
        $this->register = $register;
        return $this;
    }

    /**
     * @param mixed $content
     * @return StreamInterface
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function StreamFactory(mixed $content): StreamInterface
    {

        $streamFactory = $this->register->get('StreamFactory');
        return $this->streamMaker($streamFactory, $content);
    }

    /**
     * @param Psr17Factory $factory
     * @param mixed $content
     * @return StreamInterface
     */
    private function streamMaker(Psr17Factory $factory, mixed $content): StreamInterface
    {
        return $factory->createStream($content);
    }

    /**
     * @return ResponseInterface
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function ResponseFactory(): ResponseInterface
    {
        /** @var Psr17Factory $responseFactory */
        $responseFactory = $this->register->get('ResponseFactory');
        return $responseFactory->createResponse();
    }


    /**
     * @param array<string|integer, mixed> $data
     * @param int $status
     * @param bool $print
     * @return ResponseInterface
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function json(array $data, int $status, bool $print = false):ResponseInterface
    {
        $check = $print ? json_encode($data, JSON_PRETTY_PRINT) : json_encode($data);
        return $this->responseFactory()
            ->withBody($this->StreamFactory($check))
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * @param string $data
     * @param int $status
     * @return ResponseInterface
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function plain(string $data, int $status): ResponseInterface
    {
        return $this->responseFactory()
            ->withBody($this->StreamFactory($data))
            ->withHeader('Content-Type', 'text/plain')
            ->withStatus($status);
    }

    /**
     * @param array<string|int, mixed> $errorMessage
     * @param int $status
     * @return ResponseInterface
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function withError(array $errorMessage, int $status): ResponseInterface
    {
        return $this->responseFactory()
            ->withBody($this->StreamFactory($errorMessage))
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}