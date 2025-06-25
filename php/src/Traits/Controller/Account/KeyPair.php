<?php

namespace Kiniauth\Traits\Controller\Account;

use Kiniauth\Objects\Security\KeyPairSummary;
use Kiniauth\Services\Security\KeyPairService;
use Kiniauth\ValueObjects\Util\LabelValue;

trait KeyPair {

    public function __construct(private KeyPairService $keyPairService) {
    }



    /**
     * @http GET /$id
     *
     * @param $id
     * @return KeyPairSummary
     */
    public function getKeyPair($id) {
        return $this->keyPairService->getKeyPair($id);
    }



    /**
     * @http GET /
     *
     * @param $projectKey
     * @return LabelValue[]
     */
    public function listKeyPairs($projectKey = null) {
        return $this->keyPairService->listKeyPairs($projectKey);
    }


    /**
     * @http POST /
     *
     * @param $description
     * @param $projectKey
     *
     * @return int
     */
    public function generateKeyPair($description, $projectKey = null) {
        return $this->keyPairService->generateKeyPair($description, $projectKey);
    }




    /**
     * @http DELETE /
     *
     * @param $id
     */
    public function deleteKeyPair($id){
        $this->keyPairService->deleteKeyPair($id);
    }



}