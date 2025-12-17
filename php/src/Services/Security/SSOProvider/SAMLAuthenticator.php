<?php

namespace Kiniauth\Services\Security\SSOProvider;

use Kinikit\Core\Exception\AccessDeniedException;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Response;
use OneLogin\Saml2\Settings;

class SAMLAuthenticator {

    private Auth $auth;

    private Settings $settings;

    public function __construct(Settings $settings, Auth $auth) {
        $this->settings = $settings;
        $this->auth = $auth;
    }

    public function initialise() {
        return $this->auth->getSSOurl();
    }

    public function authenticate(mixed $data) {
        $response = new Response($this->settings, $data["SAMLResponse"]);
        if ($response->isValid()) {
            return $response->getAttributes()["email"][0] ?? null;
        } else {
            throw new AccessDeniedException();
        }
    }

    public function getSettings() {
        return $this->settings->getSPMetadata();
    }


}