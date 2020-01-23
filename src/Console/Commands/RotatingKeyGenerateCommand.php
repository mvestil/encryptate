<?php

namespace Encryptate\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;

/**
 * Class RotatingKeyGenerateCommand
 *
 * @date      2019-09-05
 * @author    markbonnievestil (mbvestil@gmail.com)
 */
class RotatingKeyGenerateCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rotational-key:generate
                    {four-letter-prefix : Prefix for the key, used for rotation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the application key';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (strlen($this->argument('four-letter-prefix')) != 4) {
            return $this->error('Prefix must be exactly 4 characters long');
        }

        list($encodedVersion, $decodedVersion) = $this->generateRandomKey();

        $this->line('<comment>Base64 ENCODED Version : ' . $encodedVersion . '</comment>');
        $this->line('<comment>Base64 DECODED Version : ' . $decodedVersion . '</comment>');
    }

    /**
     * Generate a random key for the application.
     *
     * @return array
     */
    public function generateRandomKey(): array
    {
        // TODO : This is best to use instead of random string.
        //$generated = Encrypter::generateKey($this->laravel['config']['app.cipher']);

        $generated = Str::random($this->laravel['config']['app.cipher'] === 'AES-128-CBC' ? 16 : 32);
        $prefix    = '_' . $this->argument('four-letter-prefix') . '_:';
        $delimiter = '|@|';
        $prefix    .= $delimiter;
        $key       = substr_replace($generated, $prefix, 0, 10);

        $key = 'base64:' . base64_encode($key);

        return [$key, base64_decode(substr($key, 7))];
    }
}