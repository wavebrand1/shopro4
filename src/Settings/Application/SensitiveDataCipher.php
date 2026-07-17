<?php

declare(strict_types=1);

namespace App\Settings\Application;

final class SensitiveDataCipher
{
    private string $key;
    public function __construct(string $kernelSecret) { $this->key = hash('sha256', $kernelSecret, true); }
    public function encrypt(string $plain): string
    {
        $iv = random_bytes(12); $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) throw new \RuntimeException('Nie udało się zaszyfrować danych SMTP.');
        return 'enc:'.base64_encode($iv.$tag.$cipher);
    }
    public function decrypt(?string $stored): string
    {
        if (!$stored) return '';
        if (!str_starts_with($stored, 'enc:')) return $stored; // zgodność z wartością zapisaną przed wprowadzeniem szyfrowania
        $payload = base64_decode(substr($stored, 4), true);
        if ($payload === false || strlen($payload) < 28) throw new \RuntimeException('Nie można odczytać zaszyfrowanego hasła SMTP.');
        $plain = openssl_decrypt(substr($payload, 28), 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, substr($payload, 0, 12), substr($payload, 12, 16));
        if ($plain === false) throw new \RuntimeException('Nie można odszyfrować hasła SMTP.');
        return $plain;
    }
}
