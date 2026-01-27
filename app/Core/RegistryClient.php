<?php

declare(strict_types=1);

namespace Ishmael\Core;

/**
 * RegistryClient handles communication with the Ishmael Registry Service.
 * v0.1 - Basic XML discovery and parsing.
 */
final class RegistryClient
{
    private string $registryUrl;

    public function __construct(?string $registryUrl = null)
    {
        $this->registryUrl = $registryUrl ?: (string)config('app.registry_url', 'https://registry.vtlsoftware.co.uk/registry/feature-packs.xml');
    }

    /**
     * Returns all available packs from the registry.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $xmlContent = $this->fetchXml();
        if ($xmlContent === null) {
            return [];
        }

        try {
            $xml = new \SimpleXMLElement($xmlContent);
        } catch (\Exception $e) {
            Logger::error('Failed to parse registry XML: ' . $e->getMessage());
            return [];
        }

        $packs = [];
        foreach ($xml->{'feature-pack'} as $pack) {
            $id = (string)$pack->id;
            $packs[$id] = [
                'id'           => $id,
                'vendor'       => (string)$pack->vendor,
                'license'      => (string)$pack->license,
                'tier'         => (string)($pack->license['tier'] ?? 'community'),
                'capabilities' => (array)$pack->capabilities->capability,
                'download'     => (string)$pack->download,
                'version'      => (string)$pack->version,
                'description'  => (string)$pack->description,
            ];
        }

        return $packs;
    }

    /**
     * Filters packs by ID or capabilities.
     *
     * @param string $query
     * @return array<string, array<string, mixed>>
     */
    public function search(string $query): array
    {
        $query = strtolower($query);
        return array_filter($this->all(), function (array $pack) use ($query) {
            if (str_contains(strtolower($pack['id']), $query)) {
                return true;
            }
            foreach ($pack['capabilities'] as $capability) {
                if (str_contains(strtolower((string)$capability), $query)) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Retrieves a specific pack's metadata.
     *
     * @param string $id
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        $packs = $this->all();
        return $packs[$id] ?? null;
    }

    /**
     * Fetch the authoritative XML from the REGISTRY_URL.
     */
    private function fetchXml(): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'header'  => "User-Agent: IshmaelFramework-RegistryClient/0.1\r\n"
            ]
        ]);

        $content = @file_get_contents($this->registryUrl, false, $context);

        if ($content === false) {
            Logger::error("Failed to fetch registry from: {$this->registryUrl}");
            return null;
        }

        return $content;
    }
}
