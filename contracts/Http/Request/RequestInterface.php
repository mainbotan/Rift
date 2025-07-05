<?php

namespace Rift\Contracts\Http\Request;

use Psr\Http\Message\ServerRequestInterface;
use Rift\Core\Databus\OperationOutcome;

interface RequestInterface {
    /**
     * construct from psr request object
     */
    public function __construct(ServerRequestInterface $request);

    /**
     * fromGlobals variables public method
     * @return OperationOutcome
     */
    public static function fromGlobals(): OperationOutcome;
    
    /**
     * getMethod public method
     * @return string
     */
    public function getMethod(): string;

    /**
     * getPath public method
     * @return string
     */
    public function getPath(): string;

    /**
     * getQueryParams public method
     * @return array
     */
    public function getQueryParams(): array;

    /**
     * getHeaders public method
     * @return array
     */
    public function getHeaders(): array;

    /**
     * getHeader public method
     * @return ?string
     */
    public function getHeader(string $name): ?string;

    /**
     * getBody public method
     * @return array
     */
    public function getBody(): array;

    /**
     * getFiles public method
     * @return array
     */
    public function getFiles(): array;

    /**
     * getAllData public method
     * @return array
     */
    public function getAllData(): array;

    /**
     * isJson public method
     * @return bool
     */
    public function isJson(): bool;

    /**
     * getClientIp public method
     * @return string
     */
    public function getClientIp(): string;

    /**
     * getPsrRequest public method
     * @return ServerRequestInterface
     */
    public function getPsrRequest(): ServerRequestInterface;
}