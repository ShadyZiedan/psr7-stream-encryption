<?php

declare(strict_types=1);

namespace WhatsApp\Psr7StreamEncryption;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

class WhatsAppMacValidator implements StreamInterface
{
    private const MAC_SIZE = 10;
    
    private StreamInterface $encryptedStream;
    private string $iv;
    private string $macKey;
    private ?string $encryptedData = null;
    private ?int $size = null;
    private int $position = 0;

    public function __construct(StreamInterface $encryptedStream, string $iv, string $macKey)
    {
        $this->encryptedStream = $encryptedStream;
        $this->iv = $iv;
        $this->macKey = $macKey;
    }

    private function validateMac(): void
    {
        if ($this->encryptedData !== null) {
            return;
        }

        $allData = $this->encryptedStream->getContents();
        $this->encryptedStream->rewind();

        if (strlen($allData) < self::MAC_SIZE) {
            throw new RuntimeException('Encrypted data too short');
        }

        $encryptedFile = substr($allData, 0, -self::MAC_SIZE);
        $mac = substr($allData, -self::MAC_SIZE);

        $dataToVerify = $this->iv . $encryptedFile;
        $expectedMac = hash_hmac('sha256', $dataToVerify, $this->macKey, true);
        $expectedMacTruncated = substr($expectedMac, 0, self::MAC_SIZE);

        if (!hash_equals($mac, $expectedMacTruncated)) {
            throw new RuntimeException('MAC verification failed');
        }

        $this->encryptedData = $encryptedFile;
        $this->size = strlen($this->encryptedData);
        $this->position = 0;
    }

    public function __toString(): string
    {
        $this->validateMac();
        return $this->encryptedData;
    }

    public function close(): void
    {
        $this->encryptedData = null;
        $this->position = 0;
        $this->size = null;
        $this->encryptedStream->close();
    }

    public function detach()
    {
        $this->encryptedData = null;
        $this->position = 0;
        $this->size = null;
        return $this->encryptedStream->detach();
    }

    public function getSize(): ?int
    {
        $this->validateMac();
        return $this->size;
    }

    public function tell(): int
    {
        $this->validateMac();
        return $this->position;
    }

    public function eof(): bool
    {
        $this->validateMac();
        return $this->position >= $this->size;
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->validateMac();
        
        switch ($whence) {
            case SEEK_SET:
                $this->position = $offset;
                break;
            case SEEK_CUR:
                $this->position += $offset;
                break;
            case SEEK_END:
                $this->position = $this->size + $offset;
                break;
            default:
                throw new RuntimeException('Invalid whence value');
        }
        
        if ($this->position < 0) {
            throw new RuntimeException('Cannot seek to negative position');
        }
        
        if ($this->position > $this->size) {
            throw new RuntimeException('Cannot seek beyond end of stream');
        }
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new RuntimeException('Stream is not writable');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        $this->validateMac();
        
        if ($this->position >= $this->size) {
            return '';
        }
        
        $remaining = $this->size - $this->position;
        $readLength = min($length, $remaining);
        
        $data = substr($this->encryptedData, $this->position, $readLength);
        $this->position += $readLength;
        
        return $data;
    }

    public function getContents(): string
    {
        $this->validateMac();
        
        if ($this->position >= $this->size) {
            return '';
        }
        
        $data = substr($this->encryptedData, $this->position);
        $this->position = $this->size;
        
        return $data;
    }

    public function getMetadata(?string $key = null)
    {
        return $this->encryptedStream->getMetadata($key);
    }
}
