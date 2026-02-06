<?php

namespace BitL\Debug\Tests;

use BitL\Debug\BitL;
use BitL\Debug\Client;
use PHPUnit\Framework\TestCase;

class BitLTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        BitL::enable();
    }

    public function test_can_enable_and_disable(): void
    {
        $this->assertTrue(BitL::isEnabled());

        BitL::disable();
        $this->assertFalse(BitL::isEnabled());

        BitL::enable();
        $this->assertTrue(BitL::isEnabled());
    }

    public function test_can_set_custom_client(): void
    {
        $client = new Client('127.0.0.1', 9999);
        BitL::setClient($client);

        $this->assertSame($client, BitL::client());
    }

    public function test_dump_returns_value(): void
    {
        BitL::disable(); // Don't actually send

        $value = ['foo' => 'bar'];
        $result = BitL::dump($value);

        $this->assertSame($value, $result);
    }

    public function test_dump_does_nothing_when_disabled(): void
    {
        BitL::disable();

        // Should not throw, should return the value
        $result = BitL::dump('test');
        $this->assertSame('test', $result);
    }
}
