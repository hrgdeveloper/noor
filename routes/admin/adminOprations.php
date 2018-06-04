<?php
$app->get("/getUsers" , "authenticateAdmin" , function () {
    $response=array();
   $db = new Admin_Hanlder();
   $result = $db->getAllUsers();
    if ($result==null) {
        $response['error'] =true;
        $response['message'] = "در حال حاضر هیچ کاربری ثبت نام نکرده است" ;

    }else {
        $response['error'] = false ;
        $response['user_list'] = array();
        while ($single_user = $result->fetch_assoc()) {
            array_push($response['user_list'] , $single_user);
        }


    }
    echoResponse(201,$response);


});
$app->put("/changeUserActive/:user_id" , "authenticateAdmin" , function ($user_id) use ($app) {
    verifyRequiredParams(array("status"));
   $status = $app->request->put("status");

  //  $user_id = $app->request->put("user_id");
    $response=array();
    $db = new Admin_Hanlder();
  $result =   $db->updateUserActive($status,$user_id);
      if ($result) {
          $response['error'] =false;
          $response['message'] = "به روز رسانی انجام گردید" ;
      }else {
          $response['error'] =true;
          $response['message'] = "خطا در به روز رسانی" ;
      }
      echoResponse( 201,$response);
});


$app->post("/makeChanel" , "authenticateAdmin", function () use ($app) {
    verifyRequiredParams(array("details"));
       $details = $app->request->post("details");
       $real_detaisl = json_decode($details,true);
       $response=array();

    define ("MAX_SIZE","2000");

    if(!isset($_FILES['pic']['name']))
    {
        $response['error'] =true ;
        $response['message'] = "عکس مورد نظر انتخاب نشده";
        echoResponse(400,$response);
        $app->stop();
    }

    if ($_FILES['pic']['error'] != UPLOAD_ERR_OK ) {
        $response['error'] =true ;
        $response['message'] = "خطلا در اپلود عکس کانال ";
        echoResponse(400,$response);
        $app->stop();
    }
    $pic_size = filesize($_FILES['pic']['tmp_name']);

    if ($pic_size > MAX_SIZE*1024 ) {
        $response["error"] = true;
        $response["message"] = "حداکثر اندازه عکس 2 مگابایت میباشد" ;
        echoResponse(400, $response);
        $app->stop();
    }

    $info_pic = getimagesize($_FILES['pic']['tmp_name']);

    if ($info_pic==false) {
        $response["error"] = true;
        $response["message"] = "خطا در شناسای نوع فایل" ;
        echoResponse(400, $response);
        $app->stop();
    }


    if (($info_pic[2] !== IMAGETYPE_GIF) && ($info_pic[2] !== IMAGETYPE_JPEG) && ($info_pic[2] !== IMAGETYPE_PNG)) {
        $response["error"] = true;
        $response["message"] = "فرمت فایل آپلود شده معتبر نمیباشد" ;
        echoResponse(400, $response);
        $app->stop();
    }

    $db=new Admin_Hanlder();
    $result = $db->makeChanel($real_detaisl);



});

