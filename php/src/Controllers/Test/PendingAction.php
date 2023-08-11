<?php

namespace Kiniauth\Controllers\Test;

class PendingAction {


    /**
     * @http GET /last
     *
     * @return void
     */
    public function getLastPendingAction() {
        $pendingActions = \Kiniauth\Objects\Workflow\PendingAction::filter("ORDER BY id DESC LIMIT 1");
        return $pendingActions[0] ?? null;
    }


}