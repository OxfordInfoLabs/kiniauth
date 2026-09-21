<?php

namespace Kiniauth\ValueObjects\Communication\Messaging;

enum MessageType: string {

    // Most average messages
    case General = "general";

    // Feedback messages
    case Feedback = "feedback";


}