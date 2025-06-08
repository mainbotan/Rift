<?php

namespace Rift\Core\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;
use Psr\Http\Message\ServerRequestInterface;

class Request
{
    private ServerRequestInterface $psrRequest;
    private array $parsedBody = [];
    private array $uploadedFiles = [];

    public function __construct(ServerRequestInterface $request)
    {
        $this->psrRequest = $request;
        $this->initializeParsedData();
    }

    private function initializeParsedData(): void
    {
        // Парсинг тела запроса
        try {
            $contentType = $this->getHeader('Content-Type') ?? '';
            $bodyContents = (string)$this->psrRequest->getBody();
            
            if (str_contains($contentType, 'application/json')) {
                $this->parsedBody = json_decode($bodyContents, true) ?? [];
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Invalid JSON: ' . json_last_error_msg());
                }
            } elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
                parse_str($bodyContents, $this->parsedBody);
            } elseif (!empty($bodyContents)) {
                $this->parsedBody = ['raw' => $bodyContents];
            }
        } catch (\Throwable $e) {
            $this->parsedBody = ['error' => $e->getMessage()];
        }

        // Обработка загруженных файлов
        $this->uploadedFiles = $this->normalizeUploadedFiles(
            $this->psrRequest->getUploadedFiles()
        );
    }

    private function normalizeUploadedFiles(array $uploadedFiles): array
    {
        $normalized = [];
        foreach ($uploadedFiles as $key => $file) {
            if (is_array($file)) {
                $normalized[$key] = $this->normalizeUploadedFiles($file);
            } else {
                $normalized[$key] = [
                    'name' => $file->getClientFilename(),
                    'type' => $file->getClientMediaType(),
                    'size' => $file->getSize(),
                    'tmp_name' => $file->getStream()->getMetadata('uri'),
                    'error' => $file->getError(),
                ];
            }
        }
        return $normalized;
    }

    public static function fromGlobals(): OperationOutcome
    {
        try {
            $psr17Factory = new Psr17Factory();
            $creator = new ServerRequestCreator(
                $psr17Factory,
                $psr17Factory,
                $psr17Factory,
                $psr17Factory
            );
            
            $psrRequest = $creator->fromGlobals();
            return Operation::success(new self($psrRequest));
        } catch (\Throwable $e) {
            return Operation::error(
                500, 
                'Failed to create request', 
                ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
        }
    }

    public function getMethod(): string
    {
        return $this->psrRequest->getMethod();
    }

    public function getPath(): string
    {
        return $this->psrRequest->getUri()->getPath();
    }

    public function getQueryParams(): array
    {
        return $this->psrRequest->getQueryParams();
    }

    public function getHeaders(): array
    {
        return $this->psrRequest->getHeaders();
    }

    public function getHeader(string $name): ?string
    {
        return $this->psrRequest->getHeaderLine($name) ?: null;
    }

    public function getBody(): array
    {
        return $this->parsedBody;
    }

    public function getFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function getAllData(): array
    {
        return [
            'query' => $this->getQueryParams(),
            'body' => $this->getBody(),
            'files' => $this->getFiles()
        ];
    }
    public function isJson(): bool {
        return str_contains($this->getHeader('Content-Type') ?? '', 'application/json');
    }
    public function getClientIp(): string {
        return $this->psrRequest->getServerParams()['REMOTE_ADDR'] ?? '';
    }

    public function getPsrRequest(): ServerRequestInterface
    {
        return $this->psrRequest;
    }
}