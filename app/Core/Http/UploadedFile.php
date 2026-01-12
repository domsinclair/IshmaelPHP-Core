<?php

declare(strict_types=1);

namespace Ishmael\Core\Http;

/**
 * Wraps an entry from the $_FILES array.
 */
class UploadedFile
{
    private string $name;
    private string $type;
    private string $tmpName;
    private int $error;
    private int $size;

    public function __construct(string $name, string $type, string $tmpName, int $error, int $size)
    {
        $this->name = $name;
        $this->type = $type;
        $this->tmpName = $tmpName;
        $this->error = $error;
        $this->size = $size;
    }

    public function getClientOriginalName(): string
    {
        return $this->name;
    }

    public function getClientMimeType(): string
    {
        return $this->type;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getRealPath(): string
    {
        return $this->tmpName;
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function moveTo(string $targetPath): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        return move_uploaded_file($this->tmpName, $targetPath);
    }
}
