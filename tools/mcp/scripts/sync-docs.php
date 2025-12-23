#!/usr/bin/env php

<?php



declare(strict_types=1);



// Sync repository Docs/ and site/ into tools/mcp/resources/{Docs,site}

// for packaging the MCP with a bundled doc set.



function rrmdir(string $dir): void {

    if (!is_dir($dir)) { return; }

    $it = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);

    $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($ri as $file) {

        if ($file->isDir()) {

            @rmdir($file->getPathname());

        } else {

            @unlink($file->getPathname());

        }

    }

    @rmdir($dir);

}



function rcopy(string $src, string $dst): void {

    if (!is_dir($src)) { return; }

    if (!is_dir($dst)) {

        if (!@mkdir($dst, 0777, true) && !is_dir($dst)) {

            throw new RuntimeException("Failed to create directory: $dst");

        }

    }

    $it = new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS);

    $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::SELF_FIRST);

    foreach ($ri as $item) {

        $target = $dst . DIRECTORY_SEPARATOR . $ri->getSubPathName();

        if ($item->isDir()) {

            if (!is_dir($target) && !@mkdir($target, 0777, true) && !is_dir($target)) {

                throw new RuntimeException("Failed to create directory: $target");

            }

        } else {

            if (!@copy($item->getPathname(), $target)) {

                throw new RuntimeException("Failed to copy to: $target");

            }

        }

    }

}



$repoRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..');

if ($repoRoot === false) {

    fwrite(STDERR, "[sync-docs] Failed to resolve repo root.\n");

    exit(1);

}



$docsSrc = $repoRoot . DIRECTORY_SEPARATOR . 'Docs';

$siteSrc = $repoRoot . DIRECTORY_SEPARATOR . 'site';



$resourcesBase = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');

if ($resourcesBase === false) {

    fwrite(STDERR, "[sync-docs] Failed to resolve tools/mcp/scripts base.\n");

    exit(1);

}

$resourcesBase = dirname($resourcesBase) . DIRECTORY_SEPARATOR . 'resources';



// Safety: ensure we are operating under repo root

if (strpos($resourcesBase, $repoRoot) !== 0) {

    fwrite(STDERR, "[sync-docs] Refusing to write outside repo root: $resourcesBase\n");

    exit(1);

}



@mkdir($resourcesBase, 0777, true);



// Sync Docs

$dstDocs = $resourcesBase . DIRECTORY_SEPARATOR . 'Docs';

rrmdir($dstDocs);

if (is_dir($docsSrc)) {

    rcopy($docsSrc, $dstDocs);

    fwrite(STDOUT, "[sync-docs] Copied Docs to $dstDocs\n");

} else {

    fwrite(STDERR, "[sync-docs] Source Docs/ not found at $docsSrc\n");

}



// Sync site

$dstSite = $resourcesBase . DIRECTORY_SEPARATOR . 'site';

rrmdir($dstSite);

if (is_dir($siteSrc)) {

    rcopy($siteSrc, $dstSite);

    fwrite(STDOUT, "[sync-docs] Copied site to $dstSite\n");

} else {

    fwrite(STDERR, "[sync-docs] Source site/ not found at $siteSrc\n");

}



fwrite(STDOUT, "[sync-docs] Done.\n");