<?php
namespace Incube8\Encryptate;

use Illuminate\Encryption\Encrypter;

/**
 * ClassEncrypterExtended
 *
 * @date      2019-09-05
 * @author    markbonnievestil (mbvestil@gmail.com)
 */
class EncrypterExtended extends Encrypter
{
    /**
     * Set the key
     *
     * @param $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }
}