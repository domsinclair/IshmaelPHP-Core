<?php

declare(strict_types=1);

namespace Ishmael\Tests;

use Ishmael\Core\Capability;
use Ishmael\Core\CapabilityException;
use Ishmael\Core\ModuleManager;
use PHPUnit\Framework\TestCase;

class CapabilityTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset ModuleManager state
        $reflection = new \ReflectionClass(ModuleManager::class);
        $property = $reflection->getProperty('modules');
        $property->setAccessible(true);
        $property->setValue([]);
    }

    public function testIsAvailableCommunityAlwaysTrue(): void
    {
        ModuleManager::$modules['TestModule'] = [
            'capabilities' => [
                'basic-feature' => 'community'
            ]
        ];

        $this->assertTrue(Capability::isAvailable('TestModule', 'basic-feature'));
    }

    public function testIsAvailablePremiumInProductionWithoutLicenseIsFalse(): void
    {
        putenv('APP_ENV=production');
        ModuleManager::$modules['TestModule'] = [
            'capabilities' => [
                'premium-feature' => 'premium'
            ]
        ];

        $this->assertFalse(Capability::isAvailable('TestModule', 'premium-feature'));
        putenv('APP_ENV=development'); // Restore
    }

    public function testIsAvailablePremiumInDevelopmentWithoutLicenseOrTrialIsFalse(): void
    {
        putenv('APP_ENV=development');
        ModuleManager::$modules['TestModule'] = [
            'capabilities' => [
                'premium-feature' => 'premium'
            ]
        ];

        $this->assertFalse(Capability::isAvailable('TestModule', 'premium-feature'));
    }

    public function testAssertThrowsExceptionWhenUnavailable(): void
    {
        putenv('APP_ENV=development');
        ModuleManager::$modules['TestModule'] = [
            'capabilities' => [
                'premium-feature' => 'premium'
            ]
        ];

        $this->expectException(CapabilityException::class);
        $this->expectExceptionMessage("To use this premium feature in development, please start a trial or apply a license.");

        Capability::assert('TestModule', 'premium-feature');
    }

    public function testUnknownCapabilityDefaultsToCommunity(): void
    {
        ModuleManager::$modules['TestModule'] = [
            'capabilities' => []
        ];

        // Should default to community and be available
        $this->assertTrue(Capability::isAvailable('TestModule', 'unknown-feature'));
    }
}
