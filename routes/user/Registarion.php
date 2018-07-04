<?php
$app->post("/register" , function () use ($app) {
    verifyRequiredParams(array('mobile'));
    $response = array();
    $mobile = $app->request->post("mobile");

    //$pattern ="/^(9|09)(12|19|35|36|37|38|39|32|21)\d{7}$/";
    $pattern ="/^(09)\d{9}$/";
    if (!preg_match($pattern,$mobile))
    {
        $response['error'] =true;
        $response['message'] = "فرمت شماره وارد شده صحیح نمیباشد" ;
    }else {
        $otp = rand(100000,999999);
        $db = new User_Handler();
        $result = $db->createUser($mobile,$otp);


        if ($result==1 || $result==2) {
         //   sendSms($mobile,$otp);
            $response['error'] = false ;
            $response['message'] = "پیامک حاوی کد فعال سازی برای شما ارسال گردید";

        }else {
            $response['error'] = true ;
            $response['message'] = "خطلا در ساخت کاربر ! لطفا دوباره امتحان کنید";
        }

    }

    echoResponse(201,$response);
}) ;
$app->post('/verify' , function () use ($app) {
 //   $response = array();
    verifyRequiredParams(array('mobile' , 'otp'));
    $mobile = $app->request->post('mobile');
    $otp = $app->request->post('otp');
    $db = new User_Handler();
    $response= $db->activeUser($mobile,$otp);
//    if ($user==null) {
//        $response['error'] = true;
//        $response['message'] = 'کد ارسال شده اشتباه میباشد';
//    }else {
//        $response['error'] = false ;
//        $response['message'] = 'خوش آمدید';
//        $response['user'] = $user;
//    }
    echoResponse(200,$response);

});
//vase be rooz resanie code firebase userha

$app->put('/updatefcm/:id' , function ($user_id) use ($app) {
    verifyRequiredParams(array('fcm_code'));
    $fcm_code = $app->request->put("fcm_code");
    $db = new User_Handler();
    $response = $db->updateFcmCode($user_id,$fcm_code);
    echoResponse(200,$response);

});

$app->put('/updateUsername' , "authenticate"  ,function () use ($app) {
       global $user_id ;
    $username = $app->request->put("username");

    $db = new User_Handler();
    $response = $db->updateUsername($user_id,$username);
    echoResponse(200,$response);

});