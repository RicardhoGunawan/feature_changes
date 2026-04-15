<?php

if (!function_exists('encryptData')) {
    /**
     * Encrypt string to database directly
     */
    function encryptData($string) {
        if ($string === null || $string === '') return '';

        $method = 'AES-256-CBC';

        // Mengambil key dari env atau config laravel
        $key = hash('sha256', env('APP_ENCRYPT_KEY', 'default_key'));
        $iv = substr(hash('sha256', env('APP_ENCRYPT_IV', 'default_iv')), 0, 16);

        $encrypted = openssl_encrypt($string, $method, $key, 0, $iv);

        return base64_encode($encrypted);
    }
}

if (!function_exists('decryptData')) {
    /**
     * Decrypt string from database
     */
    function decryptData($string) {
        if ($string === null || $string === '' || $string === '-') return $string;

        // Cek apakah string adalah base64 valid (sederhana)
        // Jika bukan base64, mungkin data memang belum diencrypt
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string)) return $string;

        $method = 'AES-256-CBC';

        $key = hash('sha256', env('APP_ENCRYPT_KEY', 'default_key'));
        $iv = substr(hash('sha256', env('APP_ENCRYPT_IV', 'default_iv')), 0, 16);

        $decrypted = openssl_decrypt(
            base64_decode($string),
            $method,
            $key,
            0,
            $iv
        );

        return $decrypted ?: $string;
    }
}
