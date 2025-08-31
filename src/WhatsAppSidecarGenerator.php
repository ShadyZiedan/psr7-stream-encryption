<?php

declare(strict_types=1);

namespace WhatsApp\Psr7StreamEncryption;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use InvalidArgumentException;

class WhatsAppSidecarGenerator
{
    private const CHUNK_SIZE = 65536;
    private const MAC_SIZE = 10;

    private StreamInterface $stream;
    private string $mediaKey;
    private MediaType $mediaType;
    private WhatsAppKeyManager $keyManager;

    public function __construct(StreamInterface $stream, string $mediaKey, MediaType $mediaType)
    {
        $this->stream = $stream;
        $this->mediaKey = $mediaKey;
        $this->mediaType = $mediaType;
        $this->keyManager = new WhatsAppKeyManager();
        
        $this->validateInputs();
    }

    private function validateInputs(): void
    {
        if (strlen($this->mediaKey) !== 32) {
            throw new InvalidArgumentException('Media key must be exactly 32 bytes');
        }

        if (!$this->keyManager->isMediaTypeSupported($this->mediaType)) {
            throw new InvalidArgumentException('Unsupported media type: ' . $this->mediaType->value);
        }

        if (!$this->stream->isSeekable()) {
            throw new InvalidArgumentException('Stream must be seekable for sidecar generation');
        }
    }

    public function generate(): string
    {
        $expandedKey = $this->keyManager->expandMediaKey($this->mediaKey, $this->mediaType);
        $keys = $this->keyManager->splitExpandedKey($expandedKey);

        $sidecar = '';
        $streamSize = $this->stream->getSize();
        
        if ($streamSize === null) {
            throw new RuntimeException('Cannot determine stream size');
        }

        $originalPosition = $this->stream->tell();
        
        try {
            $chunkIndex = 0;
            $offset = 0;
            
            while ($offset < $streamSize) {
                $chunkStart = $offset;
                $chunkEnd = min($offset + self::CHUNK_SIZE + 16, $streamSize);
                
                $this->stream->seek($chunkStart);
                $chunkData = $this->stream->read($chunkEnd - $chunkStart);
                
                if ($chunkData === '') {
                    break;
                }

                $mac = hash_hmac('sha256', $chunkData, $keys['macKey'], true);
                $macTruncated = substr($mac, 0, self::MAC_SIZE);
                
                $sidecar .= $macTruncated;
                
                $offset += self::CHUNK_SIZE;
                $chunkIndex++;
            }
        } finally {
            $this->stream->seek($originalPosition);
        }

        return $sidecar;
    }

    public function generateForChunk(int $chunkIndex, int $chunkSize = null): string
    {
        $chunkSize = $chunkSize ?? self::CHUNK_SIZE;
        
        $expandedKey = $this->keyManager->expandMediaKey($this->mediaKey, $this->mediaType);
        $keys = $this->keyManager->splitExpandedKey($expandedKey);

        $streamSize = $this->stream->getSize();
        if ($streamSize === null) {
            throw new RuntimeException('Cannot determine stream size');
        }

        $chunkStart = $chunkIndex * $chunkSize;
        if ($chunkStart >= $streamSize) {
            throw new InvalidArgumentException('Chunk index out of bounds');
        }

        $chunkEnd = min($chunkStart + $chunkSize + 16, $streamSize);
        
        $originalPosition = $this->stream->tell();
        
        try {
            $this->stream->seek($chunkStart);
            $chunkData = $this->stream->read($chunkEnd - $chunkStart);
            
            if ($chunkData === '') {
                throw new RuntimeException('Failed to read chunk data');
            }

            $mac = hash_hmac('sha256', $chunkData, $keys['macKey'], true);
            return substr($mac, 0, self::MAC_SIZE);
        } finally {
            $this->stream->seek($originalPosition);
        }
    }

    public function getChunkCount(int $chunkSize = null): int
    {
        $chunkSize = $chunkSize ?? self::CHUNK_SIZE;
        $streamSize = $this->stream->getSize();
        
        if ($streamSize === null) {
            throw new RuntimeException('Cannot determine stream size');
        }

        return (int) ceil($streamSize / $chunkSize);
    }

    public function getStream(): StreamInterface
    {
        return $this->stream;
    }

    public function getMediaKey(): string
    {
        return $this->mediaKey;
    }

    public function getMediaType(): MediaType
    {
        return $this->mediaType;
    }

    public function getKeyManager(): WhatsAppKeyManager
    {
        return $this->keyManager;
    }

    public function setKeyManager(WhatsAppKeyManager $keyManager): void
    {
        $this->keyManager = $keyManager;
    }
}


