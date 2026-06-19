<?php

namespace Laraditz\Courier\SfExpress\Http;

class SfExpressEncryptor
{
    public function __construct(
        private readonly string $encodingAesKey,
        private readonly string $appKey,
    ) {}

    public function encrypt(string $plaintext, string $accessToken, string $timestamp, string $nonce): array
    {
        $random = random_bytes(16);
        $padded = $this->pkcs7Pad(
            $random . pack('N', strlen($plaintext)) . $plaintext . $this->appKey
        );

        $aesKey = base64_decode($this->encodingAesKey . '=');
        $iv     = substr($aesKey, 0, 16);

        $raw = openssl_encrypt($padded, 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
        if ($raw === false) {
            throw new \RuntimeException('SF Express AES encryption failed.');
        }

        $encrypt = base64_encode($raw);
        $arr     = [$encrypt, $accessToken, $timestamp, $nonce];
        sort($arr, SORT_STRING);

        return ['encrypt' => $encrypt, 'signature' => hash('sha256', implode('', $arr))];
    }

    public function decrypt(string $ciphertext): string
    {
        $aesKey = base64_decode($this->encodingAesKey . '=');
        $iv     = substr($aesKey, 0, 16);

        $decrypted = openssl_decrypt(
            base64_decode($ciphertext),
            'AES-256-CBC',
            $aesKey,
            OPENSSL_RAW_DATA | OPENSSL_NO_PADDING,
            $iv
        );

        if ($decrypted === false) {
            throw new \RuntimeException('SF Express AES decryption failed.');
        }

        $msgLen = unpack('N', substr($decrypted, 16, 4))[1];

        return substr($decrypted, 20, $msgLen);
    }

    private function pkcs7Pad(string $data): string
    {
        $blockSize = 32;
        $pad       = $blockSize - (strlen($data) % $blockSize);

        return $data . str_repeat(chr($pad), $pad);
    }
}
