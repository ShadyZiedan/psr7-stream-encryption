<?php

declare(strict_types=1);

namespace WhatsApp\Psr7StreamEncryption;

use GuzzleHttp\Psr7\AppendStream;
use GuzzleHttp\Psr7\LimitStream;
use GuzzleHttp\Psr7\Utils;
use Jsq\EncryptionStreams\HashingStream;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class WhatsAppMacValidator implements StreamInterface
{
    private const MAC_SIZE = 10;

    private StreamInterface $sourceStream;
    private string $iv;
    private string $macKey;
    private ?StreamInterface $validatedStream = null;

    public function __construct(StreamInterface $encryptedStream, string $iv, string $macKey)
    {
        $this->sourceStream = $encryptedStream;
        $this->iv = $iv;
        $this->macKey = $macKey;
    }

    private function getValidatedStream(): StreamInterface
    {
        if ($this->validatedStream !== null) {
            return $this->validatedStream;
        }

        $totalSize = $this->sourceStream->getSize();
        if ($totalSize === null) {
            throw new RuntimeException('Cannot determine stream size');
        }

        if ($totalSize < self::MAC_SIZE) {
            throw new RuntimeException('Encrypted data too short');
        }

        $encryptedSize = $totalSize - self::MAC_SIZE;
        $this->sourceStream->rewind();

        $encryptedDataStream = new LimitStream($this->sourceStream, $encryptedSize);

        $ivStream = Utils::streamFor($this->iv);
        $validationStream = new AppendStream([$ivStream, $encryptedDataStream]);

        $expectedMac = '';
        $hashingStream = new HashingStream(
            $validationStream,
            $this->macKey,
            static function(string $hash) use (&$expectedMac): void {
                $expectedMac = substr($hash, 0, self::MAC_SIZE);
            },
            'sha256'
        );

        $hashingStream->getContents();

        $this->sourceStream->rewind();
        $macStream = new LimitStream($this->sourceStream, self::MAC_SIZE, $encryptedSize);
        $actualMac = $macStream->getContents();

        if (strlen($actualMac) !== self::MAC_SIZE) {
            throw new RuntimeException('Failed to read MAC');
        }

        if (!hash_equals($actualMac, $expectedMac)) {
            throw new RuntimeException('MAC verification failed');
        }

        $this->sourceStream->rewind();
        $this->validatedStream = new LimitStream($this->sourceStream, $encryptedSize);

        return $this->validatedStream;
    }

    public function __toString(): string
    {
        try {
            return $this->getValidatedStream()->__toString();
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function close(): void
    {
        $this->validatedStream?->close();
        $this->sourceStream->close();
        $this->validatedStream = null;
    }

    public function detach()
    {
        $result = $this->validatedStream?->detach();
        $this->sourceStream->detach();
        $this->validatedStream = null;
        return $result;
    }

    public function getSize(): ?int
    {
        return $this->getValidatedStream()->getSize();
    }

    public function tell(): int
    {
        return $this->getValidatedStream()->tell();
    }

    public function eof(): bool
    {
        return $this->getValidatedStream()->eof();
    }

    public function isSeekable(): bool
    {
        return $this->getValidatedStream()->isSeekable();
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->getValidatedStream()->seek($offset, $whence);
    }

    public function rewind(): void
    {
        $this->getValidatedStream()->rewind();
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
        return $this->getValidatedStream()->read($length);
    }

    public function getContents(): string
    {
        return $this->getValidatedStream()->getContents();
    }

    public function getMetadata(?string $key = null)
    {
        return $this->getValidatedStream()->getMetadata($key);
    }
}