# eFaas Laravel Socialite

[Laravel Socialite](https://github.com/laravel/socialite) Provider for [eFaas](https://efaas.gov.mv/).

## Installation

You can install the package via composer:

``` bash
composer require ncitmv/efaas-socialite
```

**Laravel 5.5** and above uses Package Auto-Discovery, so doesn't require you to manually add the ServiceProvider.

After updating composer, add the ServiceProvider to the providers array in config/app.php

``` bash
Ncit\Efaas\Socialite\Providers\EfaasSocialiteServiceProvider::class,
```


### Add configuration to `config/services.php`

```php
'efaas' => [    
    'client_id' => env('EFAAS_CLIENT_ID'),  
    'client_secret' => env('EFAAS_CLIENT_SECRET'),  
    'redirect' => env('EFAAS_CLIENT_REDIRECT_URI'),
    'server_url' => env('EFAAS_URL', 'https://efaas.gov.mv/connect'),
],
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):
Refer to the [Official Social Docs](https://laravel.com/docs/8.x/socialite#routing) for more info.

**Warning:** If you get `403 Forbidden` error when your Laravel app makes requests to the eFaas authorization endpoints, request NCIT to whitelist your server IP.

```php
//efaas default scopes are openid and efaas.profile
return Socialite::driver('efaas')->redirect();

//to get extra scopes pass other scopes on scopes methods
return Socialite::driver('efaas')->scopes([
    'openid',
    'efaas.profile',
    'efaas.email',
    'efaas.mobile',
    'efaas.passport_number',
    'efaas.country',
    'efaas.work_permit_status',
    'efaas.photo'
])->redirect();

```

and in your callback handler, you can access the user data like so.

```
$efaasUser = Socialite::driver('efaas')->user();
$accessToken = $efaasUser->token;
```

#### Logging out the eFaas User

In your Laravel logout redirect, redirect with the provider `logOut()` method using the access token saved during login

``` php
return Socialite::driver('efaas')->logOut($access_token, $post_logout_redirect_url);
```

#### Using eFaas One-tap Login

This package will automatically add an /efaas-one-tap-login endpoint to your web routes which will redirect to eFaas with the eFaas login code.

Sometimes you may wish to customize the routes defined by the Efaas Provider. To achieve this, you first need to ignore the routes registered by Efaas Provider by adding `EfaasProvider::ignoreRoutes` to the register method of your application's `AppServiceProvider`:

``` php
use Ncit\Efaas\EfaasProvider;

/**
 * Register any application services.
 */
public function register(): void
{
    EfaasProvider::ignoreRoutes();
}

```

Then, you may copy the routes defined by Efaas Provider in [its routes file](/routes/web.php) to your application's routes/web.php file and modify them to your liking:

```php
Route::group([
    'as' => 'efaas.',
    'namespace' => '\Ncit\Efaas\Http\Controllers',
], function () {
    // Efaas routes...
});
```

#### Authenticating from mobile apps

To authenticate users from mobile apps, redirect to the eFaas login screen through a Web View on the mobile app.
Then intercept the `code` (authorization code) from eFaas after they redirect you back to your website after logging in to eFaas.

Once your mobile app receives the auth code, send the code to your API endpoint.
You can then get the eFaas user details from your server side using the auth code as follows:

``` php
$efaas_user = Socialite::driver('efaas')->userFromCode($code);
```

After you receive the eFaas user, you can then issue your own access token or API key according to whatever authentication scheme you use for your API.

#### Changing the eFaas login prompt behaviour

The eFaas login prompt behaviour can be customized by modifying the prompt option on your redirect request
```php
return Socialite::driver('efaas')->with(['prompt' => 'select_account'])->redirect();
```

The available prompt options are:

 Option                  | Description                                    
------------------------ |----------------------------------------------- 
**`login`**              | Forces the user to enter their credentials on that request, regardless of whether the user is already logged into eFaas.
**`none`**               | Opposite of the `login` option. Ensures that the user isn't presented with any interactive prompt. If the request can't be completed silently by using single-sign on, the Microsoft identity platform returns an interaction_required error.                                     
**`consent`**            | Triggers the OAuth consent dialog after the user signs in, asking the user to grant permissions to the app.
**`select_account`**     | Interrupts the single sign-on, providing account selection experience listing all the accounts either in session or any remembered account or an option to choose to use a different account altogether

#### Available properties for eFaas User

``` php
$id_number = $efaasUser->username;
```

#### All Available eFaas data fields
 Field                   | Description                                   
------------------------ |-----------------------------------------------
**`id`**                 | Efaas User Identifier                          |
**`name`**               | Full Name                                      | `Ahmed Mohamed`
**`first_name`**         | First Name                                     | 
**`middle_name`**        | Middle Name                                    | 
**`last_name`**          | Last Name                                      | `Mohamed`
**`name_dhivehi`**       | Full Name In dhivehi                           | `Ahmed Mohamed`
**`first_name_dhivehi`** | First name in Dhivehi                          | `އަހުމަދު`
**`middle_name_dhivehi`**| Middle name in Dhivehi                         |
**`last_name_dhivehi`**  | Last name in Dhivehi                           | `މުހައްމަދު`
**`user_type`**          | User type<br>1- Maldivian<br>2- Work Permit Holder<br>3- Foreigners | 1
**`username`**           | ID number in case of maldivian and workpermit number in case of expatriates | `A037420`
**`birthdate`**           | Date of birth. (Carbon instance)              | `10/28/1987`
**`gender`**             | Gender                                         | `M` or `F`
**`email`**              | Email address                                  | `ahmed@example.com`
**`mobile`**             | Registered phone number                        | `9939900`
**`photo`**              | User photo                                     | `https://api.efaas.gov.mv/user/photo`
**`passport_number`**    | Passport number of the individual (expat and foreigners only) | 
**`is_workpermit_active`** | Is the work permit active                    | `false`
**`permanentAddress`**  | Permananet Address. Country will contain an ISO 3 Digit country code. | ```["AddressLine1" => "Light Garden", "AddressLine2" => "", "Road" => "", "AtollAbbreviation" => "K", "IslandName" => "Male", "HomeNameDhivehi" => "ލައިޓްގާރޑްން", "Ward" => "Maafannu", "Country" => "462"]```                                     | `Ahmed`
**`country`**             | user Country name                   | `Maldivian`
**`countryCode`**         | user Country Code                   | `MDV`
**`is_verified`**         | Whether User is verified or Not | `true` or `false` 
**`verification_type`**   | user verification type  | `manual` or `face` 
**`updated_at`**          | Information Last Updated date. (Carbon instance) | `10/28/2017`

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email is@ncit.gov.mv instead of using the issue tracker.

## Credits

- [Javaabu Pvt. Ltd.](https://github.com/javaabu)
- [Arushad Ahmed (@dash8x)](http://arushad.org)
- [Mohamed Jailam](http://github.com/muhammedjailam)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
