<?php

namespace App\Classes;

class Encryptor
{
    private static $method;
    private static $password;
    private static $iv;

    // public function __construct()
    // {
    //     $this->method = env('CIPHER_METHOD');
    //     $this->password = substr(hash('sha256', env('CIPHER_PASSWORD'), true), 0, 32);
    //     $this->iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0);
    // }

    public static function encrypt($plaintext): string
    {
        self::$method = env('CIPHER_METHOD');
        self::$password = substr(hash('sha256', env('CIPHER_PASSWORD'), true), 0, 32);
        self::$iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0);
        return bin2hex(base64_encode(openssl_encrypt($plaintext, self::$method, self::$password, OPENSSL_RAW_DATA, self::$iv)));
        // return base64_encode(openssl_encrypt($plaintext, self::$method, self::$password, OPENSSL_RAW_DATA, self::$iv));
    }

    public static function decrypt($encrpyted_text): string
    {
        self::$method = env('CIPHER_METHOD');
        self::$password = substr(hash('sha256', env('CIPHER_PASSWORD'), true), 0, 32);
        self::$iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0);
        return openssl_decrypt(base64_decode(hex2bin($encrpyted_text)), self::$method, self::$password, OPENSSL_RAW_DATA, self::$iv);
        // return openssl_decrypt(base64_decode($encrpyted_text), self::$method, self::$password, OPENSSL_RAW_DATA, self::$iv);
    }
}
