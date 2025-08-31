<?php

declare(strict_types=1);

namespace WhatsApp\Psr7StreamEncryption;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Utils;

class WhatsAppStreamEncryption
{
    private WhatsAppKeyManager $keyManager;

    public function __construct()
    {
        $this->keyManager = new WhatsAppKeyManager();
    }

    public function encrypt(StreamInterface $stream, string $mediaKey, MediaType $mediaType): StreamInterface
    {
        $keys = $this->expandKey($mediaKey, $mediaType);
        
        $cipherMethod = new \Jsq\EncryptionStreams\Cbc($keys['iv'], 256);
        $encryptedStream = new \Jsq\EncryptionStreams\AesEncryptingStream($stream, $keys['cipherKey'], $cipherMethod);
        
        return new WhatsAppMacWrapper($encryptedStream, $keys['iv'], $keys['macKey']);
    }

    public function decrypt(StreamInterface $stream, string $mediaKey, MediaType $mediaType): StreamInterface
    {
        $keys = $this->expandKey($mediaKey, $mediaType);
        
        $macValidator = new WhatsAppMacValidator($stream, $keys['iv'], $keys['macKey']);
        
        $cipherMethod = new \Jsq\EncryptionStreams\Cbc($keys['iv'], 256);
        return new \Jsq\EncryptionStreams\AesDecryptingStream($macValidator, $keys['cipherKey'], $cipherMethod);
    }

    private function expandKey(string $mediaKey, MediaType $mediaType): array
    {
        $keyManager = new WhatsAppKeyManager();
        $expandedKey = $keyManager->expandMediaKey($mediaKey, $mediaType);
        return $keyManager->splitExpandedKey($expandedKey);
    }

    public function createSidecarGenerator(StreamInterface $stream, string $mediaKey, MediaType $mediaType): WhatsAppSidecarGenerator
    {
        return new WhatsAppSidecarGenerator($stream, $mediaKey, $mediaType);
    }

    public function generateMediaKey(): string
    {
        return random_bytes(32);
    }

    public function isMediaTypeSupported(MediaType $mediaType): bool
    {
        return $this->keyManager->isMediaTypeSupported($mediaType);
    }

    public function getSupportedMediaTypes(): array
    {
        return $this->keyManager->getSupportedMediaTypes();
    }

    public function encryptFile(string $filePath, string $mediaKey, MediaType $mediaType): StreamInterface
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $stream = Utils::streamFor(fopen($filePath, 'r'));
        return $this->encrypt($stream, $mediaKey, $mediaType);
    }

    public function decryptFile(string $filePath, string $mediaKey, MediaType $mediaType): StreamInterface
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $stream = Utils::streamFor(fopen($filePath, 'r'));
        return $this->decrypt($stream, $mediaKey, $mediaType);
    }

    public function encryptString(string $data, string $mediaKey, MediaType $mediaType): StreamInterface
    {
        $stream = Utils::streamFor($data);
        return $this->encrypt($stream, $mediaKey, $mediaType);
    }

    public function decryptString(string $data, string $mediaKey, MediaType $mediaType): StreamInterface
    {
        $stream = Utils::streamFor($data);
        return $this->decrypt($stream, $mediaKey, $mediaType);
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


