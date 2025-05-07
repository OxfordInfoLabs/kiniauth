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
     * If using Recaptcha v3, a score threshold for which this will be successful
     *
     * @var integer
     */
    private $recaptchaScoreThreshold;

    /**
     * Set test mode for testing
     * Null for off, and true/false for forced output
     *
     * @var string
     */
    public static $testMode = null;


    /**
     * GoogleRecaptchaProvider constructor.
     *
     * @param string $recaptchaSecretKey
     */
    public function __construct($recaptchaSecretKey = null, $recaptchaScoreThreshold = null) {
        $this->recaptchaSecretKey = $recaptchaSecretKey ?: Configuration::readParameter("recaptcha.secret.key");
        $this->recaptchaScoreThreshold = $recaptchaScoreThreshold ?: Configuration::readParameter("recaptcha.score.threshold");
    }

    /**
     * @param mixed|string|null $recaptchaSecretKey
     */
    public function setRecaptchaSecretKey($recaptchaSecretKey) {
        $this->recaptchaSecretKey = $recaptchaSecretKey;
    }

    /**
     * @param int|mixed|null $recaptchaScoreThreshold
     */
    public function setRecaptchaScoreThreshold($recaptchaScoreThreshold) {
        $this->recaptchaScoreThreshold = $recaptchaScoreThreshold;
    }


    /**
     * Verify a client side Captcha using captcha specific data
     *
     * @param mixed $verificationData
     * @param Request $request
     * @return boolean
     */
    public function verifyCaptcha($verificationData, $request = null) {

        // Check test mode is off
        if (!is_null(self::$testMode))
            return self::$testMode;

        $remoteIp = null;
        if ($request) {
            $remoteIp = $request->getRemoteIPAddress();
        }

        $recaptcha = new \ReCaptcha\ReCaptcha($this->recaptchaSecretKey);
        $response = $recaptcha->verify($verificationData, $remoteIp);

        $success = $response->isSuccess();

        if ($success && isset($this->recaptchaScoreThreshold)) {
            $success = ($response->getScore() ?? 0 >= $this->recaptchaScoreThreshold);
        }

        return $success;

    }
}
