<?php

namespace Incube8\Encryptate\Test;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Str;
use Incube8\Encryptate\EncrypterExtended;
use Incube8\Encryptate\Exceptions\EncryptionKeyUsedNotFound;
use Incube8\Encryptate\RotationalEncrypter;
use PHPUnit\Framework\TestCase;

/**
 * Class RotationalEncrypterTest
 *
 * @date      2019-10-01
 * @author    markbonnievestil
 * @copyright Copyright (c) Infostream Group
 */
class RotationalEncrypterTest extends TestCase
{
    /**
     *
     */
    const KEYS = [
        'legacy'       => 'wkHRWb5CGwDEl98VPx9I7L1234567890',
        'rotate-key-1' => '_V001_:|@|0dyABqn3OGcacvYGqypaKM',
        'rotate-key-2' => '_V002_:|@|jC8Uw7rcD1TSvrGdDtE4og',
    ];

    /**
     * @var string
     */
    protected $cipher = 'AES-256-CBC';

    /**
     * @var
     */
    protected $appKey;

    /**
     * @var
     */
    protected $appKeyOld;

    /**
     * @param string $appKey
     * @param string $appKeyOld
     * @return RotationalEncrypter
     */
    protected function makeEncrypter(string $appKey, string $appKeyOld = '')
    {
        $this->initKeys($appKey, $appKeyOld);

        // This subclass provides additional method for Laravel's default Encrypter
        $subclass = new EncrypterExtended($this->getAppKey(), $this->cipher);

        // if app key old is not provided, then we treat the app key as also the old
        $appKeyOld = $this->getAppKeyOld() ?: $this->getAppKey();
        $appKeyOld = $this->decodedKey($appKeyOld);

        return new RotationalEncrypter($subclass, $appKeyOld);
    }

    /**
     * @param string $key
     * @return string
     */
    protected function decodedKey(string $key): string
    {
        // If the old key starts with "base64:", we will need to decode the key before handing
        // it off to the encrypter. Keys may be base-64 encoded for presentation and we
        // want to make sure to convert them back to decoded version
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return $key;
    }

    /**
     * @param string $appKey
     * @param string $appKeyOld
     */
    protected function initKeys(string $appKey, string $appKeyOld = '')
    {
        $this->setAppKey($appKey);
        $this->setAppKeyOld($appKeyOld);
    }

    /**
     * @param string $key
     */
    protected function setAppKey(string $key)
    {
        $this->appKey = $key;
    }

    /**
     * @param string $key
     */
    protected function setAppKeyOld(string $key)
    {
        $this->appKeyOld = $key;
    }

    /**
     * @return string
     */
    protected function getAppKey(): string
    {
        return $this->appKey;
    }

    /**
     * @return mixed
     */
    protected function getAppKeyOld()
    {
        return $this->appKeyOld;
    }


    public function testEncryptAndDecryptWithoutRotationalKey()
    {
        $encrypter = $this->makeEncrypter(static::KEYS['legacy']);

        $text = 'Hello World';

        $encrypted = $encrypter->encrypt($text);
        $this->assertStringStartsNotWith('_', $encrypted);
        $this->assertEquals($text, $encrypter->decrypt($encrypted));
    }

    public function testEncryptAndDecryptWithRotationalKeyAndLegacyKey()
    {
        $legacyEncrypt = $this->makeEncrypter(static::KEYS['legacy']);
        $firstRotate   = $this->makeEncrypter(static::KEYS['rotate-key-1'], static::KEYS['legacy']);

        $text1 = 'Hello World';
        $text2 = 'Hello Universe';

        $this->assertStringStartsNotWith('_', $legacyEncrypt->encrypt($text1));
        $this->assertEquals($text1, $legacyEncrypt->decrypt($legacyEncrypt->encrypt($text1)));
        $this->assertStringStartsWith('_V001_', $firstRotate->encrypt($text1));

        // legacy key encrypter should be able to decrypt data encrypted by key legacy, but not by rotate-key-1
        $this->assertEquals($text1, $legacyEncrypt->decrypt($legacyEncrypt->encrypt($text1)));
        try {
            $legacyEncrypt->decrypt($firstRotate->encrypt($text2));
        } catch (\Exception $e) {
        }

        $this->assertEquals(EncryptionKeyUsedNotFound::class, get_class($e));

        // encrypter with active rotational key should be able to decrypt data encrypted by key legacy or rotate-key-1
        $this->assertEquals($text1, $firstRotate->decrypt($legacyEncrypt->encrypt($text1)));
        $this->assertEquals($text2, $firstRotate->decrypt($firstRotate->encrypt($text2)));
    }

    public function testEncryptAndDecryptWithBothActiveAndOldKeyRotational()
    {
        $firstRotate  = $this->makeEncrypter(static::KEYS['rotate-key-1'], static::KEYS['legacy']);
        $secondRotate = $this->makeEncrypter(static::KEYS['rotate-key-2'], static::KEYS['rotate-key-1']);

        $text1 = 'Hello World';
        $text2 = 'Hello Universe';

        $this->assertStringStartsWith('_V001_', $firstRotate->encrypt($text1));
        $this->assertStringStartsWith('_V002_', $secondRotate->encrypt($text2));

        // first encrypter should be able to decrypt data encrypted by key rotate-key-1, but not by rotate-key-2
        $this->assertEquals($text1, $firstRotate->decrypt($firstRotate->encrypt($text1)));
        try {
            $firstRotate->decrypt($secondRotate->encrypt($text2));
        } catch (\Exception $e) {
        }

        $this->assertEquals(EncryptionKeyUsedNotFound::class, get_class($e));

        // second encrypter should be able to decrypt data encrypted by key rotate-key-1 or rotate-key-2
        $this->assertEquals($text1, $secondRotate->decrypt($firstRotate->encrypt($text1)));
        $this->assertEquals($text2, $secondRotate->decrypt($secondRotate->encrypt($text2)));

        // first and second encrypter should not be able to decrypt a data encrypted with legacy key
        $legacyEncrypter = $this->makeEncrypter(static::KEYS['legacy']);
        $this->assertEquals($text1, $firstRotate->decrypt($legacyEncrypter->encrypt($text1)));

        try {
            $secondRotate->decrypt($legacyEncrypter->encrypt($text2));
        } catch (\Exception $e) {
            $this->assertEquals(DecryptException::class, get_class($e));
        }
    }
}