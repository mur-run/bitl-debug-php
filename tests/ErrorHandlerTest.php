<?php

namespace BitL\Debug\Tests;

use BitL\Debug\BitL;
use BitL\Debug\ErrorHandler;
use PHPUnit\Framework\TestCase;

class ErrorHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        BitL::disable(); // Don't send to BitL during tests
    }

    protected function tearDown(): void
    {
        ErrorHandler::unregister();
        parent::tearDown();
    }

    public function test_can_register_and_unregister(): void
    {
        ErrorHandler::register();
        // Should not throw
        $this->assertTrue(true);

        ErrorHandler::unregister();
        // Should not throw
        $this->assertTrue(true);
    }

    public function test_error_level_to_string(): void
    {
        $reflection = new \ReflectionClass(ErrorHandler::class);
        $method = $reflection->getMethod('errorLevelToString');
        $method->setAccessible(true);

        $this->assertSame('Error', $method->invoke(null, E_ERROR));
        $this->assertSame('Warning', $method->invoke(null, E_WARNING));
        $this->assertSame('Notice', $method->invoke(null, E_NOTICE));
        $this->assertSame('Deprecated', $method->invoke(null, E_DEPRECATED));
        $this->assertSame('Parse Error', $method->invoke(null, E_PARSE));
    }
}
