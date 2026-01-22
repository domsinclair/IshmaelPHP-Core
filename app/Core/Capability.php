<?php

declare(strict_types=1);

namespace Ishmael\Core;

/**
 * Service to manage and verify module capabilities (community vs. premium).
 * Enforces environment-aware gating based on the "Constitution of Ishmael".
 */
final class Capability
{
    /**
     * Check if a specific capability is available for a module.
     *
     * @param string $moduleName
     * @param string $capabilityId
     * @return bool
     */
    public static function isAvailable(string $moduleName, string $capabilityId): bool
    {
        $module = ModuleManager::get($moduleName);
        if (!$module) {
            return false;
        }

        $capabilities = $module['capabilities'] ?? [];
        $capType = $capabilities[$capabilityId] ?? 'community';

        // Community capabilities are always available.
        if ($capType === 'community') {
            return true;
        }

        // Premium capabilities require a license or trial.
        $appEnv = getenv('APP_ENV') ?: 'development';

        if ($appEnv === 'production') {
            return LicenseManager::hasLicense($moduleName);
        }

        // In development/testing, premium is available via license or trial.
        return LicenseManager::hasLicense($moduleName) || LicenseManager::isTrialActive($moduleName);
    }

    /**
     * Assert that a capability is available, otherwise throw an exception.
     * Provides a "mentorship prompt" in development for missing premium features.
     *
     * @param string $moduleName
     * @param string $capabilityId
     * @throws CapabilityException
     */
    public static function assert(string $moduleName, string $capabilityId): void
    {
        if (self::isAvailable($moduleName, $capabilityId)) {
            return;
        }

        $appEnv = getenv('APP_ENV') ?: 'development';
        $message = "Capability '{$capabilityId}' is not available for module '{$moduleName}'.";

        if ($appEnv !== 'production') {
            $message .= " To use this premium feature in development, please start a trial or apply a license.";
        }

        throw new CapabilityException($message);
    }
}
