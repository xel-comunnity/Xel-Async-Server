<?php

namespace Xel\Async\Http;
use Swoole\Http\Response;
class Responses
{
    private Response $response;
    private string $path;
    public function __invoke(Response $response, string $path): static
    {
        $this->response = $response;
        return $this;
    }

    /*******************************************************************************************************************
     * Response Maker Interface
     ******************************************************************************************************************/
    // ? return plain
    public function plain(string $data, int $http_code): void
    {
        $this->response->setStatusCode($http_code);
        $this->response->header("Content-Type", "text/plain");
        $this->response->end($data);
    }
    // ? return json
    public function json(mixed $data, bool $enable_pretty_print, int $http_code): void
    {
        // ? process data first
        $data = $enable_pretty_print === true ?json_encode($data,JSON_PRETTY_PRINT):json_encode($data);
        $this->response->setStatusCode($http_code);
        $this->response->header("Content-Type", "application/json");
        $this->response->end($data);
    }
    // ? downloadable file
    public function downloadable(mixed $file, string $type, int $http_code): void
    {
        // Check if the file exists
        if (!file_exists($file)) {
            // Return a response with a 404 status code if the file doesn't exist
            $this->response->status(404);
            $this->response->end("File not found");
        }

        // Set the response header indicating the content type
        $this->response->header("Content-Type", $type);

        // Set the response header to force download
        $filename = basename($file);
        $this->response->header("Content-Disposition", "attachment; filename=\"$filename\"");

        // Read the file content
        $fileContent = file_get_contents($file);

        // Set the HTTP response code
        $this->response->status($http_code);

        // Send the file content in the response body
        $this->response->end($fileContent);
    }
    /*******************************************************************************************************************
     * Cookie Maker
     ******************************************************************************************************************/
    public function setCookie
    (
        string $name,
        string $value = '',
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = false,
        string $sameSite = '',
        string $priority = ''
    ): static
    {
        $this->response->setCookie($name, $value, $expire, $path, $domain,$secure, $httponly, $sameSite, $priority);
        return $this;
    }

    public function compressedDisplay(string $display): void
    {
        $data = file_get_contents($this->path.$display);
        $this->response->header('Content-Type', 'text/html');
        $this->response->end($data);
    }

    public function OptimizedDisplay(string $display): void
    {
        $data = file_get_contents($this->path.$display);
        $this->response->sendfile($data);

    }

    public function Display(string $display): void
    {
        $data = file_get_contents($this->path.$display);
        $this->response->header('Content-Type', 'text/html');
        $this->response->end($data);
    }
}