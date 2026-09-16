<?php


namespace Kiniauth\Objects\Communication\Messaging;

use Kinikit\Persistence\ORM\ActiveRecord;


/**
 * Summary class for listing of messages
 *
 * @table ka_message_thread
 */
class MessageThreadSummary extends ActiveRecord {


    /**
     * Unique primary key
     *
     * @autoIncrement
     */
    protected $id;

    /**
     * MessageSummary constructor.
     *
     * @param ?int $id
     */
    public function __construct($id = null) {

        $this->id = $id;

    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return void
     */
    public function setId(int $id): void {
        $this->id = $id;
    }




}


