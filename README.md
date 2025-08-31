# WhatsApp PSR-7 Stream Encryption

Декораторы для PSR-7 потоков с шифрованием по алгоритмам WhatsApp.

**Требования:** PHP >= 8.0

## Установка

```bash
composer require whatsapp/psr7-stream-encryption
```

## Использование

### Шифрование

```php
use WhatsApp\Psr7StreamEncryption\WhatsAppStreamEncryption;
use WhatsApp\Psr7StreamEncryption\MediaType;
use GuzzleHttp\Psr7\Utils;

$encryption = new WhatsAppStreamEncryption();
$stream = Utils::streamFor(fopen('file.jpg', 'r'));
$mediaKey = random_bytes(32);

$encryptedStream = $encryption->encrypt($stream, $mediaKey, MediaType::IMAGE());
```

### Дешифрование

```php
$decryptedStream = $encryption->decrypt($encryptedStream, $mediaKey, MediaType::IMAGE());
```

### Sidecar для стриминга

```php
use WhatsApp\Psr7StreamEncryption\WhatsAppSidecarGenerator;

$sidecarGenerator = new WhatsAppSidecarGenerator($stream, $mediaKey, MediaType::VIDEO());
$sidecar = $sidecarGenerator->generate();
```

## Типы медиа

- `IMAGE` - изображения
- `VIDEO` - видео  
- `AUDIO` - аудио
- `DOCUMENT` - документы

## Алгоритм

1. Расширение `mediaKey` до 112 байт (HKDF-SHA256)
2. Разделение на компоненты: `iv`, `cipherKey`, `macKey`, `refKey`
3. Шифрование AES-CBC с `cipherKey` и `iv`
4. Подпись HMAC-SHA256 с `macKey`

## Тестирование

```bash
composer test
```

## Лицензия

MIT
