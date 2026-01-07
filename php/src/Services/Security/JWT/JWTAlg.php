<?php

namespace Kiniauth\Services\Security\JWT;

enum JWTAlg {

    // None
    case none;

    // HMAC-based algorithms
    case HS256;
    case HS384;
    case HS512;

    // RSA-based algorithms
    case RS256;
    case RS384;
    case RS512;

    // ECDSA-based algorithms
    case ES256;
    case ES384;
    case ES512;
}