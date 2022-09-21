<?php

namespace App\Utils;

class Crypter
{
    /**
     * Encrypts the message using someone else's key
     *
     * @param String $message - base64 encode of the data
     *
     * @return String - base64 encode of the encrypted data
     */
    public static function symmetricalEncryptUsingOwnKey($message)
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $crypt = sodium_crypto_secretbox(
            $message,
            $nonce,
            self::symmetricalKey()
        );
        return base64_encode(
            $nonce . $crypt
        );
    }

    /**
     * Encrypts the message using other key
     *
     * @param mixed $message - binary  or string
     * @param String $key - base64 encode of other key
     *
     * @return String - base64 encode of the encrypted data
     */
    public static function symmetricalEncryptUsingOtherKey($message, $key)
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $crypt = sodium_crypto_secretbox(
            $message,
            $nonce,
            base64_decode($key)
        );
        return base64_encode(
            $nonce . $crypt
        );
    }

    /**
     * Dencrypts the message using own key
     *
     * @param String $encrypted - base64 encode of the encrypted data
     *
     * @return mixed binary or string
     */
    public static function symmetricalDecryptUsingOwnKey($encrypted)
    {
        $decoded = base64_decode($encrypted);
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        return sodium_crypto_secretbox_open(
            $ciphertext,
            $nonce,
            self::symmetricalKey()
        );
    }

    /**
     * Dencrypts the message using other key
     *
     * @param String $encrypted - base64 encode of the encrypted data
     * @param String $key - base64 encode of other key
     *
     * @return mixed binary or string
     */
    public static function symmetricalDecryptUsingOtherKey($encrypted, $key)
    {
        $decoded = base64_decode($encrypted);
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        return sodium_crypto_secretbox_open(
            $ciphertext,
            $nonce,
            base64_decode($key)
        );
    }

    private static function symmetricalKey()
    {
        return base64_decode(config('encryption.symmetrical.key'));
    }

    private static function privateKey()
    {
        return base64_decode(config('encryption.asymmetrical.private_key'));
    }

        /**
     * @param String $encrypted -  base64 encode of the encrypted data
     *
     * @return String
     */
    public function decryptPublicKeyEncryption($encrypted)
    {
        $decoded = base64_decode($encrypted);
        return sodium_crypto_box_seal_open(
            $decoded,
            self::privateKey()
        );
    }
}
