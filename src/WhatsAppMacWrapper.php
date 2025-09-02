<?php

declare(strict_types=1);

namespace WhatsApp\Psr7StreamEncryption;

use GuzzleHttp\Psr7\AppendStream;
use GuzzleHttp\Psr7\Utils;
use Jsq\EncryptionStreams\HashingStream;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

// MAC WRAPPER - for adding MAC to encrypted data
class WhatsAppMacWrapper implements StreamInterface
{
    private const MAC_SIZE = 10;

    private StreamInterface $baseStream;
    private string $iv;
    private string $macKey;
    private ?StreamInterface $finalStream = null;

    public function __construct(StreamInterface $encryptedStream, string $iv, string $macKey)
    {
        $this->baseStream = $encryptedStream;
        $this->iv = $iv;
        $this->macKey = $macKey;
    }

    private function buildFinalStream(): StreamInterface
    {
        if ($this->finalStream !== null) {
            return $this->finalStream;
        }

        $this->baseStream->rewind();

        $ivStream = Utils::streamFor($this->iv);
        $macCalculationStream = new AppendStream([$ivStream, $this->baseStream]);

        $mac = '';
        $hashingStream = new HashingStream(
            $macCalculationStream,
            $this->macKey,
            static function(string $hash) use (&$mac): void {
                $mac = substr($hash, 0, self::MAC_SIZE);
            },
            'sha256'
        );

        $hashingStream->getContents();

        $this->baseStream->rewind();
        $this->finalStream = new AppendStream([
            $this->baseStream,
            Utils::streamFor($mac)
        ]);

        return $this->finalStream;
    }

    public function __toString(): string
    {
        try {
            return $this->buildFinalStream()->__toString();
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function close(): void
    {
        $this->finalStream?->close();
        $this->baseStream->close();
        $this->finalStream = null;
    }

    public function detach()
    {
        $result = $this->finalStream?->detach();
        $this->baseStream->detach();
        $this->finalStream = null;
        return $result;
    }

    public function getSize(): ?int
    {
        $baseSize = $this->baseStream->getSize();
        return $baseSize !== null ? $baseSize + self::MAC_SIZE : null;
    }

    public function tell(): int
    {
        return $this->buildFinalStream()->tell();
    }

    public function eof(): bool
    {
        return $this->buildFinalStream()->eof();
    }

    public function isSeekable(): bool
    {
        return $this->buildFinalStream()->isSeekable();
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->buildFinalStream()->seek($offset, $whence);
    }

    public function rewind(): void
    {
        $this->buildFinalStream()->rewind();
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
        return $this->buildFinalStream()->read($length);
    }

    public function getContents(): string
    {
        return $this->buildFinalStream()->getContents();
    }

    public function getMetadata(?string $key = null)
    {
        return $this->buildFinalStream()->getMetadata($key);
    }
}