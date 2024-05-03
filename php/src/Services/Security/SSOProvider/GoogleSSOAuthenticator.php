<?php

namespace Kiniauth\Services\Security\SSOProvider;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;

class GoogleSSOAuthenticator extends SSOAuthenticator {

    public function authenticate($data) {

        if (!$data)
            throw new \Exception("No access token supplied");

        $userDetails = file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $data);

        if (isset($userDetails["email"])) {
            return $payload["email"] ?? null;
        } else {
            throw new \Exception("No email linked");
        }

    }

}