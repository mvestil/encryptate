### What is this package for? ###

* This package extends Laravel's `Illuminate\Encryption\Encrypter` to support rotating keys
* This is useful when you want to change encryption key from time to time for security reasons, by easily updating APP_KEY to your new key, and APP_KEY_OLD to your old key
* This package has a command to generate rotating keys
* Version 1.0

### Installation ###

Simply to add to your composer.json
```
{
    "require": {
        "incube8/encryptate": "^2.0"
    }
}
```
### How do I get set up? ###

##### Step 1
* If you are using Laravel 5.5 and above, the service provider is automatically registered. Thanks to package auto-discovery
* Or you can also manually register Encryptate's service provider in your `config/app.php`
```
'providers' => [
    ...
    Incube8\Encryptate\EncryptateServiceProvider::class,
]
``` 

##### Step 2
* Add `key_old` in your `config/app.php` that uses your .env file
```
'key_old' => env('APP_KEY_OLD'),
```

##### Step 3
* Generate new rotating keys
```
php artisan rotational-key:generate PRFX
```
The argument of the artisan command is a 4 letter prefix. This is prepended to every encrypted value.

It is recommended to version your prefix everytime you create new keys to easily determine if encrypted data is old or new
```
php artisan rotational-key:generate V001
php artisan rotational-key:generate V002
```

The command will output
```
Base64 ENCODED Version : base64:X1YwMTVfOnxhQWR6SkpjRDBTRFVUNXZrd3R1NUI5cjc=
Base64 DECODED Version : _V001_:|@|aAdzJJcD0SDUT5vkwtu5B9
```
##### Step 4
* Copy the base64 encoded key from the artisan command and add it in your .env file. 
You can also use the base64 decoded version, but the encoded one is recommended
```
APP_KEY=base64:X1YwMTVfOnxhQWR6SkpjRDBTRFVUNXZrd3R1NUI5cjc=
APP_KEY_OLD=<your-existing-app-key>
```
### Usage ###
Use Laravel's Crypt facade normally as it was before.

* Encrypt
```
\Crypt::encrypt('Hello World');

// Output: _V001_:eyJpdiI6Ijg4Zjc4VFwvdU9rYnVLc0k3NFR2ZDVRPT0iLCJ2YWx1ZSI6ImdvekZxa3FJRlNTQ3hLSURRamJWMlwvZnVhUnpVQkc0NndzbCswVkM5TDM4PSIsIm1hYyI6IjE3MWNmN2U0ZjdhNzQ4YzQwMmJmNDE3M2YyNzY0ZmU0Y2ZkYmFhYmIyZmMwMTQ3YTY4OTc3MmRiMzE5YzRhOGQifQ==
```

* Decrypt
```
\Crypt::decrypt('_V001_:eyJpdiI6Ijg4Zjc4VFwvdU9rYnVLc0k3NFR2ZDVRPT0iLCJ2YWx1ZSI6ImdvekZxa3FJRlNTQ3hLSURRamJWMlwvZnVhUnpVQkc0NndzbCswVkM5TDM4PSIsIm1hYyI6IjE3MWNmN2U0ZjdhNzQ4YzQwMmJmNDE3M2YyNzY0ZmU0Y2ZkYmFhYmIyZmMwMTQ3YTY4OTc3MmRiMzE5YzRhOGQifQ==');

// Output: Hello World
```

* Decrypt automatically with old keys
```
// Set new key and old key in your .env
// APP_KEY=_V002_:|@|E2st92JVPJDp5PCz9YoIJu
// APP_KEY_OLD=_V001_:|@|VYIaSPtbfE3hVNXzO2tbZS

// This decrypts using the APP_KEY_OLD immediately, and will NOT attempt to decrypt with new key, thanks to the prefix :)
\Crypt::decrypt('_V001_:eyJpdiI6Ijg4Zjc4VFwvdU9rYnVLc0k3NFR2ZDVRPT0iLCJ2YWx1ZSI6ImdvekZxa3FJRlNTQ3hLSURRamJWMlwvZnVhUnpVQkc0NndzbCswVkM5TDM4PSIsIm1hYyI6IjE3MWNmN2U0ZjdhNzQ4YzQwMmJmNDE3M2YyNzY0ZmU0Y2ZkYmFhYmIyZmMwMTQ3YTY4OTc3MmRiMzE5YzRhOGQifQ==');

// Output: Hello World
```

##### Backwards Compatibility
* When you start using this package, every encrypt attempt onwards will add a prefix to the encrypted value. However if you have existing encrypted data without a prefix, when you try to decrypt them, the old key will immediately be used to decrypt.
* If you have not set any APP_KEY_OLD (i.e when you don't want to rotate keys yet), the APP_KEY will be considered as the APP_KEY_OLD

##### Drawbacks
* Cannot use Laravel's artisan `key:generate`, must use this package's artisan `rotational-key:generate` to create rotational keys

### TODOs ###

* Write unit tests
* Support multiple old keys

### Who do I talk to? ###

* Mark Bonnie Vestil (mark@incube8.sg / mbvestil@gmail.com)
* Other community or team contact