<p align="center">
    <img src="https://img.shields.io/packagist/v/uepg/laravel-keycloak-web-guard.svg" />
    <img src="https://img.shields.io/packagist/dt/uepg/laravel-keycloak-web-guard.svg" />
</p>

#  Keycloak Web Guard for Laravel

This packages allow you authenticate users with [Keycloak Server](https://www.keycloak.org).

It works on front. For APIs we recommend [laravel-keycloak-guard](https://github.com/robsontenorio/laravel-keycloak-guard).

## Requirements

* Have a Keycloak Server.
* Have a realm configured and a client that accepts authentication.

### Support

This package was tested with:

* PHP: 8.2+
* Laravel: 11 / 12 / 13 (tested on CI)
* Keycloak: 18+

Need an older Laravel? Use `vizir/laravel-keycloak-web-guard` 4.x.

Any other version is not guaranteed to work.

*This is project is open source and maintained on my free time. So, if you have any problem you can open a Issue with all details (laravel version, keycloak version, the description of problem...) and I'll be happy to try to help.*

## The flow

1. User access a guarded route and is redirected to Keycloak login.
1. User signin and obtains a code.
1. He's redirected to callback page and we change the code for a access token.
1. We store it on session and validate user.
1. User is logged.
1. We redirect the user to "redirect_url" route (see config) or the intended one.

## Install

Require the package

```
composer require uepg/laravel-keycloak-web-guard
```

### Migrating from `vizir/laravel-keycloak-web-guard`

This is the same package under a new name. Swap the package:

```
composer remove vizir/laravel-keycloak-web-guard
composer require uepg/laravel-keycloak-web-guard
```

The namespace changed from `Vizir\KeycloakWebGuard` to `Uepg\KeycloakWebGuard`. The old class names still work as aliases (your `config/auth.php`, models extending `KeycloakUser`, `catch` blocks...), but they are deprecated and will be removed on 6.0, so replace `Vizir\KeycloakWebGuard` with `Uepg\KeycloakWebGuard` in your code when you can.

Then read the [CHANGELOG](CHANGELOG.md): token signatures are now verified by default.

If you want to change routes or the default values for Keycloak, publish the config file:

```
php artisan vendor:publish  --provider="Uepg\KeycloakWebGuard\KeycloakWebGuardServiceProvider"

```

## Configuration

After publishing `config/keycloak-web.php` file, you can change the routes:

```php
'redirect_url' => '/admin',

'routes' => [
    'login' => 'login',
    'logout' => 'logout',
    'register' => 'register',
    'callback' => 'callback',
]
```

The scope `openid` is always included, but if you need extra scopes you can add them as strings to the array:

```php
'scopes' => [],
```

Example:

```php
'scopes' => ['example_scope_1', 'example_scope_2'],
```

Change any value to change the URL.

Other configurations can be changed to have a new default value, but we recommend to use `.env` file:

*  `KEYCLOAK_BASE_URL`

The Keycloak Server url. Generally is something like: `https://your-domain.com/auth`.

*  `KEYCLOAK_REALM`

The Keycloak realm. The default is `master`.

*  `KEYCLOAK_VERIFY_SIGNATURE`

Verify the signature of the access and ID tokens. Default is `true`.

Keys are loaded from the realm JWKS endpoint (`jwks_uri`), so nothing else is required. If the key is rotated on Keycloak, we load the keys again.

*  `KEYCLOAK_REALM_PUBLIC_KEY`

Optional. The Keycloak Server realm public key (string). If set, it's used to verify the signatures (RS256) instead of the JWKS endpoint. Remember to update it when you rotate the realm keys.

In dashboard go to: Keycloak >> Realm Settings >> Keys >> RS256 >> Public Key.

*  `KEYCLOAK_CLIENT_ID`

Keycloak Client ID.

In dashboard go to: Keycloak >> Clients >> Installation.

*  `KEYCLOAK_CLIENT_SECRET`

Keycloak Client Secret. If empty we'll not send it to Token Endpoint.

In dashboard go to: Keycloak >> Clients >> Installation.

*  `KEYCLOAK_CACHE_OPENID`

We can cache the OpenId Configuration: it's a list of endpoints we require to Keycloak.

If you activate it, *remember to flush the cache* when change the realm or url. The realm keys (JWKS) are cached too.

*  `KEYCLOAK_CACHE_OPENID_TTL`

How long (seconds) to keep the OpenId Configuration and keys in cache. Default is `86400` (1 day). Empty caches forever.

*  `KEYCLOAK_CACHE_USER_PROFILE`

Keep the user profile (userinfo) in session until the access token changes. Default is `false`, which requests Keycloak on every page load. The token is still validated on every request.

*  `KEYCLOAK_REDIRECT_URL`

Page to redirect after login when there's no intended URL. Default is `/admin`.

*  `KEYCLOAK_REDIRECT_LOGOUT`

Page Keycloak redirects to after logout. Default is your app home.

### Logout via POST

The logout route accepts `GET` by default, so any link (or an `<img>` on another site) can log your user out. We recommend accepting only `POST`, which is protected by CSRF:

```php
'logout_methods' => ['POST'],
```

```blade
<form method="POST" action="{{ route('keycloak.logout') }}">
    @csrf
    <button type="submit">Logout</button>
</form>
```

## Laravel Auth

You should add Keycloak Web guard to your `config/auth.php`.

Just add **keycloak-web** to "driver" option on configurations you want.

As my default is web, I add to it:

```php
'guards' => [
    'web' => [
        'driver' => 'keycloak-web',
        'provider' => 'users',
    ],

    // ...
],
```

And change your provider config too:

```php
'providers' => [
    'users' => [
        'driver' => 'keycloak-users',
        'model' => Uepg\KeycloakWebGuard\Models\KeycloakUser::class,
    ],

    // ...
]
```

**Note:** if you want use another User Model, check the FAQ *How to implement my Model?*.

## API

We implement the `Illuminate\Contracts\Auth\Guard`. So, all Laravel default methods will be available.

Ex: `Auth::user()` returns the authenticated user.

## Roles

You can check user has a role simply by `Auth::hasRole('role')`;

This method accept two parameters: the first is the role (string or array of strings) and the second is the resource.

If not provided, resource will be the client_id, which is the regular check if you authenticating into this client to your front.

### Keycloak Web Gate

You can use [Laravel Authorization Gate](https://laravel.com/docs/7.x/authorization#gates) to check user against one or more roles (and resources).

For example, in your Controller you can check **one role**:

```php
if (Gate::denies('keycloak-web', 'manage-account')) {
  return abort(403);
}
```

Or **multiple roles**:

```php
if (Gate::denies('keycloak-web', ['manage-account'])) {
  return abort(403);
}
```

And **roles for a resource**:

```php
if (Gate::denies('keycloak-web', 'manage-account', 'another-resource')) {
  return abort(403);
}
```

*This last use is not trivial, but you can extend the Guard to request authentication/authorization to multiple resources. By default, we request only the current client.*

### Keycloak Can Middleware

If you do not want to use the Gate or already implemented middlewares, you can check user against one or more roles using the `keycloak-web-can` Middleware.

Add this to your Controller's `__construct` method:

```php
$this->middleware('keycloak-web-can:manage-something-cool');

// For multiple roles, separate with '|' (user must have all of them)
$this->middleware('keycloak-web-can:manage-something-cool|manage-something-nice|manage-my-application');

// User must have at least one of the roles
$this->middleware('keycloak-web-can-any:manage-something-cool|manage-something-nice');
```

`keycloak-web-can-one` is the old name of `keycloak-web-can-any` and still works.

These middlewares search for roles on default resource (client_id) and on realm roles.

You can extend it and register your own middleware on Kernel.php or just use `Auth::hasRole($roles, $resource)` on your Controller.

## FAQ

### How to implement my Model?

We registered a new user provider that you configured on `config/auth.php` called "keycloak-users".

In this same configuration you setted the model. So you can register your own model extending `Uepg\KeycloakWebGuard\Models\KeycloakUser` class and changing this configuration.

You can implement your own [User Provider](https://laravel.com/docs/5.8/authentication#adding-custom-user-providers): just remember to implement the `retrieveByCredentials` method receiving the Keycloak Profile information to retrieve a instance of model.

Eloquent/Database User Provider should work well as they will parse the Keycloak Profile and make a "where" to your database. So your user data must match with Keycloak Profile.

### I cannot find my login form.

We register a `login` route to redirect to Keycloak Server. After login we'll receive and proccess the token to authenticate your user.

There's no login/registration form.

### How can I protect a route?

Just add the `keycloak-web` middleware:

```php
// On RouteServiceProvider.php for example

Route::prefix('admin')
  ->middleware('keycloak-web')
  ->namespace($this->namespace)
  ->group(base_path('routes/web.php'));

// Or with Route facade in another place

Route::group(['middleware' => 'keycloak-web'], function () {
    Route::get('/admin', 'Controller@admin');
});


```

### Where the access/refresh tokens and state are persisted?

On session. We recommend implement the database driver if you have load balance.

### What's a state?

State is a unique and non-guessable string used to mitigate CSRF attacks.

We associate each authentication request about to be initiated with one random state and check on callback. You should do it if you are extending/implementing your own Auth controller.

Use `KeycloakWeb::saveState()` method to save the already generated state to session and `KeycloakWeb::validateState()` to check the current state against the saved one.

### I'm having problems with session (stuck on login loop)

For some reason Laravel can present a problem with EncryptCookies middleware changing the session ID.

In this case, we will always try to login, as tokens cannot be retrieved.

You can remove session_id cookie from encryption:

```php
// On your EncryptCookies middleware

class EncryptCookies extends Middleware
{
    protected $except = [];

    public function __construct(EncrypterContract $encrypter)
    {
        parent::__construct($encrypter);

        /**
         * This will disable in runtime.
         *
         * If you have a "session.cookie" option or don't care about changing the app name
         * (in another environment, for example), you can only add it to "$except" array on top
         */
        $this->disableFor(config('session.cookie'));
    }
}

```

### My client is not public.

If your client is not public, you should provide a `KEYCLOAK_CLIENT_SECRET` on your `.env`.

### How can I override the default Guzzle options?

In some use cases you may need to override the default Guzzle options - likely either to disable SSL verification or to set a Proxy to route all requests through.

Every [Guzzle Request Option](http://docs.guzzlephp.org/en/stable/request-options.html) is supported and is passed directly to the Guzzle Client instance.

Just add the options you would like to `guzzle_options` array on `keycloak-web.php` config file. For example:

```
'guzzle_options' => [
    'verify' => false
]
```

### How can I show users on Laravel Pulse (or find a user by id)?

Keycloak only gives us a user when we have their token, so by default `retrieveById()` returns `null`. Packages like [Laravel Pulse](https://pulse.laravel.com) call it to show users who are not logged in right now.

Enable `KEYCLOAK_REMEMBER_USERS=true` and the user profile (userinfo) is kept in cache after login, so `retrieveById()` can return it:

*  `KEYCLOAK_REMEMBER_USERS`: default `false`.
*  `KEYCLOAK_REMEMBER_USERS_TTL`: seconds to keep the profile. Default is 30 days.
*  `KEYCLOAK_REMEMBER_USERS_STORE`: cache store. Default is your default store.

The profile is written once per session (or when it changes), not on every request. Remember it holds personal data (name, email...) in your cache.

For users not in cache, you can register a resolver (in a service provider `boot()`), returning a profile array, an `Authenticatable` or `null`:

```php
use Uepg\KeycloakWebGuard\Auth\KeycloakWebUserProvider;

KeycloakWebUserProvider::resolveUsersUsing(function ($identifier) {
    return ['email' => $identifier, 'name' => MyDirectory::nameFor($identifier)];
});
```

> **Laravel Pulse needs a resolver that never returns `null`.** Pulse calls `retrieveById()` for every user it shows and fails with `Laravel\Pulse\Users::key(): Argument #1 ($user) must be of type Illuminate\Contracts\Auth\Authenticatable, null given` on the first one it can't find (anyone not in cache, like users who haven't logged in since you enabled `KEYCLOAK_REMEMBER_USERS`). Return at least the identifier, so Pulse shows it instead of a name:
>
> ```php
> KeycloakWebUserProvider::resolveUsersUsing(function ($identifier) {
>     return ['email' => $identifier, 'name' => MyDirectory::nameFor($identifier)]; // name can be null
> });
> ```
>
> The array must have the field your model uses as identifier (`email` for `KeycloakUser`), or Pulse won't match the user.
>
> The package doesn't do this by default on purpose: a provider that returns a user for any id would let any guard using it log in as anyone (e.g. `Auth::loginUsingId()`). Use `keycloak-users` only with the `keycloak-web` guard.

### How can I stop Keycloak errors from filling my logs?

The package does not write to the log directly. Every error is a `Uepg\KeycloakWebGuard\Exceptions\KeycloakException` (or a subclass) sent to Laravel's exception handler with `report()`, so you control it like any other exception.

* `KeycloakRequestException`: a request to Keycloak failed. Its log context has the method, URL, status code and the first 500 characters of the response body (request headers are never logged).
* `KeycloakTokenException`: the token or user profile returned by Keycloak is not valid.
* `KeycloakCallbackException`: Keycloak returned an error on the callback, or the state is invalid.

On Laravel 11+ (`bootstrap/app.php`):

```php
->withExceptions(function (Exceptions $exceptions) {
    // Ignore all of them...
    $exceptions->dontReport(\Uepg\KeycloakWebGuard\Exceptions\KeycloakException::class);

    // ...or just lower the level
    $exceptions->level(\Uepg\KeycloakWebGuard\Exceptions\KeycloakRequestException::class, \Psr\Log\LogLevel::WARNING);
})
```

On older versions, add the class to `$dontReport` in `app/Exceptions/Handler.php`.

## Developers

* Mário Valney[@mariovalney](https://twitter.com/mariovalney) is the original developer of this package, now the Ponta Grossa State University is mantaining this fork.

With contributors on GitHub :heart:

### Running the tests

```
composer install
composer test
```
