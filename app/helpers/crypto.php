<?php

function getEncryptionKey()
{
    return hash('sha256', '#tHZZfp4tE$H2xqsRnBt');
}

function encryptValue($plainText)
{
    $method = 'AES-256-CBC';
    $key = getEncryptionKey();
    $ivLength = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($ivLength);

    $encrypted = openssl_encrypt($plainText, $method, $key, 0, $iv);

    return base64_encode($iv . '::' . $encrypted);
}

function decryptValue($encryptedText)
{
    $method = 'AES-256-CBC';
    $key = getEncryptionKey();

    $decoded = base64_decode($encryptedText);

    if (!$decoded || strpos($decoded, '::') === false) {
        return '';
    }

    list($iv, $encrypted) = explode('::', $decoded, 2);

    return openssl_decrypt($encrypted, $method, $key, 0, $iv);
}