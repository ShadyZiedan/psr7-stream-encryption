<?php

declare(strict_types=1);

namespace WhatsApp\Psr7StreamEncryption;

final class MediaType
{
    public const IMAGE = 'IMAGE';
    public const VIDEO = 'VIDEO';
    public const AUDIO = 'AUDIO';
    public const DOCUMENT = 'DOCUMENT';

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function create(string $value): self
    {
        $upperValue = strtoupper($value);
        
        if (!self::isValid($upperValue)) {
            throw new \InvalidArgumentException("Invalid media type: {$value}");
        }
        
        return new self($upperValue);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getInfo(): string
    {
        return match($this->value) {
            self::IMAGE => 'WhatsApp Image Keys',
            self::VIDEO => 'WhatsApp Video Keys',
            self::AUDIO => 'WhatsApp Audio Keys',
            self::DOCUMENT => 'WhatsApp Document Keys',
            default => throw new \RuntimeException("Unknown media type: {$this->value}")
        };
    }

    public function getLabel(): string
    {
        return match($this->value) {
            self::IMAGE => 'Image',
            self::VIDEO => 'Video',
            self::AUDIO => 'Audio',
            self::DOCUMENT => 'Document',
            default => throw new \RuntimeException("Unknown media type: {$this->value}")
        };
    }

    public static function getAll(): array
    {
        return [
            self::create(self::IMAGE),
            self::create(self::VIDEO),
            self::create(self::AUDIO),
            self::create(self::DOCUMENT)
        ];
    }

    public static function getAllValues(): array
    {
        return [self::IMAGE, self::VIDEO, self::AUDIO, self::DOCUMENT];
    }

    public static function fromString(string $value): self
    {
        return self::create($value);
    }

    public static function isValid(string $value): bool
    {
        $upperValue = strtoupper($value);
        return in_array($upperValue, self::getAllValues(), true);
    }

    public static function IMAGE(): self
    {
        return new self(self::IMAGE);
    }

    public static function VIDEO(): self
    {
        return new self(self::VIDEO);
    }

    public static function AUDIO(): self
    {
        return new self(self::AUDIO);
    }

    public static function DOCUMENT(): self
    {
        return new self(self::DOCUMENT);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function __serialize(): array
    {
        return ['value' => $this->value];
    }

    public function __unserialize(array $data): void
    {
        $this->value = $data['value'];
    }
}
