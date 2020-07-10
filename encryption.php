<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 0);

/**
 * calling from here (this is request money method) // Example
 * @param type $arrFundData
 * @return type
 */
//function requestMoney($arrFundData) {

$arrData = [];

/* Mandatory Fields */
$arrData["fromEntityId"] = 'JUVOC201610';
$arrData["toEntityId"] = 'JUVOC2016281';
$arrData["productId"] = "GENERAL";
$arrData["description"] = 'Test description';
$arrData["amount"] = '2.00';
$arrData["transactionType"] = "C2C";
$arrData["transactionOrigin"] = "MOBILE";

//$this->setTenant("JUVO");
//$this->setMethod("POST");

$symmKey = generateSymmetricKey();
$mdata = encryptUsingSymmetric(json_encode($arrData), $symmKey);

$token = getToken(json_encode($arrData));
$key = getKey($symmKey);
$entity = getEntity("JUVO");
$varRefNo = '1234123412341234';

$arrRequest = [];
$arrRequest['token'] = $token;
$arrRequest['body'] = $mdata;
$arrRequest['key'] = $key;
$arrRequest['entity'] = $entity;
$arrRequest['refNo'] = $varRefNo;

echo "<pre>";
print_r($arrRequest);
die("HERE");
//dd(json_encode($arrRequest));

$arrResponse = $this->sendRequest($this->request_money_url, json_encode($arrRequest));

//Create log
Event::fire('juvo.log', serialize([
    'user_id' => 0,
    'deviceID' => '',
    'activityID' => 1,
    'message' => 'request money',
    'method' => 'POST',
    'request_data' => json_encode($arrData) . "======" . json_encode($arrRequest),
    'response_data' => json_encode($arrResponse),
]));
return $arrResponse;

//}

function generateSymmetricKey() {
    $data = openssl_random_pseudo_bytes(32);
    $hex   = bin2hex($data);
    return $hex;
}

function encryptUsingSymmetric($input, $key) {
    $iv = "1234123412341234";
    $iv_hex = strhex($iv);
    $key_original = Hex2String($key);
    $data = openssl_encrypt($input, "AES-256-CBC", $key_original, OPENSSL_RAW_DATA, $iv);
    return base64_encode($data);
}

function strhex($string) {
  $hexstr = unpack('H*', $string);
  return array_shift($hexstr);
}

function getToken($data) {
    $pvtKeyFile = __DIR__ . '/Key/sakshay.in.pem';
    $priv_key = file_get_contents($pvtKeyFile);
    $pkeyid = openssl_get_privatekey($priv_key, "Meghendra@123");
    $signature = "";
    $algo = "SHA1";
    openssl_sign($data, $signature, $pkeyid, $algo);
    return base64_encode($signature);
}

function Hex2String($hex){
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2){
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
}

function getKey($input) {
    $input_rev = Hex2String($input);
    $pemKey = der2pem(file_get_contents(__DIR__ . '/Key/m2psolutions_pub.der'));
    $encrypted = "";
    openssl_public_encrypt($input_rev, $encrypted, $pemKey);
    return base64_encode($encrypted);
}

function getEntity($input) {
    $pemKey = der2pem(file_get_contents(__DIR__ . '/Key/m2psolutions_pub.der'));
    $encrypted = "";
    openssl_public_encrypt($input, $encrypted, $pemKey);
    return base64_encode($encrypted);
}

function der2pem($der_data) {
    $pem = chunk_split(base64_encode($der_data), 64, "\n");
    $pem = "-----BEGIN PUBLIC KEY-----\n" . $pem . "-----END PUBLIC KEY-----\n";
    return $pem;
}
