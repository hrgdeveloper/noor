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
        $db = new Register_Handler_U();
        $result = $db->createUser($mobile,$otp);


        if ($result==1 || $result==2) {
       //     sendSms($mobile,$otp);
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
    $response = array();
    verifyRequiredParams(array('mobile' , 'otp'));
    $mobile = $app->request->post('mobile');
    $otp = $app->request->post('otp');
    $db = new Register_Handler_U();
    $user= $db->activeUser($mobile,$otp);
    if ($user==null) {
        $response['error'] = true;
        $response['message'] = 'کد ارسال شده اشتباه میباشد';
    }else {
        $response['error'] = false ;
        $response['message'] = 'خوش آمدید';
        $response['user'] = $user;
    }
    echoResponse(200,$response);

});
