<?php

namespace BitL\Debug\Tests;

use BitL\Debug\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function test_can_create_client_with_defaults(): void
    {
        $client = new Client();

        $this->assertInstanceOf(Client::class, $client);
    }

    public function test_can_create_client_with_custom_config(): void
    {
        $client = new Client(
            host: '192.168.1.100',
            port: 9999,
            timeout: 5
        );

        $this->assertInstanceOf(Client::class, $client);
    }

    public function test_format_value_handles_null(): void
    {
        $client = new Client();
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('formatValue');
        $method->setAccessible(true);

        $this->assertSame('null', $method->invoke($client, null));
    }

    public function test_format_value_handles_bool(): void
    {
        $client = new Client();
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('formatValue');
        $method->setAccessible(true);

        $this->assertSame('true', $method->invoke($client, true));
        $this->assertSame('false', $method->invoke($client, false));
    }

    public function test_format_value_handles_string(): void
    {
        $client = new Client();
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('formatValue');
        $method->setAccessible(true);

        $this->assertSame('"hello"', $method->invoke($client, 'hello'));
    }

    public function test_get_type_returns_class_name_for_objects(): void
    {
        $client = new Client();
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('getType');
        $method->setAccessible(true);

        $this->assertSame('stdClass', $method->invoke($client, new \stdClass()));
        $this->assertSame(Client::class, $method->invoke($client, $client));
    }

    public function test_get_type_returns_type_for_primitives(): void
    {
        $client = new Client();
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('getType');
        $method->setAccessible(true);

        $this->assertSame('string', $method->invoke($client, 'hello'));
        $this->assertSame('integer', $method->invoke($client, 42));
        $this->assertSame('array', $method->invoke($client, []));
        $this->assertSame('boolean', $method->invoke($client, true));
    }
}
