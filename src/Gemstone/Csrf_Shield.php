<?php

namespace Xel\Async\Gemstone;
use Xel\Async\SessionManager\SwooleSession;

class Csrf_Shield 
{
    private const LENGTH_RANDOM_STRING = 16;
    private const CHAR_IMPLEMENT_DATA = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public string $csrfTokenRandom;
    private const TOKEN_EXPIRATION_TIME = 300;

    public function __construct(private SwooleSession $swooleSession,private array $csrfConfig)
    {}

    public function generateCSRFToken(): string
    {
        // ?  generate random token
        $data = $this->maker();
        $this->csrfTokenRandom = $data;

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $data = openssl_encrypt($data, 'aes-256-cbc', $this->csrfConfig['key'], OPENSSL_RAW_DATA, $iv);
        $token_hex = bin2hex($data);
        $iv_hex = bin2hex($iv);
        
        // ? $time maker 
        $time = isset($this->csrfConfig['expired']) ? time() + $this->csrfConfig['expired'] : time() + self::TOKEN_EXPIRATION_TIME;

        $token = $token_hex.$iv_hex;
        $this->swooleSession->add($iv_hex, $token_hex, $time);
        return $token;
    }

    public function validateToken($csrfToken): bool
    {
        // ? get the token
        $data = str_split($csrfToken, 160);
        if (count($data) !== 2) {
            return false; // Invalid token format
        }

        // ? get token data
        $tokenPart = mb_substr($csrfToken, 0, 160);
        $ivPart = mb_substr($csrfToken, 160);

        // ? hex data
        $iv = hex2bin($ivPart);
        $token = hex2bin($tokenPart);
        if ($this->swooleSession->isExist($ivPart)) {
            $data = openssl_decrypt($token, 'aes-256-cbc', $this->csrfConfig['key'], OPENSSL_RAW_DATA, $iv);
            
            // ? delete token from session
            $this->swooleSession->delete($ivPart);
            // ? check the data if valid
            if ($data === false) {
                echo "cannot decode";
                return false; // Decryption failed
            }
            return true;
        }else{
            echo "invalid";
            return false; // Token is invalid or expired

        }
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