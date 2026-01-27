<?php

declare(strict_types=1);

namespace Ishmael\Core;

/**
 * Handles license verification and trial tracking for modules.
 * Note: Groundwork implementation; license server integration is not yet present.
 */
final class LicenseManager
{
    /**
     * Check if a valid license token is present for a given module.
     *
     * @param string $moduleName
     * @return bool
     */
    public static function hasLicense(string $moduleName): bool
    {
        // Groundwork: Placeholder for license check.
        // In the future, this might check a local file or environment variable.
        return false;
    }

    /**
     * Get the license tier for a given module (v0.1 groundwork).
     *
     * @param string $moduleName
     * @return string 'community'|'commercial'
     */
    public static function getTier(string $moduleName): string
    {
        // For now, we look for a 'license' key in the module's manifest
        $manifest = ModuleManager::$modules[$moduleName]['manifest'] ?? [];
        return (string)($manifest['license']['tier'] ?? 'community');
    }

    /**
     * Check if a trial period is currently active for a given module.
     *
     * @param string $moduleName
     * @return bool
     */
    public static function isTrialActive(string $moduleName): bool
    {
        // Groundwork: Placeholder for trial tracking.
        return false;
    }
}
