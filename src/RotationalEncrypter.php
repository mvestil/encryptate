<?php

namespace Encryptate;

use Encryptate\Contracts\RotateEncrypterInterface;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Support\Str;
use Encryptate\Exceptions\EncryptionKeyUsedNotFound;

/**
 * Class RotationalEncrypter
 *
 * @date      2019-09-04
 * @author    markbonnievestil
 * @copyright Copyright (c) Infostream Group
 */
class RotationalEncrypter implements RotateEncrypterInterface
{
    /**
     * @var EncrypterContract
     */
    protected $encrypter;

    /**
     * @var string
     */
    protected $oldKey;

    /**
     * RotationalEncrypter constructor.
     *
     * @param EncrypterContract $encrypter
     * @param string            $oldKey
     */
    public function __construct(EncrypterContract $encrypter, string $oldKey)
    {
        $this->encrypter = $encrypter;
        $this->oldKey    = $oldKey;
    }

    /**
     * Encrypt the given value. We always encrypt using the new key
     *
     * @param mixed $value
     * @param bool  $serialize
     * @return string
     */
    public function encrypt($value, $serialize = true): string
    {
        return $this->getActivePrefix() . $this->encrypter->encrypt($value, $serialize);
    }

    /**
     * Decrypt the given value.
     *
     * @param mixed $value
     * @param bool  $unserialize
     *
     * @return string|null
     * @throws \Exception
     */
    public function decrypt($value, $unserialize = true)
    {
        return $this->rotationalDecrypt($value, $unserialize);
    }

    /**
     * Encrypt a plain string
     *
     * @param string $value
     * @return string
     */
    public function encryptString(string $value): string
    {
        return $this->encrypt($value, false);
    }

    /**
     * Decrypt a plain string
     *
     * @param string $value
     * @return string|null
     * @throws EncryptionKeyUsedNotFound
     */
    public function decryptString(string $value)
    {
        return $this->rotationalDecrypt($value, false);
    }

    /**
     * Decrypt a given value in a rotational way.
     * It determines first the encryption key used (new or old key) and decrypt it using that key
     *
     * @param mixed $value
     * @param bool  $unserialize
     * @return string|null
     * @throws EncryptionKeyUsedNotFound
     */
    protected function rotationalDecrypt($value, $unserialize = true)
    {
        // When the value is encrypted without a prefix (means not using rotational key),
        // then we assume that it was encrypted with an old key, so decrypt using the old key
        if (!$this->isEncryptedWithAPrefix($value)) {
            // TODO : support multiple old keys
            return (clone $this->encrypter)
                ->setKey($this->oldKey)
                ->decrypt($value, $unserialize);
        }

        list($keyUsed, $prefix) = $this->getEncryptionUsed($value);

        $value = str_replace($prefix, '', $value);

        // if encryption key used is not the same as active key
        // create a new instance of encrypter
        if ($keyUsed != $this->getActiveKey()) {
            return (clone $this->encrypter)
                ->setKey($keyUsed)
                ->decrypt($value, $unserialize);
        }

        return $this->encrypter->decrypt($value, $unserialize);
    }

    /**
     * Determine whether a string has already been encrypted.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isEncrypted($value): bool
    {
        return $this->isEncryptedWithNewKey($value) || $this->isEncryptedWithOldKey($value);
    }

    /**
     * Get the encryption key used and the prefix
     *
     * @param mixed $value
     * @return array
     * @throws EncryptionKeyUsedNotFound
     */
    private function getEncryptionUsed($value): array
    {
        $activePrefix = $this->getActivePrefix();
        if ($activePrefix && strpos((string)$value, $activePrefix) === 0) {
            return [$this->getActiveKey(), $activePrefix];
        }

        $oldKeys = $this->parseOldKeys();
        foreach ($oldKeys as $oldKey) {
            $oldPrefix = $this->extractPrefixFromKey($oldKey);
            if ($this->isEncryptedUsingPrefix($value, $oldPrefix)) {
                return [$oldKey, $oldPrefix];
            }
        }

        throw new EncryptionKeyUsedNotFound('Unable to determine the encryption key used');
    }

    /**
     * /**
     * Get the active prefix to be used. This is found in APP_KEY of .env delimited by '|'
     *      Ex. _V001_:|fhasdgHtrybvpkHLKnHTYGHL
     *
     * If no prefix is found, this will return the default prefix
     *
     * @return string
     */
    protected function getActivePrefix(): string
    {
        return $this->extractPrefixFromKey($this->getActiveKey());
    }

    /**
     * Get the old keys
     *
     * @return array
     */
    protected function parseOldKeys(): array
    {
        $all = collect(explode(',', $this->oldKey));

        return $all->toArray();
    }

    /**
     * Extract and return the prefix of a key
     * If a delimiter is not found, return the default prefix __ELOCRYPT__
     *
     * @param string $key
     * @return string
     */
    protected function extractPrefixFromKey(string $key): string
    {
        if (!Str::contains($key, '|@|')) {
            return '';
        }

        return explode('|@|', $key)[0];
    }

    /**
     * Check if the value is encrypted with a new key
     *
     * @param mixed $value
     * @return bool
     */
    protected function isEncryptedWithNewKey($value): bool
    {
        return $this->getActivePrefix() && strpos((string)$value, $this->getActivePrefix()) === 0;
    }

    /**
     * Check if the value is encrypted with an old key
     *
     * @param mixed $value
     * @return bool
     */
    protected function isEncryptedWithOldKey($value): bool
    {
        $oldKeys = $this->parseOldKeys();
        foreach ($oldKeys as $oldKey) {
            if ($this->isEncryptedUsingPrefix($value, $this->extractPrefixFromKey($oldKey))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a value is encrypted using a specific prefix
     *
     * @param mixed  $value
     * @param string $prefix
     * @return bool
     */
    protected function isEncryptedUsingPrefix($value, string $prefix): bool
    {
        if ($prefix && strpos((string)$value, $prefix) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Get the active key
     *
     * @return string
     */
    protected function getActiveKey(): string
    {
        return $this->encrypter->getKey();
    }

    /**
     * Check if a given value is encrypted with a prefix
     *
     * @param mixed $value
     * @return bool
     */
    protected function isEncryptedWithAPrefix($value): bool
    {
        $exploded = explode(':', $value);
        if (count($exploded) !== 2) {
            return false;
        }

        return (Str::startsWith($exploded[0], '_') && Str::endsWith($exploded[0], '_'));
    }

    /**
     * Check if the active key is rotational
     *
     * @return bool
     */
    public function isActiveKeyRotational(): bool
    {
        if ($this->getActivePrefix()) {
            return true;
        }

        return false;
    }
}