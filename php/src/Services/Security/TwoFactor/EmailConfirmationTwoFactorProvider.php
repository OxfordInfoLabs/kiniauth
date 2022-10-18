<?php

namespace Kiniauth\Services\Security\TwoFactor;


use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Objects\MetaData\ObjectStructuredData;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\MetaData\MetaDataService;
use Kinikit\Core\Util\StringUtils;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

class EmailConfirmationTwoFactorProvider implements TwoFactorProvider {

    /**
     * @var MetaDataService
     */
    private $metaDataService;

    /**
     * @var EmailService
     */
    private $emailService;


    /**
     * EmailConfirmationTwoFactorProvider constructor.
     *
     * @param EmailService $emailService
     * @param MetaDataService $metaDataService
     */
    public function __construct($emailService, $metaDataService) {
        $this->emailService = $emailService;
        $this->metaDataService = $metaDataService;
    }


    /**
     * Generate Two Factor if required
     *
     * @param User $pendingUser
     * @param mixed $twoFactorClientData
     *
     * @return mixed
     */
    public function generateTwoFactorIfRequired($pendingUser, $twoFactorClientData) {

        // Check to see whether there is a structured data object for the user and passed data.
        try {
            $this->metaDataService->getStructuredDataItem(User::class, $pendingUser->getId(), "2FAAuthorisedClient", $twoFactorClientData);
            return false;
        } catch (ObjectNotFoundException $e) {

            // Generate a new 6 digit code
            $code = rand(100001, 999999);

            // Send the two factor email
            $email = new UserTemplatedEmail($pendingUser->getId(), "security/twofactor/two-factor-code", ["code" => $code]);
            $this->emailService->send($email, null, $pendingUser->getId());

            return $code;

        }
    }


    /**
     * Authenticate a two factor authentication based on a pending user, pending two factor data
     * as returned from the generate method and any client passed two factor login data
     *
     * @param User $pendingUser
     * @param mixed $pendingTwoFactorData
     * @param mixed $twoFactorLoginData
     *
     * @return mixed
     */
    public function authenticate($pendingUser, $pendingTwoFactorData, $twoFactorLoginData) {

        if ($pendingTwoFactorData == $twoFactorLoginData) {
            $newClientAuthentication = StringUtils::generateRandomString(32, true, true, false);
            $this->metaDataService->updateStructuredDataItems([
                new ObjectStructuredData(User::class, $pendingUser->getId(), "2FAAuthorisedClient", $newClientAuthentication, null)
            ]);
            return $newClientAuthentication;
        } else {
            return false;
        }

    }
}