<?php

namespace Kiniauth\Services\Security\JWT;

/**
 * Currently only supports the HS algorithm class
 */
class JWTManager {

    private ?string $secretKey;

    private JWTAlg $alg;

    /**
     * @param JWTAlg $alg
     * @param string $secretKey
     */
    public function __construct(JWTAlg $alg = JWTAlg::HS256, ?string $secretKey = null) {

        if (in_array($alg, [JWTAlg::HS256, JWTAlg::HS384, JWTAlg::HS512])) {
            if (empty($secretKey)) {
                throw new \InvalidArgumentException("A secret key must be provided for HMAC algorithms (HS256, HS384, HS512).");
            }
        }

        $this->secretKey = $secretKey;
        $this->alg = $alg;
    }

    public function createToken(array $payload): string {

        $encodedHeader = $this->base64UrlEncode(
            json_encode([
                "alg" => $this->alg->name,
                "typ" => "JWT"
            ])
        );

        $encodedPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = match ($this->alg) {
            JWTAlg::none => "",
            JWTAlg::HS256 => $this->signHS("sha256", $encodedHeader, $encodedPayload),
            JWTAlg::HS384 => $this->signHS("sha384", $encodedHeader, $encodedPayload),
            JWTAlg::HS512 => $this->signHS("sha512", $encodedHeader, $encodedPayload),
            JWTAlg::RS256 => throw new \Exception('To be implemented'),
            JWTAlg::RS384 => throw new \Exception('To be implemented'),
            JWTAlg::RS512 => throw new \Exception('To be implemented'),
            JWTAlg::ES256 => throw new \Exception('To be implemented'),
            JWTAlg::ES384 => throw new \Exception('To be implemented'),
            JWTAlg::ES512 => throw new \Exception('To be implemented'),
        };

        return $encodedHeader . "." . $encodedPayload . "." . $signature;

    }

    public function validateToken(string $token): bool {

        // Check token format (must be A.B.C)
        if (substr_count($token, ".") !== 2) {
            return false;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = explode(".", $token);

        $header = json_decode($this->base64UrlDecode($encodedHeader), true);
        $alg = $header["alg"] ?? null;

        // Validate algorithm
        if ($alg !== $this->alg->name) {
            throw new \Exception("Algorithm does not match expected");
        }

        // Validate signature
        $signature = match ($this->alg) {
            JWTAlg::HS256 => $this->signHS("sha256", $encodedHeader, $encodedPayload),
            JWTAlg::HS384 => $this->signHS("sha384", $encodedHeader, $encodedPayload),
            JWTAlg::HS512 => $this->signHS("sha512", $encodedHeader, $encodedPayload),
            default => throw new \Exception('Unsupported algorithm'),
        };

        return $encodedSignature === $signature;

    }

    public function decodeToken(string $token): array {
        [, $encodedPayload,] = explode(".", $token);
        $payload = $this->base64UrlDecode($encodedPayload);
        return json_decode($payload, true);
    }

    private function base64UrlEncode($data) {
        $base64 = base64_encode($data);
        $base64Url = strtr($base64, '+/', '-_');
        return rtrim($base64Url, '=');
    }

    private function base64UrlDecode($data) {
        $base64 = strtr($data, '-_', '+/');
        $remainder = strlen($base64) % 4;

        if ($remainder)
            $base64 = str_pad($base64, 4 - $remainder, "=", STR_PAD_RIGHT);

        return base64_decode($base64);
    }

    private function signHS(string $algorithm, string $encodedHeader, string $encodedPayload): string {
        return $this->base64UrlEncode(
            hash_hmac(
                algo: $algorithm,
                data: $encodedHeader . "." . $encodedPayload,
                key: $this->secretKey,
                binary: true
            )
        );
    }
}