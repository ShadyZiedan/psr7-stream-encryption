<?php

declare(strict_types=1);

namespace WhatsApp\Psr7StreamEncryption\Tests;

use PHPUnit\Framework\TestCase;
use WhatsApp\Psr7StreamEncryption\WhatsAppSidecarGenerator;
use WhatsApp\Psr7StreamEncryption\WhatsAppStreamEncryption;
use WhatsApp\Psr7StreamEncryption\MediaType;
use GuzzleHttp\Psr7\Utils;

class WhatsAppSidecarGeneratorTest extends TestCase
{
    private WhatsAppStreamEncryption $encryption;
    private string $testMediaKey;
    private string $testData;

    protected function setUp(): void
    {
        $this->encryption = new WhatsAppStreamEncryption();
        $this->testMediaKey = str_repeat('A', 32);
        $this->testData = str_repeat('Test data for sidecar generation! ', 1000);
    }

    public function testSidecarGeneration(): void
    {
        $stream = Utils::streamFor($this->testData);
        $generator = $this->encryption->createSidecarGenerator($stream, $this->testMediaKey, MediaType::VIDEO());
        
        $sidecar = $generator->generate();
        
        $this->assertNotEmpty($sidecar);
        $this->assertEquals(0, strlen($sidecar) % 10);
        
        $expectedChunks = ceil(strlen($this->testData) / 65536);
        $expectedSize = $expectedChunks * 10;
        $this->assertEquals($expectedSize, strlen($sidecar));
    }

    public function testSidecarGenerationForChunk(): void
    {
        $stream = Utils::streamFor($this->testData);
        $generator = $this->encryption->createSidecarGenerator($stream, $this->testMediaKey, MediaType::VIDEO());
        
        $chunkSidecar = $generator->generateForChunk(0);
        
        $this->assertNotEmpty($chunkSidecar);
        $this->assertEquals(10, strlen($chunkSidecar));
    }

    public function testSidecarGenerationWithDifferentMediaTypes(): void
    {
        $stream = Utils::streamFor($this->testData);
        
        $mediaTypes = [MediaType::VIDEO(), MediaType::AUDIO()];
        $sidecarResults = [];
        
        foreach ($mediaTypes as $mediaType) {
            $generator = $this->encryption->createSidecarGenerator($stream, $this->testMediaKey, $mediaType);
            $sidecarResults[$mediaType->getValue()] = $generator->generate();
            
            $this->assertNotEmpty($sidecarResults[$mediaType->getValue()]);
        }
        
        $this->assertNotEquals($sidecarResults['VIDEO'], $sidecarResults['AUDIO']);
    }

    public function testSidecarGenerationWithEmptyData(): void
    {
        $emptyStream = Utils::streamFor('');
        $generator = $this->encryption->createSidecarGenerator($emptyStream, $this->testMediaKey, MediaType::VIDEO());
        
        $sidecar = $generator->generate();
        
        $this->assertEquals('', $sidecar);
    }

    public function testSidecarGenerationWithSmallData(): void
    {
        $smallData = 'Small data';
        $stream = Utils::streamFor($smallData);
        $generator = $this->encryption->createSidecarGenerator($stream, $this->testMediaKey, MediaType::VIDEO());
        
        $sidecar = $generator->generate();
        
        $this->assertEquals(10, strlen($sidecar));
    }

    public function testSidecarGenerationWithLargeData(): void
    {
        $largeData = str_repeat('Large data test! ', 10000);
        $stream = Utils::streamFor($largeData);
        $generator = $this->encryption->createSidecarGenerator($stream, $this->testMediaKey, MediaType::VIDEO());
        
        $sidecar = $generator->generate();
        
        $expectedChunks = ceil(strlen($largeData) / 65536);
        $expectedSize = $expectedChunks * 10;
        $this->assertEquals($expectedSize, strlen($sidecar));
    }

    public function testSidecarGenerationForSpecificChunk(): void
    {
        $stream = Utils::streamFor($this->testData);
        $generator = $this->encryption->createSidecarGenerator($stream, $this->testMediaKey, MediaType::VIDEO());
        
        $chunkSidecar = $generator->generateForChunk(0);
        
        $this->assertNotEmpty($chunkSidecar);
        $this->assertEquals(10, strlen($chunkSidecar));
    }

    public function testSidecarGeneratorProperties(): void
    {
        $stream = Utils::streamFor($this->testData);
        $generator = $this->encryption->createSidecarGenerator($stream, $this->testMediaKey, MediaType::VIDEO());
        
        $this->assertTrue($generator->getMediaType()->equals(MediaType::VIDEO()));
        $this->assertEquals($this->testMediaKey, $generator->getMediaKey());
    }

    public function testGetChunkCount(): void
    {
        $stream = Utils::streamFor($this->testData);
        $generator = $this->encryption->createSidecarGenerator($stream, $this->testMediaKey, MediaType::VIDEO());
        
        $chunkCount = $generator->getChunkCount();
        $expectedChunks = ceil(strlen($this->testData) / 65536);
        
        $this->assertEquals($expectedChunks, $chunkCount);
    }
}
