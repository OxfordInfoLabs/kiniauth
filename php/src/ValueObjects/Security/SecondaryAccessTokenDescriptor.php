<?php


namespace Kiniauth\ValueObjects\Security;


class SecondaryAccessTokenDescriptor {

    /**
     * @var string
     */
    private $userAccessToken;

    /**
     * @var string
     */
    private $secondaryToken;

    /**
     * @return string
     */
    public function getUserAccessToken() {
        return $this->userAccessToken;
    }

    /**
     * @param string $userAccessToken
     */
    public function setUserAccessToken($userAccessToken) {
        $this->userAccessToken = $userAccessToken;
    }

    /**
     * @return string
     */
    public function getSecondaryToken() {
        return $this->secondaryToken;
    }

    /**
     * @param string $secondaryToken
     */
    public function setSecondaryToken($secondaryToken) {
        $this->secondaryToken = $secondaryToken;
    }


}





