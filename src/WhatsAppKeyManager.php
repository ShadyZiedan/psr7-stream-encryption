<?php

declare(strict_types=1);

namespace WhatsApp\Psr7StreamEncryption;

use InvalidArgumentException;

class WhatsAppKeyManager
{
    private const EXPANDED_KEY_SIZE = 112;
    private const IV_SIZE = 16;
    private const CIPHER_KEY_SIZE = 32;
    private const MAC_KEY_SIZE = 32;
    private const REF_KEY_SIZE = 32;

    public function expandMediaKey(string $mediaKey, MediaType $mediaType): string
    {
        if (strlen($mediaKey) !== 32) {
            throw new InvalidArgumentException('Media key must be exactly 32 bytes');
        }

        $info = $mediaType->getInfo();
        
        return hash_hkdf('sha256', $mediaKey, self::EXPANDED_KEY_SIZE, $info);
    }

    public function splitExpandedKey(string $expandedKey): array
    {
        if (strlen($expandedKey) !== self::EXPANDED_KEY_SIZE) {
            throw new InvalidArgumentException('Expanded key must be exactly 112 bytes');
        }

        return [
            'iv' => substr($expandedKey, 0, self::IV_SIZE),
            'cipherKey' => substr($expandedKey, self::IV_SIZE, self::CIPHER_KEY_SIZE),
            'macKey' => substr($expandedKey, self::IV_SIZE + self::CIPHER_KEY_SIZE, self::MAC_KEY_SIZE),
            'refKey' => substr($expandedKey, self::IV_SIZE + self::CIPHER_KEY_SIZE + self::MAC_KEY_SIZE, self::REF_KEY_SIZE),
        ];
    }

    public function getMediaTypeInfo(MediaType $mediaType): string
    {
        return $mediaType->getInfo();
    }

    public function isMediaTypeSupported(MediaType $mediaType): bool
    {
        return true;
    }

    public function getSupportedMediaTypes(): array
    {
        return MediaType::getAllValues();
    }
}


