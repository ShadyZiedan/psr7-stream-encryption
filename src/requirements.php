<?php

if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    throw new RuntimeException('PHP 8.0.0 or higher is required. Current version: ' . PHP_VERSION);
}

$requiredExtensions = [
    'openssl' => 'OpenSSL extension is required for encryption/decryption',
    'hash' => 'Hash extension is required for HMAC and HKDF',
    'mbstring' => 'Mbstring extension is required for string operations'
];

foreach ($requiredExtensions as $extension => $message) {
    if (!extension_loaded($extension)) {
        throw new RuntimeException($message);
    }
}

$requiredCiphers = ['aes-256-cbc'];
foreach ($requiredCiphers as $cipher) {
    if (!in_array($cipher, openssl_get_cipher_methods())) {
        throw new RuntimeException("Cipher {$cipher} is not available");
    }
}

$requiredHashes = ['sha256'];
foreach ($requiredHashes as $hash) {
    if (!in_array($hash, hash_algos())) {
        throw new RuntimeException("Hash algorithm {$hash} is not available");
    }
}

echo "✓ All requirements are met\n";
echo "✓ PHP version: " . PHP_VERSION . "\n";
echo "✓ OpenSSL version: " . OPENSSL_VERSION_TEXT . "\n";
echo "✓ Available ciphers: " . count(openssl_get_cipher_methods()) . "\n";
echo "✓ Available hash algorithms: " . count(hash_algos()) . "\n";
