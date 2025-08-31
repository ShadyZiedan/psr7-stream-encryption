<?php

echo "Testing basic PHP functionality...\n";

echo "PHP version: " . PHP_VERSION . "\n";
echo "OpenSSL available: " . (extension_loaded('openssl') ? 'Yes' : 'No') . "\n";
echo "Hash available: " . (extension_loaded('hash') ? 'Yes' : 'No') . "\n";
echo "Mbstring available: " . (extension_loaded('mbstring') ? 'Yes' : 'No') . "\n";

$ciphers = openssl_get_cipher_methods();
echo "Available ciphers: " . count($ciphers) . "\n";
echo "AES-256-CBC available: " . (in_array('aes-256-cbc', $ciphers) ? 'Yes' : 'No') . "\n";

$hashes = hash_algos();
$hashes = hash_algos();
echo "Available hash algorithms: " . count($hashes) . "\n";
echo "SHA256 available: " . (in_array('sha256', $hashes) ? 'Yes' : 'No') . "\n";

echo "Basic test completed!\n";


