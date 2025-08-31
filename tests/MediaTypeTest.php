<?php

declare(strict_types=1);

namespace WhatsApp\Psr7StreamEncryption\Tests;

use PHPUnit\Framework\TestCase;
use WhatsApp\Psr7StreamEncryption\MediaType;
use InvalidArgumentException;

class MediaTypeTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals('IMAGE', MediaType::IMAGE);
        $this->assertEquals('VIDEO', MediaType::VIDEO);
        $this->assertEquals('AUDIO', MediaType::AUDIO);
        $this->assertEquals('DOCUMENT', MediaType::DOCUMENT);
    }

    public function testGetInfo(): void
    {
        $this->assertEquals('WhatsApp Image Keys', MediaType::IMAGE()->getInfo());
        $this->assertEquals('WhatsApp Video Keys', MediaType::VIDEO()->getInfo());
        $this->assertEquals('WhatsApp Audio Keys', MediaType::AUDIO()->getInfo());
        $this->assertEquals('WhatsApp Document Keys', MediaType::DOCUMENT()->getInfo());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('Image', MediaType::IMAGE()->getLabel());
        $this->assertEquals('Video', MediaType::VIDEO()->getLabel());
        $this->assertEquals('Audio', MediaType::AUDIO()->getLabel());
        $this->assertEquals('Document', MediaType::DOCUMENT()->getLabel());
    }

    public function testGetAll(): void
    {
        $allTypes = MediaType::getAll();
        
        $this->assertCount(4, $allTypes);
        $this->assertInstanceOf(MediaType::class, $allTypes[0]);
        $this->assertInstanceOf(MediaType::class, $allTypes[1]);
        $this->assertInstanceOf(MediaType::class, $allTypes[2]);
        $this->assertInstanceOf(MediaType::class, $allTypes[3]);
        
        $this->assertTrue($allTypes[0]->equals(MediaType::IMAGE()));
        $this->assertTrue($allTypes[1]->equals(MediaType::VIDEO()));
        $this->assertTrue($allTypes[2]->equals(MediaType::AUDIO()));
        $this->assertTrue($allTypes[3]->equals(MediaType::DOCUMENT()));
    }

    public function testGetAllValues(): void
    {
        $allValues = MediaType::getAllValues();
        
        $this->assertCount(4, $allValues);
        $this->assertContains('IMAGE', $allValues);
        $this->assertContains('VIDEO', $allValues);
        $this->assertContains('AUDIO', $allValues);
        $this->assertContains('DOCUMENT', $allValues);
    }

    public function testFromString(): void
    {
        $this->assertTrue(MediaType::fromString('IMAGE')->equals(MediaType::IMAGE()));
        $this->assertTrue(MediaType::fromString('VIDEO')->equals(MediaType::VIDEO()));
        $this->assertTrue(MediaType::fromString('AUDIO')->equals(MediaType::AUDIO()));
        $this->assertTrue(MediaType::fromString('DOCUMENT')->equals(MediaType::DOCUMENT()));
    }

    public function testFromStringCaseInsensitive(): void
    {
        $this->assertTrue(MediaType::fromString('image')->equals(MediaType::IMAGE()));
        $this->assertTrue(MediaType::fromString('Video')->equals(MediaType::VIDEO()));
        $this->assertTrue(MediaType::fromString('AUDIO')->equals(MediaType::AUDIO()));
        $this->assertTrue(MediaType::fromString('document')->equals(MediaType::DOCUMENT()));
    }

    public function testFromStringWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid media type: INVALID');
        
        MediaType::fromString('INVALID');
    }

    public function testIsValid(): void
    {
        $this->assertTrue(MediaType::isValid('IMAGE'));
        $this->assertTrue(MediaType::isValid('VIDEO'));
        $this->assertTrue(MediaType::isValid('AUDIO'));
        $this->assertTrue(MediaType::isValid('DOCUMENT'));
        
        $this->assertTrue(MediaType::isValid('image'));
        $this->assertTrue(MediaType::isValid('Video'));
        $this->assertTrue(MediaType::isValid('document'));
        
        $this->assertFalse(MediaType::isValid('INVALID'));
        $this->assertFalse(MediaType::isValid(''));
        $this->assertFalse(MediaType::isValid('random'));
    }

    public function testObjectComparison(): void
    {
        $image1 = MediaType::IMAGE();
        $image2 = MediaType::IMAGE();
        $video = MediaType::VIDEO();
        
        $this->assertTrue($image1->equals($image2));
        $this->assertFalse($image1->equals($video));
    }

    public function testObjectInArray(): void
    {
        $types = [MediaType::IMAGE(), MediaType::VIDEO()];
        

        $this->assertCount(2, $types);
        $this->assertTrue($types[0]->equals(MediaType::IMAGE()));
        $this->assertTrue($types[1]->equals(MediaType::VIDEO()));
        

        $audioFound = false;
        foreach ($types as $type) {
            if ($type->equals(MediaType::AUDIO())) {
                $audioFound = true;
                break;
            }
        }
        $this->assertFalse($audioFound);
    }

    public function testObjectSwitch(): void
    {
        $result = match(MediaType::IMAGE()->getValue()) {
            MediaType::IMAGE => 'image',
            MediaType::VIDEO => 'video',
            MediaType::AUDIO => 'audio',
            MediaType::DOCUMENT => 'document',
            default => 'unknown'
        };
        
        $this->assertEquals('image', $result);
    }

    public function testToString(): void
    {
        $this->assertEquals('IMAGE', (string) MediaType::IMAGE());
        $this->assertEquals('VIDEO', (string) MediaType::VIDEO());
        $this->assertEquals('AUDIO', (string) MediaType::AUDIO());
        $this->assertEquals('DOCUMENT', (string) MediaType::DOCUMENT());
    }

    public function testGetValue(): void
    {
        $this->assertEquals('IMAGE', MediaType::IMAGE()->getValue());
        $this->assertEquals('VIDEO', MediaType::VIDEO()->getValue());
        $this->assertEquals('AUDIO', MediaType::AUDIO()->getValue());
        $this->assertEquals('DOCUMENT', MediaType::DOCUMENT()->getValue());
    }
}
