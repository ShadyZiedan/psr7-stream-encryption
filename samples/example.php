<?php

require_once __DIR__ . '/../vendor/autoload.php';

use WhatsApp\Psr7StreamEncryption\WhatsAppStreamEncryption;
use WhatsApp\Psr7StreamEncryption\MediaType;
use GuzzleHttp\Psr7\Utils;

echo "WhatsApp PSR-7 Stream Encryption Example\n";
echo "========================================\n\n";

try {
    $encryption = new WhatsAppStreamEncryption();
    
    $mediaKey = $encryption->generateMediaKey();
    echo "Generated media key: " . bin2hex($mediaKey) . "\n\n";
    
    $testData = "Hello, WhatsApp! This is a test message for encryption.\n";
    echo "Original data: " . $testData;
    
    $originalStream = Utils::streamFor($testData);
    
    echo "Encrypting data as IMAGE...\n";
    $encryptedStream = $encryption->encrypt($originalStream, $mediaKey, MediaType::IMAGE());
    $encryptedData = $encryptedStream->getContents();
    
    echo "Encrypted data size: " . strlen($encryptedData) . " bytes\n";
    echo "Encrypted data (hex): " . bin2hex(substr($encryptedData, 0, 32)) . "...\n\n";
    
    echo "Decrypting data...\n";
    $encryptedStreamForDecrypt = Utils::streamFor($encryptedData);
    $decryptedStream = $encryption->decrypt($encryptedStreamForDecrypt, $mediaKey, MediaType::IMAGE());
    $decryptedData = $decryptedStream->getContents();
    
    echo "Decrypted data: " . $decryptedData;
    
    if ($testData === $decryptedData) {
        echo "✓ Encryption/decryption successful!\n\n";
    } else {
        echo "✗ Encryption/decryption failed!\n\n";
    }
    
    echo "Testing different media types:\n";
    $mediaTypes = [MediaType::IMAGE(), MediaType::VIDEO(), MediaType::AUDIO(), MediaType::DOCUMENT()];
    
    foreach ($mediaTypes as $mediaType) {
        $stream = Utils::streamFor($testData);
        $encrypted = $encryption->encrypt($stream, $mediaKey, $mediaType);
        $encryptedContent = $encrypted->getContents();
        
        echo "  " . $mediaType->getLabel() . ": " . strlen($encryptedContent) . " bytes\n";
    }
    echo "\n";
    
    echo "Generating sidecar for streaming...\n";
    $largeData = str_repeat("Large data for sidecar generation! ", 1000);
    $largeStream = Utils::streamFor($largeData);
    
    $sidecarGenerator = $encryption->createSidecarGenerator($largeStream, $mediaKey, MediaType::VIDEO());
    $sidecar = $sidecarGenerator->generate();
    
    echo "Sidecar size: " . strlen($sidecar) . " bytes\n";
    echo "Expected chunks: " . ceil(strlen($largeData) / 65536) . "\n";
    echo "Sidecar chunks: " . (strlen($sidecar) / 10) . "\n\n";
    
    echo "Testing file operations...\n";
    
    $tempFile = tempnam(sys_get_temp_dir(), 'whatsapp_test_');
    file_put_contents($tempFile, $testData);
    
    $encryptedFileStream = $encryption->encryptFile($tempFile, $mediaKey, MediaType::IMAGE());
    $encryptedFileData = $encryptedFileStream->getContents();
    
    $encryptedFilePath = $tempFile . '.encrypted';
    file_put_contents($encryptedFilePath, $encryptedFileData);
    
    echo "  Original file: " . $tempFile . " (" . filesize($tempFile) . " bytes)\n";
    echo "  Encrypted file: " . $encryptedFilePath . " (" . filesize($encryptedFilePath) . " bytes)\n";
    
    $decryptedFileStream = $encryption->decryptFile($encryptedFilePath, $mediaKey, MediaType::IMAGE());
    $decryptedFileData = $decryptedFileStream->getContents();
    
    $decryptedFilePath = $tempFile . '.decrypted';
    file_put_contents($decryptedFilePath, $decryptedFileData);
    
    echo "  Decrypted file: " . $decryptedFilePath . " (" . filesize($decryptedFilePath) . " bytes)\n";
    
    if ($testData === $decryptedFileData) {
        echo "  ✓ File encryption/decryption successful!\n\n";
    } else {
        echo "  ✗ File encryption/decryption failed!\n\n";
    }
    
    echo "MediaType enum features:\n";
    echo "  All types: " . implode(', ', array_map(fn($type) => $type->getLabel(), MediaType::getAll())) . "\n";
    echo "  All values: " . implode(', ', MediaType::getAllValues()) . "\n";
    echo "  IMAGE info: " . MediaType::IMAGE()->getInfo() . "\n";
    echo "  VIDEO label: " . MediaType::VIDEO()->getLabel() . "\n";
    echo "  Is 'image' valid? " . (MediaType::isValid('image') ? 'Yes' : 'No') . "\n";
    echo "  From string 'video': " . MediaType::fromString('video')->getValue() . "\n\n";
    
    unlink($tempFile);
    unlink($encryptedFilePath);
    unlink($decryptedFilePath);
    
    echo "All tests completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}


