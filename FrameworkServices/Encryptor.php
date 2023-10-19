<?php

namespace FrameworkServices;

class Encryptor extends BaseServices
{
    /** @var string */
    private $key;

    /** @var string */
    private $ciphering;

    /** @var string */
    private $iv_length;

    /**
     * Initializes encryptor
     *
     * @param string $key
     * @return Encryptor
     */
    public function init(string $key): Encryptor
    {
        $this->key = $key;
        $this->ciphering = "AES-256-CBC";
        $this->iv_length = openssl_cipher_iv_length($this->ciphering);

        return $this;
    }

    /**
     * Formats a URL with base64_encode.
     *
     * @param string $string
     * @return string
     */
    function base64_encode_url($string)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($string));
    }

    /**
     * Formats a URL with base64_decode.
     *
     * @param string $string
     * @return string
     */
    function base64_decode_url($string)
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $string));
    }

    /**
     * OpenSSL Encrypts a value.
     *
     * @param int $id
     * @return string
     */
    function encrypt($id)
    {
        $encryption_iv = random_bytes($this->iv_length);

        $encryption = openssl_encrypt(
            $id,
            $this->ciphering,
            $this->key,
            0,
            $encryption_iv
        );

        return $this->base64_encode_url($encryption_iv . $encryption);
    }

    /**
     * OpenSSL Decrypts a value.
     *
     * @param int $id
     * @return string
     */
    function decrypt($id)
    {
        $decoded64 = $this->base64_decode_url($id);

        return openssl_decrypt(
            mb_substr($decoded64, $this->iv_length, null, '8bit'),
            $this->ciphering,
            $this->key,
            0,
            mb_substr($decoded64, 0, $this->iv_length, '8bit')
        );
    }
}
