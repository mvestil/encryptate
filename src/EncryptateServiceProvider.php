<?php

namespace Encryptate;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Encryptate\Console\Commands\RotatingKeyGenerateCommand;
use RuntimeException;

/**
 * Class EncryptateServiceProvider
 *
 * @date      2019-09-04
 * @author    markbonnievestil (mbvestil@gmail.com)
 */
class EncryptateServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * This method is called after all other service providers have
     * been registered, meaning you have access to all other services
     * that have been registered by the framework.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->extendEncrypter();

        $this->commands([RotatingKeyGenerateCommand::class]);
    }

    /**
     * Extend Laravel's Encrypter by wrapping Encrypter class with EncrypterExtended,
     * then decorate it with RotationalEncrypter to support rotating keys
     */
    protected function extendEncrypter()
    {
        $this->app->extend('encrypter', function ($service, $app) {
            $config = $app->make('config')->get('app');

            // This subclass provides additional method for Laravel's default Encrypter
            $subclass = new EncrypterExtended($service->getKey(), $config['cipher']);

            $oldKey = $config['key_old'] ?? $config['key'];
            // If the old key starts with "base64:", we will need to decode the key before handing
            // it off to the encrypter. Keys may be base-64 encoded for presentation and we
            // want to make sure to convert them back to decoded version
            if (Str::startsWith($oldKey, 'base64:')) {
                $oldKey = base64_decode(substr($oldKey, 7));
            }

            return new RotationalEncrypter($subclass, $oldKey);
        });
    }
}
