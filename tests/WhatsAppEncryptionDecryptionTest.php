<?php

declare(strict_types=1);

namespace WhatsApp\Psr7StreamEncryption\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use WhatsApp\Psr7StreamEncryption\WhatsAppStreamEncryption;
use WhatsApp\Psr7StreamEncryption\MediaType;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;

class WhatsAppEncryptionDecryptionTest extends TestCase
{
    private WhatsAppStreamEncryption $encryption;
    private string $testMediaKey;
    private string $testData;

    protected function setUp(): void
    {
        $this->encryption = new WhatsAppStreamEncryption();
        $this->testMediaKey = str_repeat('A', 32);
        $this->testData = 'Hello, WhatsApp encryption! This is a test message for encryption and decryption.';
    }

    public function testEncryptionAndDecryption(): void
    {
        $originalStream = Utils::streamFor($this->testData);
        
        $encryptedStream = $this->encryption->encrypt($originalStream, $this->testMediaKey, MediaType::IMAGE());
        $encryptedData = $encryptedStream->getContents();
        
        $this->assertNotEquals($this->testData, $encryptedData);
        $this->assertGreaterThan(strlen($this->testData), strlen($encryptedData));
        
        $encryptedStreamForDecrypt = Utils::streamFor($encryptedData);
        $decryptedStream = $this->encryption->decrypt($encryptedStreamForDecrypt, $this->testMediaKey, MediaType::IMAGE());
        $decryptedData = $decryptedStream->getContents();
        
        $this->assertEquals($this->testData, $decryptedData);
    }

    public function testEncryptionWithDifferentMediaTypes(): void
    {
        $originalStream = Utils::streamFor($this->testData);
        
        $mediaTypes = [MediaType::IMAGE(), MediaType::VIDEO(), MediaType::AUDIO(), MediaType::DOCUMENT()];
        $encryptedResults = [];
        
        foreach ($mediaTypes as $mediaType) {
            $encryptedStream = $this->encryption->encrypt($originalStream, $this->testMediaKey, $mediaType);
            $encryptedResults[$mediaType->getValue()] = $encryptedStream->getContents();
            
            $this->assertNotEquals($this->testData, $encryptedResults[$mediaType->getValue()]);
        }
        
        $this->assertNotEquals($encryptedResults['IMAGE'], $encryptedResults['VIDEO']);
        $this->assertNotEquals($encryptedResults['IMAGE'], $encryptedResults['AUDIO']);
        $this->assertNotEquals($encryptedResults['IMAGE'], $encryptedResults['DOCUMENT']);
    }

    public function testDecryptionWithWrongKey(): void
    {
        $originalStream = Utils::streamFor($this->testData);
        
        $encryptedStream = $this->encryption->encrypt($originalStream, $this->testMediaKey, MediaType::IMAGE());
        $encryptedData = $encryptedStream->getContents();
        
        $wrongKey = str_repeat('B', 32);
        $encryptedStreamForDecrypt = Utils::streamFor($encryptedData);
        
        $this->expectException(\RuntimeException::class);
        $decryptedStream = $this->encryption->decrypt($encryptedStreamForDecrypt, $wrongKey, MediaType::IMAGE());
        $decryptedStream->getContents();
    }

    public function testDecryptionWithWrongMediaType(): void
    {
        $originalStream = Utils::streamFor($this->testData);
        
        $encryptedStream = $this->encryption->encrypt($originalStream, $this->testMediaKey, MediaType::IMAGE());
        $encryptedData = $encryptedStream->getContents();
        
        $encryptedStreamForDecrypt = Utils::streamFor($encryptedData);
        
        $this->expectException(\RuntimeException::class);
        $decryptedStream = $this->encryption->decrypt($encryptedStreamForDecrypt, $this->testMediaKey, MediaType::VIDEO());
        $decryptedStream->getContents();
    }

    public function testFileOperations(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'whatsapp_test_');
        file_put_contents($tempFile, $this->testData);
        
        try {
            $encryptedFileStream = $this->encryption->encryptFile($tempFile, $this->testMediaKey, MediaType::IMAGE());
            $encryptedFileData = $encryptedFileStream->getContents();
            
            $this->assertNotEquals($this->testData, $encryptedFileData);
            $this->assertGreaterThan(strlen($this->testData), strlen($encryptedFileData));
            
            $encryptedFilePath = $tempFile . '.encrypted';
            file_put_contents($encryptedFilePath, $encryptedFileData);
            
            $decryptedFileStream = $this->encryption->decryptFile($encryptedFilePath, $this->testMediaKey, MediaType::IMAGE());
            $decryptedFileData = $decryptedFileStream->getContents();
            
            $this->assertEquals($this->testData, $decryptedFileData);
            
            unlink($encryptedFilePath);
        } finally {
            unlink($tempFile);
        }
    }

    public function testSidecarGeneration(): void
    {
        $largeData = str_repeat("Large data for sidecar generation! ", 1000);
        $largeStream = Utils::streamFor($largeData);
        
        $sidecarGenerator = $this->encryption->createSidecarGenerator($largeStream, $this->testMediaKey, MediaType::VIDEO());
        $sidecar = $sidecarGenerator->generate();
        
        $expectedChunks = ceil(strlen($largeData) / 65536);
        $expectedSize = $expectedChunks * 10;
        
        $this->assertEquals($expectedSize, strlen($sidecar));
    }

    public function testMediaTypeSupport(): void
    {
        $this->assertTrue($this->encryption->isMediaTypeSupported(MediaType::IMAGE()));
        $this->assertTrue($this->encryption->isMediaTypeSupported(MediaType::VIDEO()));
        $this->assertTrue($this->encryption->isMediaTypeSupported(MediaType::AUDIO()));
        $this->assertTrue($this->encryption->isMediaTypeSupported(MediaType::DOCUMENT()));
    }

    public function testSupportedMediaTypes(): void
    {
        $supportedTypes = $this->encryption->getSupportedMediaTypes();
        
        $this->assertContains('IMAGE', $supportedTypes);
        $this->assertContains('VIDEO', $supportedTypes);
        $this->assertContains('AUDIO', $supportedTypes);
        $this->assertContains('DOCUMENT', $supportedTypes);
        $this->assertCount(4, $supportedTypes);
    }
}


