<?php

namespace Kiniauth\Test\Controllers\Internal;

use Kiniauth\Objects\Security\UserSummary;

class TestCallObject {

    /**
     * @param integer $int
     * @param string $string
     * @param float $float
     * @param boolean $boolean
     * @param int[] $intArray
     *
     * @return void
     */
    public function firstMethod($int, $string, $float, $boolean, $intArray) {
        return "Success";
    }


    /**
     * @param UserSummary $user
     * @param UserSummary[] $userArray
     * @return void
     */
    public function secondMethod($user, $userArray) {
        throw new \Exception("Hello world of fun and adventure");
    }


}