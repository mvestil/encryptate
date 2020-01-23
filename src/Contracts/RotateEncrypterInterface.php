<?php

namespace Incube8\Encryptate\Contracts;

use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

/**
 * Interface RotateEncrypterInterface
 *
 * @date      2019-09-04
 * @author    markbonnievestil (mbvestil@gmail.com)
 */

interface RotateEncrypterInterface extends EncrypterContract
{
    public function isEncrypted($value): bool;
}