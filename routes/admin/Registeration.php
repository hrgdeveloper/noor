<?php
$app->post("/createAdmin" , function() use ($app) {


    $response = array();

    $username= $app->request->post('username');
    $password = $app->request->post('password');

    $db = new Admin_Hanlder();
    $res = $db->createAdmin( $username, $password );
    if($res==0){
        $response["error"] = true;
        $response["message"] = "خطایی پیش آمده";
    }

    else if ($res == 1) {
        $response["error"] = false;
        $response["message"] = "مدیریت جدید ذخیره شد";

        // echo json response

    }
    echoResponse(201, $response);
});

$app->post("/LogAdmin_noor" , function() use ($app) {




    $userName= $app->request()->post('username');
    $password = $app->request()->post('password');
    $response = array();

    $db = new Admin_Hanlder();
    // check for correct email and password
    $status = $db->checkLogin($userName,$password);
    if ($status==1) {


        $admin_temp= $db->getAdminByUsername($userName);
        $admin=$admin_temp->fetch_assoc();

        $response["error"] = false;
        $response["admin"] = $admin;

    } else {
        $response['error'] = true;
        $response['message'] = 'نام کاربری یا رمز عبور وارد شده صحیح نمیباشد';
    }


    echoResponse(200, $response);

});

