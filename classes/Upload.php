<?php

namespace Neat\Http\Server;

use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class Upload
{
    protected UploadedFileInterface $file;

    public function __construct(UploadedFileInterface $file)
    {
        $this->file = $file;
    }

    public function psr(): UploadedFileInterface
    {
        return $this->file;
    }

    public function moveTo(string $destination): void
    {
        if (!$this->ok()) {
            throw new RuntimeException('Cannot move invalid file upload');
        }
        if (!$this->file->getStream()->isReadable()) {
            throw new RuntimeException('Cannot move unreadable file upload');
        }
        $this->file->moveTo($destination);
    }

    public function path(): string
    {
        return $this->file->getStream()->getMetadata()['uri'];
    }

    public function size(): ?int
    {
        return $this->file->getSize();
    }

    public function clientName(): ?string
    {
        return $this->file->getClientFilename();
    }

    public function clientType(): ?string
    {
        return $this->file->getClientMediaType();
    }

    public function error(): int
    {
        return $this->file->getError();
    }

    public function ok(): bool
    {
        return $this->file->getError() === UPLOAD_ERR_OK;
    }
}
