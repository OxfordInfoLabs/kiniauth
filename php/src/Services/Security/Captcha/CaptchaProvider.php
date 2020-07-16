<?php

namespace Kiniauth\Services\Security\Captcha;

use Kinikit\MVC\Request\Request;

/**
 * Server side captcha verification provider
 *
 * @defaultImplementation \Kiniauth\Services\Security\Captcha\GoogleRecaptchaProvider
 *
 * Interface CaptchaProvider
 */
interface CaptchaProvider {

    /**
     * Verify a client side Captcha using captcha specific data
     *
     * @param mixed $verificationData
     * @param Request $request
     * @return boolean
     */
    public function verifyCaptcha($verificationData, $request = null);

}
