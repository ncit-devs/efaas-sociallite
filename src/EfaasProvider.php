<?php

namespace Ncit\Efaas\Socialite;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Ncit\Efaas\Socialite\EfaasUser;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\InvalidStateException;

class EfaasProvider extends AbstractProvider implements ProviderInterface
{
    const ONE_TAP_LOGIN_KEY = 'efaas_login_code';

    protected $stateless = true;

    protected $enc_type = PHP_QUERY_RFC1738;

    /**
     * Indicates if Efaas routes will be registered.
     *
     * @var bool
     */
    public static $registersOneTapRoute = true;

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['openid', 'efaas.profile'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * Get correct endpoint for API
     *
     * @param $key
     * @param null $default
     * @return string
     */
    protected function config($key, $default = null)
    {
        return config("services.efaas.$key", $default);
    }

    /**
     * Get correct endpoint for API
     *
     * @return string
     */
    protected function getEfaasUrl($path = '')
    {
        $url = rtrim( $this->config('server_url'), '/');
        $path = ltrim($path, '/');
        
        if ($path) {
            $url .= "/$path";
        }

        return $url;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getEfaasUrl('authorize'), $state);
    }

    /**
     * Get the login code from the request.
     *
     * @return string
     */
    protected function getLoginCode()
    {
        return $this->request->input(self::ONE_TAP_LOGIN_KEY);
    }

    /**
     * Get the GET parameters for the code request.
     *
     * @param  string|null  $state
     * @return array
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'response_type' => 'code id_token',
            'response_mode' => 'form_post',
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'nonce' => $this->getState()
        ];

        // add the efaas login code if provided
        if ($login_code = $this->getLoginCode()) {
            $fields['acr_values'] = self::ONE_TAP_LOGIN_KEY.':'.$login_code;
        }

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        return array_merge($fields, $this->parameters);
    }


    /**
    * Get the code from the request.
    *
    * @return string
    */
    protected function getCode()
    {
        return $this->request->input('code');
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $this->user = $this->mapUserToObject($this->getUserByToken(
            $token = Arr::get($response, 'access_token')
        ));

        if ($this->user->avatar) {
            $photoResponse = Http::withToken(Arr::get($response, 'access_token'))->get($this->user->avatar);
            
            if ($photoResponse->ok()) {
                $this->user->setAvatar($photoResponse->json('data.photo'));
            }
        }

        return $this->user->setToken($token)
                    ->setOpenIdToken(Arr::get($response, 'id_token'))
                    ->setRefreshToken(Arr::get($response, 'refresh_token'))
                    ->setExpiresIn(Arr::get($response, 'expires_in'))
                    ->setApprovedScopes(explode($this->scopeSeparator, Arr::get($response, 'scope', '')));
    }


    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param array $user
     * @return User
     */
    protected function mapUserToObject(array $user)
    {
        $socialteUser = (new EfaasUser)->setRaw($user)->map([
            'id' => Arr::get($user, 'sub'),
            'nickname' => Arr::get($user, 'full_name'),
            'name' => Arr::get($user, 'full_name'),
            'name_dhivehi' => Arr::get($user, 'full_name_dhivehi'),
            'first_name' => Arr::get($user, 'first_name'),
            'first_name_dhivehi' => Arr::get($user, 'first_name_dhivehi'),
            'middle_name' => Arr::get($user, 'middle_name'),
            'middle_name_dhivehi' => Arr::get($user, 'middle_name_dhivehi'),
            'last_name' => Arr::get($user, 'last_name'),
            'last_name_dhivehi' => Arr::get($user, 'last_name_dhivehi'),
            'gender' => Arr::get($user, 'gender') == 'F' ? 'female' : 'male',
            'user_type' => Arr::get($user, 'user_type_description'),
            'is_verified' => (bool) Arr::get($user, 'verified'),
            'verification_type' => Arr::get($user, 'verification_type'),
            'updated_at' =>  Carbon::parse(Arr::get($user, 'updated_at')),
        ]);

        $socialteUser->username = Arr::get($user, 'idnumber');

        if (array_key_exists('full_name', $user)) {
            $socialteUser->name = Arr::get($user, 'full_name');
        }

        if (array_key_exists('email', $user)) {
            $socialteUser->email = Arr::get($user, 'email');
        }

        if (array_key_exists('mobile', $user)) {
            $socialteUser->mobile = Arr::get($user, 'mobile');
        }

        if (array_key_exists('photo', $user)) {
            $socialteUser->avatar = Arr::get($user, 'photo');
        }

        if (array_key_exists('passport_number', $user)) {
            $socialteUser->passportNumber = Arr::get($user, 'passport_number');
        }
        if (array_key_exists('is_workpermit_active', $user)) {
            $socialteUser->attributes['work_permit_status'] = Arr::get($user, 'is_workpermit_active') == 'False' ? 'inactive' : 'active';
        }

        if (array_key_exists('permanent_address', $user)) {
            $socialteUser->permanentAddress = json_decode(Arr::get($user, 'permanent_address'), true);
        }

        if (array_key_exists('country_name', $user)) {
            $socialteUser->country = Arr::get($user, 'country_name');
            $socialteUser->countryCode = Arr::get($user, 'country_code_alpha3');
        }
        
        if (array_key_exists('birthdate', $user)) {
            $socialteUser->birthDate = Carbon::parse(Arr::get($user, 'birthdate'));
        }

        return $socialteUser;
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return $this->getEfaasUrl('token');
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenFields($code)
    {
        return Arr::add(
            parent::getTokenFields($code), 'grant_type', 'authorization_code'
        );
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param string $token
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post($this->getEfaasUrl('userinfo'), [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return  json_decode($response->getBody()->getContents(), true);
    }

    /**
     * It calls the end-session endpoint of the OpenID Connect provider to notify the OpenID
     * Connect provider that the end-user has logged out of the relying party site
     * (the client application).
     *
     * @param string $access_token ID token (obtained at login)
     * @param string|null $redirect URL to which the RP is requesting that the End-User's User Agent
     * be redirected after a logout has been performed. The value MUST have been previously
     * registered with the OP. Value can be null.
     * https://github.com/jumbojett/OpenID-Connect-PHP/blob/master/src/OpenIDConnectClient.php
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logOut($idToken, $redirect)
    {
        $params = [
            'id_token_hint' => $idToken
        ];

        if ($redirect) {
            $params['post_logout_redirect_uri'] = $redirect;
        }

        $redirectUri = $this->getEfaasUrl('endsession?'. Arr::query($params));

        return redirect()->to($redirectUri);
    }

    /**
     * Get a Social User instance from a known auth code.
     *
     * @param  string  $code
     * @return \Laravel\Socialite\Two\User
     */
    public function userFromCode($code)
    {
        $response = $this->getAccessTokenResponse($code);

        $token = Arr::get($response, 'access_token');

        return $this->userFromToken($token);
    }

    /**
     * Configure Efaas to not register its routes.
     *
     * @return void
     */
    public static function ignoreRoutes()
    {
        static::$registersOneTapRoute = false;
    }
}
