<?php

namespace Kiniauth\Test\Services\Security\SSO;

use Kiniauth\Services\Security\SSOProvider\FacebookSSOAuthenticator;
use Kiniauth\Test\TestBase;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\HTTP\Response\Headers;
use Kinikit\Core\HTTP\Response\Response;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinikit\Core\Testing\MockObjectProvider;

include_once "autoloader.php";

class FacebookSSOAuthenticatorTest extends TestBase {

    /**
     * @var HttpRequestDispatcher
     */
    private $requestDispatcher;

    /**
     * @var FacebookSSOAuthenticator
     */
    private $authenticator;

    public function setUp(): void {
        $this->requestDispatcher = MockObjectProvider::instance()->getMockInstance(HttpRequestDispatcher::class);
        $this->authenticator = new FacebookSSOAuthenticator($this->requestDispatcher);
    }

    public function testCanGetUserEmailAddressUsingFacebookSSO() {

        $code = 98765;

        $appId = Configuration::readParameter("sso.facebook.appId");
        $appSecret = Configuration::readParameter("sso.facebook.appSecret");
        $redirectURI = Configuration::readParameter("sso.facebook.redirectURI");

        $accessToken = 5555;
        $personId = 9999;


        $tokenExchangeRequest = new Request("https://graph.facebook.com/v19.0/oauth/access_token?client_id=$appId&redirect_uri=$redirectURI&client_secret=$appSecret&code=$code");
        $tokenExchangeResponse = new Response(
            new ReadOnlyStringStream('{"access_token":5555,"token_type":"bearer","expires_in":123456}'),
            200, new Headers(), $tokenExchangeRequest);
        $this->requestDispatcher->returnValue("dispatch", $tokenExchangeResponse, [$tokenExchangeRequest]);

        $inspectTokenRequest = new Request("https://graph.facebook.com/debug_token?input_token=$accessToken&access_token=$appId|$appSecret", Request::METHOD_GET);
        $inspectTokenResponse = new Response(
            new ReadOnlyStringStream('{"data":{"is_valid":true,"user_id":9999,"expires_at":1712934610}}'),
            200, new Headers(), $inspectTokenRequest);
        $this->requestDispatcher->returnValue("dispatch", $inspectTokenResponse, [$inspectTokenRequest]);

        $userInfoRequest = new Request("https://graph.facebook.com/v19.0/$personId?fields=name,email&access_token=$accessToken", Request::METHOD_GET);
        $userInfoResponse = new Response(
            new ReadOnlyStringStream('{"name":"Sam Davis","email":"sam@test.com"}'),
            200, new Headers(), $userInfoRequest);
        $this->requestDispatcher->returnValue("dispatch", $userInfoResponse, [$userInfoRequest]);

        $email = $this->authenticator->authenticate($code);


        $this->assertEquals("sam@test.com", $email);

    }

    public function testCanHandleInvalidCodeFromClient() {

        $code = 98765;

        $appId = Configuration::readParameter("sso.facebook.appId");
        $appSecret = Configuration::readParameter("sso.facebook.appSecret");
        $redirectURI = Configuration::readParameter("sso.facebook.redirectURI");

        $tokenExchangeRequest = new Request("https://graph.facebook.com/v19.0/oauth/access_token?client_id=$appId&redirect_uri=$redirectURI&client_secret=$appSecret&code=$code");
        $tokenExchangeResponse = new Response(
            new ReadOnlyStringStream('{"error":{"message":"This authorization code has been used.","type":"OAuthException","code":100}}'),
            200, new Headers(), $tokenExchangeRequest);
        $this->requestDispatcher->returnValue("dispatch", $tokenExchangeResponse, [$tokenExchangeRequest]);


        try {
            $this->authenticator->authenticate($code);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals("This authorization code has been used.", $e->getMessage());
        }

    }

    public function testTempTest() {

        $authenticator = Container::instance()->get(FacebookSSOAuthenticator::class);
        $authenticator->authenticate("");

    }

}