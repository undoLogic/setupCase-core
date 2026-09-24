<?php

/*******
 * Add this to a CakePHP 4.x controller

// Need to manually target file since we are pushing all classes in the same Util class for simplicity / deployment
require_once ROOT . DS . 'src' . DS . 'Util' . DS . 'OfflineBox.php';
use App\Util\ClientKey;
use function App\Util\generate_client_key;
use function App\Util\generate_client_request;
use function App\Util\generate_nonce;
use function App\Util\verify_client_request;

public function testing() {

    $this->viewBuilder()->disableAutoLayout();

    try {
        echo "Produce valid request and verification\n";
        $clientKey = generate_client_key();
        $nonce = generate_nonce();
        $backupRequest = generate_client_request($clientKey, $nonce);

        verify_client_request($clientKey, $backupRequest);
        echo "Client request was good!\n";

        if (0) { //if you want to test the fail
            echo "\nNow tamper with the signature, should fail\n";
            $backupRequest->signature = base64_encode("a bad signature");
            verify_client_request($clientKey, $backupRequest);
            echo "Should not see this message!";
        }
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
    exit(1);
    }
exit(0);
}
 ******/

namespace App\Util;
use Exception;

const SHARED_SECRET = "a very secret string";
const NONCE_SIZE = 32;

class ClientKey
{
    private string $privateKey;
    private string $publicKey;

    public function __construct(string $privateKey, string $publicKey)
    {
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
}

class BackupRequestPayload
{
    public string $nonce;
    public string $sharedSecret;

    public function __construct(string $nonce, string $sharedSecret)
    {
        $this->nonce = $nonce;
        $this->sharedSecret = $sharedSecret;
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function getSharedSecret(): string
    {
        return $this->sharedSecret;
    }

    public static function fromData(array $data): BackupRequestPayload
    {
        $p = new BackupRequestPayload("", "");
        foreach ($data as $key => $value) $p->{$key} = $value;
        return $p;
    }
}

class BackupRequest
{
    public string $payload;
    public string $signature;

    public function __construct(string $payload, string $signature)
    {
        $this->payload = $payload;
        $this->signature = $signature;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }
}

function generate_client_key(): ClientKey
{
    /*
       EC keys requires PHP 7.0 or higher if you have lower versions use
       array(
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
       );
    */
    $new_key_pair = openssl_pkey_new(array(
        "curve_name" => 'prime256v1',
        "private_key_type" => OPENSSL_KEYTYPE_EC,
    ));

    if (!$new_key_pair) {
        $sslError = openssl_error_string();
        throw new Exception("failed key generation:" . $sslError);
    }

    $ok = openssl_pkey_export($new_key_pair, $private_key_pem);
    if (!$ok) {
        $sslError = openssl_error_string();
        throw new Exception("failed key exporting to pem:" . $sslError);
    }
    $details = openssl_pkey_get_details($new_key_pair);
    $public_key_pem = $details['key'];

    return new ClientKey($private_key_pem, $public_key_pem);
}

function generate_nonce(): string
{
    $randomVal = openssl_random_pseudo_bytes(NONCE_SIZE);
    if (!$randomVal) {
        $sslError = openssl_error_string();
        throw new Exception("failed nonce generation:" . $sslError);
    }

    return base64_encode($randomVal);
}

function validate_nonce(string $nonce): bool
{
    $decodedVal = base64_decode($nonce);
    if (!$decodedVal) {
        return false;
    }

    if (strlen($decodedVal) != NONCE_SIZE) {
        return false;
    }

    return true;
}

function generate_client_request(ClientKey $clientKey, string $nonce): BackupRequest
{
    $payload = new BackupRequestPayload($nonce, SHARED_SECRET);
    $encodedPayload = json_encode(get_object_vars($payload));

    $binarySignature = "";
    $ok = openssl_sign($encodedPayload, $binarySignature, $clientKey->getPrivateKey(), OPENSSL_ALGO_SHA512);
    if (!$ok) {
        $sslError = openssl_error_string();
        throw new Exception("failed signing request payload:" . $sslError);
    }

    $b64EncodedSignature = base64_encode($binarySignature);
    return new BackupRequest($encodedPayload, $b64EncodedSignature);
}

function verify_client_request(ClientKey $clientKey, BackupRequest $backupRequest)
{
    echo "Payload: " . $backupRequest->getPayload() . "\n";
    echo "Signature: " . $backupRequest->getSignature() . "\n";

    $binarySignature = base64_decode($backupRequest->getSignature());
    if (!$binarySignature) {
        throw new Exception("failed base64 decoding of signature");
    }

    $ok = openssl_verify($backupRequest->getPayload(), $binarySignature, $clientKey->getPublicKey(), OPENSSL_ALGO_SHA512);

    if ($ok != 1) {
        if ($ok == 0) {
            throw new Exception("failed signature verification of payload");
        }

        $sslError = openssl_error_string();
        throw new Exception("failed signature verification of payload: " . $sslError);
    }

    $data = json_decode($backupRequest->getPayload(), true);
    if (is_null($data)) {
        throw new Exception("failed decoding request payload");
    }

    $payload = BackupRequestPayload::fromData($data);
    $ok = validate_nonce($payload->getNonce());
    if (!$ok) {
        throw new Exception("failed nonce validation");
    }

    if ($payload->getSharedSecret() !== SHARED_SECRET) {
        throw new Exception("failed shared secret validation");
    }
}
