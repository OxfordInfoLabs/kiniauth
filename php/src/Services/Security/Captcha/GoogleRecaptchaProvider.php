<?php

namespace Kiniauth\Services\Security\Captcha;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\MVC\Request\Request;

class GoogleRecaptchaProvider implements CaptchaProvider {


    /**
     * Recaptcha secret key
     *
     * @var string
     */
    private $recaptchaSecretKey;

    /**
     * GoogleRecaptchaProvider constructor.
     *
     * @param string $recaptchaSecretKey
     */
    public function __construct($recaptchaSecretKey = null) {
        $this->recaptchaSecretKey = $recaptchaSecretKey ?
            $recaptchaSecretKey : Configuration::readParameter("recaptcha.secret.key");
    }

    /**
     * Verify a client side Captcha using captcha specific data
     *
     * @param mixed $verificationData
     * @param Request $request
     * @return boolean
     */
    public function verifyCaptcha($verificationData, $request = null) {

        $remoteIp = null;
        if ($request) {
            $remoteIp = $request->getRemoteIPAddress();
        }

        $recaptcha = new \ReCaptcha\ReCaptcha($this->recaptchaSecretKey);
        $response = $recaptcha->verify($verificationData, $remoteIp);

        return $response->isSuccess();

    }
}
