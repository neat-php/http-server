<?php

namespace Neat\Http\Server;

use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class Upload
{
    /** @var UploadedFileInterface */
    protected $file;

    /**
     * File constructor
     *
     * @param UploadedFileInterface $file
     */
    public function __construct(UploadedFileInterface $file)
    {
        $this->file = $file;
    }

    /**
     * @return UploadedFileInterface
     */
    public function psr(): UploadedFileInterface
    {
        return $this->file;
    }

    /**
     * Move file to destination
     *
     * @param string $destination Full destination path including filename
     */
    public function moveTo($destination)
    {
        if (!$this->ok()) {
            throw new RuntimeException('Cannot move invalid file upload');
        }
        if (!$this->file->getStream()->isReadable()) {
            throw new RuntimeException('Cannot move unreadable file upload');
        }
        $this->file->moveTo($destination);
    }

    /**
     * Is this upload moved already?
     *
     * @return bool
     * @deprecated
     */
    public function moved()
    {
        trigger_error('Method:' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);

        return !$this->file->getStream()->isReadable();
    }

    /**
     * Get path to file
     *
     * @return string
     */
    public function path()
    {
        return $this->file->getStream()->getMetadata()['uri'];
    }

    /**
     * Get file size
     *
     * @return int|null
     */
    public function size()
    {
        return $this->file->getSize();
    }

    /**
     * Get file name according to the client (unsafe!)
     *
     * @return string|null
     */
    public function clientName()
    {
        return $this->file->getClientFilename();
    }

    /**
     * Get file type according to the client (unsafe!)
     *
     * @return string|null
     */
    public function clientType()
    {
        return $this->file->getClientMediaType();
    }

    /**
     * Get upload error code (one of the UPLOAD_ERR_* constants)
     *
     * @return int
     */
    public function error(): int
    {
        return $this->file->getError();
    }

    /**
     * Upload ok?
     *
     * @return bool
     */
    public function ok(): bool
    {
        return $this->file->getError() === UPLOAD_ERR_OK;
    }
}
