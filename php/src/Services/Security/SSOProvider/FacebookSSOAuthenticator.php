<?php

namespace Kiniauth\Services\Security\SSOProvider;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Request;

class FacebookSSOAuthenticator extends SSOAuthenticator {

    /**
     * @var HttpRequestDispatcher
     */
    private $requestDispatcher;

    /**
     * @param HttpRequestDispatcher $requestDispatcher
     */
    public function __construct(HttpRequestDispatcher $requestDispatcher) {
        $this->requestDispatcher = $requestDispatcher;
    }


    /**
     * Authenticate the tokens with Facebook, and login the user,
     * creating an account if necessary.
     *
     * @param mixed $data
     * @return string
     */
    public function authenticate($data) {

        $accessToken = $this->exchangeCodeForAccessToken($data);

        // Save Token - should persist until expiry
        [$tokenExpiry, $userID] = $this->inspectAccessToken($accessToken);

        // Get user's name and email
        [$name, $email] = $this->getUserInfo($userID, $accessToken);

        return $email;

    }

    /**
     * Exchange a code received for and access token
     *
     * @param string $code
     * @return string $token
     */
    private function exchangeCodeForAccessToken(string $code): string {

        $appId = Configuration::readParameter("sso.facebook.appId");
        $appSecret = Configuration::readParameter("sso.facebook.appSecret");
        $redirectURI = Configuration::readParameter("sso.facebook.redirectURI");

        $url = "https://graph.facebook.com/v19.0/oauth/access_token?client_id=$appId&redirect_uri=$redirectURI&client_secret=$appSecret&code=$code";
        $request = new Request($url);

        $response = $this->requestDispatcher->dispatch($request);
        $responseContent = json_decode($response->getBody(), true);

        if ($e = $responseContent["error"] ?? null)
            throw new \Exception($e["message"]);

        return $responseContent["access_token"];

    }

    /**
     * Check the access token is valid
     *
     * @param $accessToken
     * @return string[]
     */
    private function inspectAccessToken($accessToken) {

        $appId = Configuration::readParameter("sso.facebook.appId");
        $appSecret = Configuration::readParameter("sso.facebook.appSecret");

        $url = "https://graph.facebook.com/debug_token?input_token=$accessToken&access_token=$appId|$appSecret";
        $request = new Request($url, Request::METHOD_GET);

        $response = $this->requestDispatcher->dispatch($request);
        $responseData = json_decode($response->getBody(), true)["data"];

        if (!$responseData["is_valid"]) {
            throw new \Exception("Invalid access token");
        }


        // ToDo: verify email permission accepted

        $tokenExpiry = $responseData["expires_at"];
        $userId = $responseData["user_id"];

        return [$tokenExpiry, $userId];

    }

    /**
     * @param $personId
     * @param string
     * @return array
     */
    private function getUserInfo($personId, $token) {

        $url = "https://graph.facebook.com/v19.0/$personId?fields=name,email&access_token=$token";
        $request = new Request($url, Request::METHOD_GET);
        $response = $this->requestDispatcher->dispatch($request);
        $data = json_decode($response->getBody(), true);

        $name = $data["name"];
        $email = $data["email"] ?? null;

        if (!$email) {
            throw new \Exception("No email linked to the account");
        }

        return [$name, $email];

    }
}