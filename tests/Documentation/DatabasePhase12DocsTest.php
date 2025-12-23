<?php

declare(strict_types=1);

namespace Ishmael\Tests\Documentation;

use PHPUnit\Framework\TestCase;

final class DatabasePhase12DocsTest extends TestCase
{
    public function testDatabasePhase12GuideExistsAndHasHeadings(): void
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Documentation' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'phase-12-database-additions.md';
        $this->assertFileExists($path, 'Phase 12 database additions guide should exist');
        $contents = file_get_contents($path);
        $this->assertIsString($contents);
// Check for main title and Milestone 1 heading
        $this->assertStringContainsString('# Phase 12 — Database Additions', $contents);
        $this->assertStringContainsString('### 1) Schema and migrations foundation (relationships, indexes, custom PKs)', $contents);
        $this->assertStringContainsString('## Testing', $contents);
        $this->assertStringContainsString('## Notes', $contents);
    }
}
