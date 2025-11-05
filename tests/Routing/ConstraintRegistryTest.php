<?php
declare(strict_types=1);

use Ishmael\Core\ConstraintRegistry;
use PHPUnit\Framework\TestCase;

final class ConstraintRegistryTest extends TestCase
{
    public function testBuiltInPatternsAndConversion(): void
    {
        // int
        $this->assertSame('\\d+', ConstraintRegistry::getPattern('int'));
        $this->assertSame(123, ConstraintRegistry::convert('int', '123'));
        // numeric
        $this->assertSame('\\d+(?:\\.\\d+)?', ConstraintRegistry::getPattern('numeric'));
        $this->assertSame(12.5, ConstraintRegistry::convert('numeric', '12.5'));
        // bool
        $this->assertNotNull(ConstraintRegistry::getPattern('bool'));
        $this->assertTrue(ConstraintRegistry::convert('bool', 'true'));
        $this->assertFalse(ConstraintRegistry::convert('bool', 'no'));
        // slug and decoding of percent-encoding
        $pat = ConstraintRegistry::getPattern('slug');
        $this->assertNotNull($pat);
        $this->assertSame('ümlaut', ConstraintRegistry::convert('slug', rawurlencode('ümlaut')));
        // uuid normalization
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', ConstraintRegistry::convert('uuid', '550E8400-E29B-41D4-A716-446655440000'));
    }

    public function testCustomConstraintRegistration(): void
    {
        ConstraintRegistry::add('hex', '[A-Fa-f0-9]+', static fn (string $v): string => strtolower($v));
        $this->assertSame('[A-Fa-f0-9]+', ConstraintRegistry::getPattern('hex'));
        $this->assertSame('deadbeef', ConstraintRegistry::convert('hex', 'DEADBEEF'));
    }
}
