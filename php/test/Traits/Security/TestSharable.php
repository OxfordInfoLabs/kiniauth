<?php

namespace Kiniauth\Test\Traits\Security;

use Kiniauth\Traits\Security\Sharable;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table test_sharable
 * @generate
 */
class TestSharable extends ActiveRecord {


    /**
     * @param int $id
     * @param string $name
     */
    public function __construct(
        private ?int $id, private ?string $name
    ) {
    }

    /**
     * @return int
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): ?string {
        return $this->name;
    }




    use Sharable;
}