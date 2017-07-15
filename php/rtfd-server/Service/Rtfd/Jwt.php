<?php
/**
 * Jwt Module
 */


/**
 * Widget: Json Web Token
 */
class Rtfd_Jwt {
    /**
     * Json Web Token generator
     *
     * @param array $payload
     * @param string $key
     * @param string $alg
     * @return string
     */
    public static function generate_token(array $payload, $key, $alg = _rtfd_default_jwt_alg_) {
        // convert to upper case
        $alg = strtoupper($alg);
        // build JWT header
        $header = array(
            'typ' => 'JWT',
            'alg' => $alg
        );
        // encode header
        $encode_header = self::urlsafe_base64_encode(json_encode($header));
        // encode payload
        $encode_payload = self::urlsafe_base64_encode(json_encode($payload));
        // signature input
        $signature_source = join('.', array($encode_header, $encode_payload));
        // calc signature
        $signature = self::calc_signature($signature_source, $key, $alg);
        // encode signature
        $encode_signature = self::urlsafe_base64_encode($signature);
        // header, payload, signature assembled together using '.'
        return join('.', array($encode_header, $encode_payload, $encode_signature));
    }

    /**
     * Json Web Token decode
     *
     * @param string $jwt
     * @param string $key
     * @param bool $verify
     * @return array
     * @throws Rtfd_Exception_FatalError
     */
    public static function token_decode($jwt, $key, $verify = true) {
        // explode header.payload.signature
        $segments = explode('.', $jwt);
        // check segments count
        if (count($segments) !== 3) {
            throw new Rtfd_Exception_FatalError("Wrong number of segments",
                'Rtfd:Jwt:token_decode');
        }
        // all segments
        list($encode_header, $encode_payload, $encode_signature) = $segments;
        // decode header
        $header = json_decode(self::urlsafe_base64_decode($encode_header));
        if ($header === null) {
            // decode error
            throw new Rtfd_Exception_FatalError("Invalid segment encoding of header",
                'Rtfd:Jwt:token_decode');
        }
        // decode payload
        $payload = json_decode(self::urlsafe_base64_decode($encode_payload), true);
        if ($payload === null) {
            // decode error
            throw new Rtfd_Exception_FatalError("Invalid segment encoding of payload",
                'Here:Widget:Jwt:token_decode');
        }
        // decode signature
        $signature = self::urlsafe_base64_decode($encode_signature);
        // verify signature
        if ($verify === true) {
            if (empty($header->alg)) {
                throw new Rtfd_Exception_FatalError("JWT header invalid, empty algorithm",
                    'Rtfd:Jwt:token_decode');
            }
            // validate signature is correct
            if (!self::_validate_signature(
                // calc signature source
                join('.', array($encode_header, $encode_payload)), $key, $header->alg,
                // verify signature
                $signature)) {
                //-----------------------------------------------------------------
                throw new Rtfd_Exception_FatalError("Signature Validation failed",
                    'Rtfd:Jwt:token_decode');
            }
        }
        // validate correct, payload is safe
        return $payload;
    }

    /**
     * urlsafe base64 encode
     *
     * @param string $payload
     * @return mixed|string
     */
    public static function urlsafe_base64_encode($payload) {
        $b64_result = base64_encode($payload);
        /**
         * '+'  => '-',
         * '/'  => '_',
         * '\r' => '',
         * '\n' => '',
         * '='  => ''
         */
        $b64_result = str_replace(array('+', '/', '\r', '\n', '='), array('-', '_'), $b64_result);
        return $b64_result;
    }

    /**
     * urlsafe base64 decode
     *
     * @param string $b64_result
     * @return bool|string
     */
    public static function urlsafe_base64_decode($b64_result) {
        /**
         * '-' => '+',
         * '_' => '/'
         */
        $b64_result = str_replace(array('-', '_'), array('+', '/'), $b64_result);
        return base64_decode($b64_result);
    }

    /**
     * @param string $source
     * @param string $key
     * @param string $alg
     * @return string
     * @throws Rtfd_Exception_FatalError
     */
    public static function calc_signature($source, $key, $alg) {
        switch ($alg) {
            case 'HS256':
                return hash_hmac('sha256', $source, $key, true);
            case 'HS384':
                return hash_hmac('sha384', $source, $key, true);
            case 'HS512':
                return hash_hmac('sha512', $source, $key, true);
            case 'RS256':
                return self::_generate_rsa_signature($source, $key, OPENSSL_ALGO_SHA256);
            case 'RS384':
                return self::_generate_rsa_signature($source, $key, OPENSSL_ALGO_SHA384);
            case 'RS512':
                return self::_generate_rsa_signature($source, $key, OPENSSL_ALGO_SHA512);
            default:
                throw new Rtfd_Exception_FatalError("Unsupported or invalid signing algorithm",
                    'Rtfd:Jwt:calc_signature');
        }
    }

    /**
     * @param string $source
     * @param string $key
     * @param int $alg
     * @return string
     * @throws Rtfd_Exception_FatalError
     */
    private static function _generate_rsa_signature($source, $key, $alg) {
        /**
         * computes a signature for the specified data by generating a cryptographic digital
         * signature using the private key associated with priv_key_id.
         */
        if (!openssl_sign($source, $signature, $key, $alg)) {
            throw new Rtfd_Exception_FatalError("generate rsa signature error",
                'Rtfd:Jwt:_generate_rsa_signature');
        }
        return $signature;
    }

    /**
     * validate signature is correct
     *
     * @param string $source
     * @param string $key
     * @param string $alg
     * @param string $signature
     * @return bool
     * @throws Rtfd_Exception_FatalError
     */
    private static function _validate_signature($source, $key, $alg, $signature) {
        switch ($alg) {
            case 'HS256':
            case 'HS384':
            case 'HS512':
                return self::calc_signature($source, $key, $alg) === $signature;
            case 'RS256':
                return boolval(openssl_verify($source, $signature, $key, OPENSSL_ALGO_SHA256));
            case 'RS384':
                return boolval(openssl_verify($source, $signature, $key, OPENSSL_ALGO_SHA384));
            case 'RS512':
                return boolval(openssl_verify($source, $signature, $key, OPENSSL_ALGO_SHA512));
            default:
                throw new Rtfd_Exception_FatalError("Unsupported or invalid signing algorithm",
                    'Rtfd:Jwt:_validate_signature');
        }
    }
}
