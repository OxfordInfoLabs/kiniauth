<?php

namespace Kiniauth\Services\Security\SSOProvider;

use Kinikit\Core\Configuration\Configuration;

class GoogleSSOAuthenticator extends SSOAuthenticator {

    public function authenticate($data) {

        $clientId =  Configuration::readParameter("sso.google.clientId");
        $client = new \Google_Client(["client_id" => $clientId]);

        $payload = $client->verifyIdToken($data);
        if ($payload) {
            $userId = $payload["sub"];
            $email = $payload["email"] ?? null;
        } else {
            throw new \Exception("Invalid ID token");
        }

        return $email;

    }

}