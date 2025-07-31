<?php

declare(strict_types=1);

namespace Tests\Contracts;

use PHPUnit\Framework\TestCase;
use Rift\Core\Databus\Result;
use Rift\Core\Databus\ResultType;

class OperationTest extends TestCase
{
    public function testSuccessOperationReturnsValidDTO(): void
    {
        $result = ['message' => 'OK'];
        $response = Result::Success($result);

        $this->assertInstanceOf(ResultType::class, $response);
        $this->assertEquals(200, $response->code);
        $this->assertSame($result, $response->result);
        $this->assertNull($response->error);
        $this->assertArrayHasKey('metrics', $response->meta);
        $this->assertArrayHasKey('debug', $response->meta);
    }

    public function testErrorOperationReturnsValidDTO(): void
    {
        $errorMessage = 'Something broke';
        $response = Result::Failure(500, $errorMessage);

        $this->assertInstanceOf(ResultType::class, $response);
        $this->assertEquals(500, $response->code);
        $this->assertNull($response->result);
        $this->assertEquals($errorMessage, $response->error);
        $this->assertArrayHasKey('debug', $response->meta);
    }

    public function testOperationWithDebugAndMetrics(): void
    {
        $response = Result::Success(['ok' => true])
            ->withMetric('exec_time', 100)
            ->addDebugData('sql', 'SELECT * FROM users');

        $this->assertEquals(100, $response->getMetric('exec_time'));
        $this->assertEquals('SELECT * FROM users', $response->meta['debug']['sql']);
    }
}
