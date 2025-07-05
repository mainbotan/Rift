<?php

namespace Rift\Contracts\Http\RoutesBox;

interface RoutesBoxInterface {
    /**
     * getRoutes public method
     * transfer object to routes array
     * @return array
     */
    public function getRoutes(): array;
    
    /**
     * GET method
     * fluence method
     * @return self
     */
    public function get(string $path, string $handler): self;

    /**
     * POST method
     * fluence method
     * @return self
     */
    public function post(string $path, string $handler): self;

    /**
     * PUT method
     * fluence method
     * @return self
     */
    public function put(string $path, string $handler): self;

    /**
     * PATCH method
     * fluence method
     * @return self
     */
    public function patch(string $path, string $handler): self;

    /**
     * DELETE method
     * fluence method
     * @return self
     */
    public function delete(string $path, string $handler): self;

    /**
     * grouping routes
     * fluence method
     * @return self
     */
    public function group(string $prefix, callable $callback): self;
}