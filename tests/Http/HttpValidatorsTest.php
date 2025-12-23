<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\Http\HttpValidators;
use PHPUnit\Framework\TestCase;

final class HttpValidatorsTest extends TestCase
{
    public function testMakeEtagIsStableForSamePayload(): void
    {
        $a = HttpValidators::makeEtag('payload');
        $b = HttpValidators::makeEtag('payload');
        $this->assertSame($a, $b);
        $this->assertStringStartsWith('"', $a);
        $this->assertStringEndsWith('"', $a);
    }

    public function testMakeEtagDiffersForDifferentPayloads(): void
    {
        $a = HttpValidators::makeEtag('a');
        $b = HttpValidators::makeEtag('b');
        $this->assertNotSame($a, $b);
    }

    public function testWeakAndStrongComparisonSemantics(): void
    {
        $strong = HttpValidators::makeEtag('x');
        $weak = 'W/' . $strong;
// Strong compare requires exact equality without W/
        $this->assertTrue(HttpValidators::isEtagMatch([$strong], $strong, false));
        $this->assertFalse(HttpValidators::isEtagMatch([$weak], $strong, false));
// Weak allowed: values match ignoring W/
        $this->assertTrue(HttpValidators::isEtagMatch([$weak], $strong, true));
// '*' should match any
        $this->assertTrue(HttpValidators::isEtagMatch(['*'], $strong, true));
    }
}
