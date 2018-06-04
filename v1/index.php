<?php
require_once '../vendor/autoload.php';
require_once '../include/users/User_Handler.php';
require_once '../include/SmsIR_SendMessage.php';
require '../include/admin/Admin_Hanlder.php';
require '../include/DbHanlder.php';
error_reporting(E_ALL);
ini_set('display_errors',1);
$apikey=null;
$user_id = null ;
$admin_id = null ;
$app = new \Slim\Slim();
define('SITEURL',"https://api.kavenegar.com");



function authenticate(\Slim\Route $route) {

    $header = apache_request_headers();
    $response  = array();
    $app = \Slim\Slim::getInstance();
    if (isset($header['Authorization'])) {
        $db = new DbHanlder();
        global $apikey;
        $apikey = $header['Authorization'] ;
        if (!$db->isValidApikey($apikey)) {
            $response['error'] = true ;
            $response['message'] = "Apikey is not valid ! ";
            echoResponse(401,$response);
            $app->stop();
        }else {
            global $user_id ;
            $user_id = $db->getUserIdByApikey($apikey);
        }

    }else {
        $response['error'] = true ;
        $response['message'] = "Apikey is Missing! ";
        echoResponse(400,$response);
        $app->stop();
    }
}
function authenticateAdmin(\Slim\Route $route) {

    $header = apache_request_headers();
    $response  = array();
    $app = \Slim\Slim::getInstance();
    if (isset($header['AuthorizationMyAd'])) {
        $db = new DbHanlder();
        global $apikey;
        $apikey = $header['AuthorizationMyAd'] ;
        if (!$db->isValidApikeyAdmin($apikey)) {
            $response['error'] = true ;
            $response['message'] = "Apikey is not valid ! ";
            echoResponse(401,$response);
            $app->stop();
        }else {
            global $admin_id ;
            $admin_id = $db->getAdminByapiKey($apikey);
        }

    }else {
        $response['error'] = true ;
        $response['message'] = "Apikey is Missing! ";
        echoResponse(400,$response);
        $app->stop();
    }
}


require '../routes/user/registarion.php';
require '../routes/admin/registeration.php';
require '../routes/admin/adminOprations.php';


function sendSms($mobile ,$otp) {
    $message = "به نور الصالحین خوش آمدید رمز عبور شما :" . $otp ;
    $query=http_build_query(array('receptor' => $mobile , 'message' => $message), null, "&", PHP_QUERY_RFC3986);
     CallAPI("get",'v1/5742436474485749634F744D4241726D6B52647872673D3D/sms/send.json?'.$query ,array(),array());

//    try {
//
//
//        date_default_timezone_set("Asia/Tehran");
//        $message = "به  نور الصالحین خوش آمدید رمز تایید حساب : " . $otp ;
//
//        // your sms.ir panel configuration
//        $APIKey = "b47d87e6a117f558c5617e17";
//        $SecretKey = "&&5212tsts";
//        $LineNumber = "50002015008386";
//
//        // your mobile numbers
//        $MobileNumbers = array($mobile);
//
//        // your text messages
//        $Messages = array($message);
//
//
//
//        $SmsIR_SendMessage = new SmsIR_SendMessage($APIKey,$SecretKey,$LineNumber);
//        $SendMessage = $SmsIR_SendMessage->SendMessage($MobileNumbers,$Messages);
//     //   var_dump($SendMessage);
//
//    } catch (Exeption $e) {
//        echo 'Error SendMessage : '.$e->getMessage();
//    }


}
function CallAPI($method, $api, $data, $headers) {
    $url = SITEURL . "/" . $api;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    switch ($method) {
        case "GET":
            curl_setopt($curl,CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
            break;
        case "POST":
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl,CURLOPT_POST, count($data));
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            break;
        case "DELETE":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            break;
    }
    $response = curl_exec($curl);

    /* Check for 404 (file not found). */
//    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
//    // Check the HTTP Status code
//    switch ($httpCode) {
//        case 200:
//            $error_status = "200: Success";
//            return ($data);
//            break;
//        case 404:
//            $error_status = "404: API Not found";
//            break;
//        case 500:
//            $error_status = "500: servers replied with an error.";
//            break;
//        case 502:
//            $error_status = "502: servers may be down or being upgraded. Hopefully they'll be OK soon!";
//            break;
//        case 503:
//            $error_status = "503: service unavailable. Hopefully they'll be OK soon!";
//            break;
//        default:
//            $error_status = "Undocumented error: " . $httpCode . " : " . curl_error($curl);
//            break;
//    }
//    curl_close($curl);
//    echo $error_status;
//    die;
}


function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
}



function echoResponse($status , $response ) {
    $app = \Slim\Slim::getInstance();
    $app->status($status);
    $app->contentType('application/json');
    echo json_encode($response);

}
$app->run();