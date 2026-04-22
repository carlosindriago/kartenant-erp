<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | here which uses session storage and the Eloquent user provider.
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        // El guardia estándar para los usuarios de los tenants (clientes)
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Nuestro nuevo guardia de alta seguridad, exclusivo para el panel de admin
        'superadmin' => [
            'driver' => 'session',
            'provider' => 'superadmins',
            'cookie' => 'admin_session', // <-- Clave: Usa una cookie de sesión diferente y separada
        ],

        // Guardia dedicado para el panel de tenants (Filament App)
        'tenant' => [
            'driver' => 'session',
            'provider' => 'users',
            'cookie' => 'tenant_session',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | If you have multiple user tables or models you may configure multiple
    | sources which represent each model / table. These sources may then
    | be assigned to any authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        // El proveedor de usuarios estándar
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        // Nuestro nuevo proveedor que FUERZA la búsqueda de usuarios
        // únicamente en la base de datos 'landlord'.
        'superadmins' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
            'connection' => 'landlord', // <-- La clave: forzamos la conexión
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model in the application and you want to have
    | separate password reset settings based on the specific user types.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | times out and the user is prompted to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => 10800,

    /*
    |--------------------------------------------------------------------------
    | Two Factor Authentication
    |--------------------------------------------------------------------------
    |
    | This option controls if two-factor authentication is enabled globally
    | for the application. When disabled, users will not be required to
    | complete two-factor authentication after login.
    |
    | Set to true to enable 2FA for all users, or false to disable.
    | In the future, this can be made configurable per user.
    |
    */

    'two_factor_enabled' => env('AUTH_TWO_FACTOR_ENABLED', false),

];