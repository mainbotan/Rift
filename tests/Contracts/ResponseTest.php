<?php

declare(strict_types=1);

namespace Tests\Contracts;

use PHPUnit\Framework\TestCase;
use Rift\Core\Contracts\Response;
use Rift\Core\Contracts\ResponseDTO;

class ResponseTest extends TestCase
{
    public function testSuccessResponseReturnsValidDTO(): void
    {
        $result = ['message' => 'OK'];
        $response = Response::success($result);

        $this->assertInstanceOf(ResponseDTO::class, $response);
        $this->assertEquals(200, $response->code);
        $this->assertSame($result, $response->result);
        $this->assertNull($response->error);
        $this->assertArrayHasKey('metrics', $response->meta);
        $this->assertArrayHasKey('debug', $response->meta);
    }

    public function testErrorResponseReturnsValidDTO(): void
    {
        $errorMessage = 'Something broke';
        $response = Response::error(500, $errorMessage);

        $this->assertInstanceOf(ResponseDTO::class, $response);
        $this->assertEquals(500, $response->code);
        $this->assertNull($response->result);
        $this->assertEquals($errorMessage, $response->error);
        $this->assertArrayHasKey('debug', $response->meta);
    }

    public function testResponseWithDebugAndMetrics(): void
    {
        $response = Response::success(['ok' => true])
            ->withMetric('exec_time', 100)
            ->addDebugData('sql', 'SELECT * FROM users');

        $this->assertEquals(100, $response->getMetric('exec_time'));
        $this->assertEquals('SELECT * FROM users', $response->meta['debug']['sql']);
    }
}
