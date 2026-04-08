<?php

namespace Kiniauth\Services\Security\SSOProvider;

use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Logging\Logger;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Response;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\Utils;

class SAMLAuthenticator {

    private Auth $auth;

    private Settings $settings;

    public function __construct(Settings $settings, Auth $auth) {
        $this->settings = $settings;
        $this->auth = $auth;
    }

    public function initialise() {
        $samlReq = $this->auth->buildAuthnRequest($this->settings, true, false, true);
        $reqString = urlencode($samlReq->getRequest());

        $url = $this->auth->getSSOurl() . "&SAMLRequest=" . $reqString;

        return $url;
    }

    public function authenticate(mixed $data) {
        $response = new Response($this->settings, $data["SAMLResponse"]);

        // Set the incoming base URL to allow for proxying
        $document = $response->document;
        if ($document->documentElement->hasAttribute('Destination')) {
            $destination = $document->documentElement->getAttribute('Destination');
            Utils::setBaseURL($destination);
        }

        if ($response->isValid()) {
            return $response->getAttributes()["email"][0] ?? null;
        } else {
            Logger::log($response->getError());
            throw new AccessDeniedException();
        }
    }

    public function getSettings() {
        return $this->settings->getSPMetadata();
    }


}