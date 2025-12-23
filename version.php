<?php
/**
 * Ishmael Core version string.
 *
 * This file provides a single source of truth for the framework version used by the CLI
 * and documentation. Bump as part of release tagging.
 */
return trim(file_get_contents(__DIR__ . '/VERSION'));
