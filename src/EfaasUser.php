<?php

namespace Ncit\Efaas\Socialite;

use Laravel\Socialite\Two\User;

class EfaasUser extends User
{
    /**
     * The user's open id token.
     *
     * @var string
     */
    public $openIdToken;

    /**
     * passport number
     *
     * @var string
     */
    public $passportNumber;

    /**
     * country
     *
     * @var string
     */
    public $country;

    /**
     * country code
     *
     * @var string
     */
    public $countryCode;

    /**
     * mobile number
     *
     * @var string
     */
    public $mobile;

    /**
     * username
     *
     * @var string
     */
    public $username;
    
    /**
     * birth day
     *
     * @var Carbon\Carbon
     */
    public $birthDate;
    
    /**
     * permanent address
     *
     * @var array
     */
    public $permanentAddress;

    public function setOpenIdToken($openId)
    {
        $this->openIdToken = $openId;

        return $this;
    }

    /**
     * set avatar
     *
     * @param string $avatar
     * @return void
     */
    public function setAvatar(string $avatar)
    {
        $this->avatar = $avatar;
        return $this;
    }
}
