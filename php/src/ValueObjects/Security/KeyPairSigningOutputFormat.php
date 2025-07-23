<?php

namespace Kiniauth\ValueObjects\Security;

enum KeyPairSigningOutputFormat: string {
    case Hex = "hex";
    case Base64 = "base64";
}
