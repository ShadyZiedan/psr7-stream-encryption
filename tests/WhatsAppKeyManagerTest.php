<?php

declare(strict_types=1);

namespace WhatsApp\Psr7StreamEncryption\Tests;

use PHPUnit\Framework\TestCase;
use WhatsApp\Psr7StreamEncryption\WhatsAppKeyManager;
use WhatsApp\Psr7StreamEncryption\MediaType;
use InvalidArgumentException;

class WhatsAppKeyManagerTest extends TestCase
{
    private WhatsAppKeyManager $keyManager;
    private string $testMediaKey;

    protected function setUp(): void
    {
        $this->keyManager = new WhatsAppKeyManager();
        $this->testMediaKey = str_repeat('A', 32);
    }

    public function testExpandMediaKey(): void
    {
        $expandedKey = $this->keyManager->expandMediaKey($this->testMediaKey, MediaType::IMAGE());
        
        $this->assertEquals(112, strlen($expandedKey));
        $this->assertNotEquals($this->testMediaKey, $expandedKey);
    }

    public function testExpandMediaKeyWithDifferentTypes(): void
    {
        $imageKey = $this->keyManager->expandMediaKey($this->testMediaKey, MediaType::IMAGE());
        $videoKey = $this->keyManager->expandMediaKey($this->testMediaKey, MediaType::VIDEO());
        $audioKey = $this->keyManager->expandMediaKey($this->testMediaKey, MediaType::AUDIO());
        $documentKey = $this->keyManager->expandMediaKey($this->testMediaKey, MediaType::DOCUMENT());


        $this->assertNotEquals($imageKey, $videoKey);
        $this->assertNotEquals($imageKey, $audioKey);
        $this->assertNotEquals($imageKey, $documentKey);
        $this->assertNotEquals($videoKey, $audioKey);
        $this->assertNotEquals($videoKey, $documentKey);
        $this->assertNotEquals($audioKey, $documentKey);
    }

    public function testExpandMediaKeyWithInvalidKeySize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Media key must be exactly 32 bytes');
        
        $this->keyManager->expandMediaKey('short', MediaType::IMAGE());
    }

    public function testSplitExpandedKey(): void
    {
        $expandedKey = $this->keyManager->expandMediaKey($this->testMediaKey, MediaType::IMAGE());
        $keys = $this->keyManager->splitExpandedKey($expandedKey);

        $this->assertArrayHasKey('iv', $keys);
        $this->assertArrayHasKey('cipherKey', $keys);
        $this->assertArrayHasKey('macKey', $keys);
        $this->assertArrayHasKey('refKey', $keys);

        $this->assertEquals(16, strlen($keys['iv']));
        $this->assertEquals(32, strlen($keys['cipherKey']));
        $this->assertEquals(32, strlen($keys['macKey']));
        $this->assertEquals(32, strlen($keys['refKey']));
    }

    public function testSplitExpandedKeyWithInvalidSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expanded key must be exactly 112 bytes');
        
        $this->keyManager->splitExpandedKey('short');
    }

    public function testGetMediaTypeInfo(): void
    {
        $this->assertEquals('WhatsApp Image Keys', $this->keyManager->getMediaTypeInfo(MediaType::IMAGE()));
        $this->assertEquals('WhatsApp Video Keys', $this->keyManager->getMediaTypeInfo(MediaType::VIDEO()));
        $this->assertEquals('WhatsApp Audio Keys', $this->keyManager->getMediaTypeInfo(MediaType::AUDIO()));
        $this->assertEquals('WhatsApp Document Keys', $this->keyManager->getMediaTypeInfo(MediaType::DOCUMENT()));
    }

    public function testIsMediaTypeSupported(): void
    {
        $this->assertTrue($this->keyManager->isMediaTypeSupported(MediaType::IMAGE()));
        $this->assertTrue($this->keyManager->isMediaTypeSupported(MediaType::VIDEO()));
        $this->assertTrue($this->keyManager->isMediaTypeSupported(MediaType::AUDIO()));
        $this->assertTrue($this->keyManager->isMediaTypeSupported(MediaType::DOCUMENT()));
    }

    public function testGetSupportedMediaTypes(): void
    {
        $supportedTypes = $this->keyManager->getSupportedMediaTypes();
        
        $this->assertContains('IMAGE', $supportedTypes);
        $this->assertContains('VIDEO', $supportedTypes);
        $this->assertContains('AUDIO', $supportedTypes);
        $this->assertContains('DOCUMENT', $supportedTypes);
        $this->assertCount(4, $supportedTypes);
    }
}


