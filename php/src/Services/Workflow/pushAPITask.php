<?php

namespace Kiniauth\Services\Workflow\Task;

use Kiniauth\Services\Workflow\Task\Task;

class pushAPITask implements Task {

    public function run($configuration): void {
        print_r("hello");
    }

}