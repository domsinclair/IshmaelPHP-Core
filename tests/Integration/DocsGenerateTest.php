<?php

declare(strict_types=1);

namespace Tests\Integration;

final class DocsGenerateTest extends CliTestCase
{
    public function testDocsGenerateProducesReferenceFiles(): void
    {
        $bin = $this->repoRoot . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ish';
        $res = $this->runPhpScript($bin, ['docs:generate'], $this->appRoot);
        $this->assertSame(0, $res['exit'], 'docs:generate failed: ' . $res['err'] . "\nOUT:" . $res['out']);
        $refDir = $this->coreRoot . DIRECTORY_SEPARATOR . 'Documentation' . DIRECTORY_SEPARATOR . 'reference';
        $this->assertDirectoryExists($refDir);
        $this->assertFileExists($refDir . DIRECTORY_SEPARATOR . 'cli.md', 'CLI reference should be generated');
        $this->assertFileExists($refDir . DIRECTORY_SEPARATOR . 'modules.md', 'Modules index should be generated');
        $this->assertFileExists($refDir . DIRECTORY_SEPARATOR . 'core-api' . DIRECTORY_SEPARATOR . 'index.md', 'Core API placeholder should be present');
    }
}
