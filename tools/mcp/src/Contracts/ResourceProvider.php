<?php

declare(strict_types=1);

namespace IshmaelPHP\McpServer\Contracts;

interface ResourceProvider
{
    /**
     * Return a list of available read-only resource identifiers.
     * Example identifiers: docs:intro, templates:module, feature-packs:list
     */
    public function listResources(): array;
}
