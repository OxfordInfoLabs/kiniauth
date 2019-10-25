<?php

namespace Kiniauth\Objects\Workflow;

use Kinikit\Core\Util\StringUtils;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Generic storage for pending actions on an account.  An accountId and string type are mandatory.
 * Other data is optional.
 *
 * Class PendingAction
 *
 * @table ka_pending_action
 * @generate
 *
 * @interceptor \Kiniauth\Objects\Workflow\PendingActionInterceptor
 */
class PendingAction extends ActiveRecord {

    /**
     * Primary key id.
     *
     * @var integer
     */
    private $id;


    /**
     * Object id (required)
     *
     * @required
     * @var integer
     */
    private $objectId;


    /**
     * The type of action - a string identifier (required)
     *
     * @required
     * @var string
     */
    private $type;


    /**
     * Expiry date for this pending action - this is required and defaults to
     * 24hrs
     *
     * @required
     * @var \DateTime
     */
    private $expiryDateTime;



    /**
     * A string identifier for identifying this action - automatically generated as
     * a secure 16 digit random string
     *
     * @var string
     */
    private $identifier;


    /**
     * Additional arbitrary data stored against this action, useful when these are fulfilled
     * in certain use cases.
     *
     * @json
     * @var string
     */
    private $data;


    /**
     * PendingAction constructor.
     *
     * Expiry can either be supplied explicitly as a date or an expiry offset in DateTimeInterval string format
     * e.g. P1D
     *
     * @param integer $accountId
     * @param string $type
     * @param integer $objectId
     * @param string $data
     * @param string $expiryOffset
     * @param \DateTime $expiryDateTime
     */
    public function __construct($type, $objectId, $data = null, $expiryOffset = null, $expiryDateTime = null) {
        $this->type = $type;
        $this->objectId = $objectId;

        if (!$expiryOffset) $expiryOffset = "P1D";

        if ($expiryDateTime) {
            $this->expiryDateTime = $expiryDateTime;
        } else {
            $expiryDate = new \DateTime();
            $expiryDate->add(new \DateInterval($expiryOffset));
            $this->expiryDateTime = $expiryDate;
        }



        $this->data = $data;
        $this->identifier = StringUtils::generateRandomString(16);
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }


    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return \DateTime
     */
    public function getExpiryDateTime() {
        return $this->expiryDateTime;
    }

    /**
     * @return int
     */
    public function getObjectId() {
        return $this->objectId;
    }

    /**
     * @return string
     */
    public function getIdentifier() {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getData() {
        return $this->data;
    }


}
