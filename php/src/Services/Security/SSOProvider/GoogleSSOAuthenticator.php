<?php

namespace Kiniauth\Services\Security\SSOProvider;

class GoogleSSOAuthenticator implements SSOAuthenticator {

    public function authenticate($data) {

        if (!$data)
            throw new \Exception("No access token supplied");

        try {
            $userDetails = file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $data);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $userDetails = json_decode($userDetails, true);

        if (isset($userDetails["email"])) {
            return $userDetails["email"];
        } else {
            throw new \Exception("No email linked");
        }

    }

}