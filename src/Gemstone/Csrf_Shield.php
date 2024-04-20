<?php

namespace Xel\Async\Gemstone;
use Xel\Async\Contract\CsrfShieldedInterface;

class Csrf_Shield implements CsrfShieldedInterface
{
    private const LENGTH_RANDOM_STRING = 16;
    private const CHAR_IMPLEMENT_DATA = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public string $csrfTokenRandom;
    public function generateCSRFToken($key): string
    {
        // ?  generate random token
        $data = $this->maker();
        $this->csrfTokenRandom = $data;
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $data = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $token = [bin2hex($iv), bin2hex($data)];
        return implode(",", $token);
    }

    public function validateToken($csrfToken, $key): bool
    {
        $data = explode(',', $csrfToken);
        $token = hex2bin($data[1]);
        $iv = hex2bin($data[0]);

        $data = openssl_decrypt($token, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        if (!hash_equals($data, $this->csrfTokenRandom)) {
            return false;
        }
        return true;
    }

    private function maker(): string
    {
        $randomString = '';
        for ($i = 0; $i < self::LENGTH_RANDOM_STRING; $i++) {
            $index = rand(0, strlen(self::CHAR_IMPLEMENT_DATA) - 1);
            $randomString .= self::CHAR_IMPLEMENT_DATA[$index];
        }
        return hash('sha256',$randomString);
    }
}