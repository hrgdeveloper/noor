<?php

 //========================================================= forUsers =============================================================\\
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
//=======================================================forChanels=============================================================\\

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
    $return = $result->return ;
    $chanel = $result->chanel;

    if ($return==0) {
        $response['error'] = true ;
        $response['message'] = "خطلا در اپلود عکس" ;

    }else if ($return==1) {
        require_once __DIR__  . '/../../include/fcm/Firebase.php';
        require_once __DIR__  . '/../../include/fcm/push.php';
        $push  = new Push();
        $firebase = new Firebase();

        $push->setIsBackground(false);
        $push->setPayload($chanel->fetch_assoc());
        $push->setFlag(PUSH_NEW_CHANEL);
        $firebase->sendToTopic("global",$push->getPush());

        $response['error'] = false ;
        $response['message'] = "کانال جدید ذخیره شد" ;


    }else if ($return==2) {
        $response['error'] = true ;
        $response['message'] = "خطلا در ذخیره اطلاعات کانال" ;
    }else if ($return==3) {

        $response['error'] = true ;
        $response['message'] = "این کانال قبلا ذخیره شده است" ;
    }

    echoResponse(200,$response);





});
$app->get("/getAllChanels" , 'authenticateAdmin' ,  function () {
    $db=new Admin_Hanlder();
    $response=$db->getAllChenls();
    echoResponse(200,$response);

});
$app->get("/getAllComments/:chanel_id" , 'authenticateAdmin' ,  function ($chanel_id) {
    $db=new Admin_Hanlder();
    $response=$db->getAllComments($chanel_id);
    echoResponse(200,$response);

});
$app->put("/setCommentState/:comment_id" , 'authenticateAdmin' ,  function ($comment_id) use ($app) {
    $state = $app->request->post("state");
    $response=array();
    $db=new Admin_Hanlder();
    $result=$db->updateCommentState($comment_id,$state);
    if ($result>0) {
        $response['error'] = false;
        $response['message'] = "به روز رسانی انجام شد" ;
    }else {
        $response['error'] = true;
        $response['message'] = "خطا در به روز رسانی" ;
    }
    echoResponse(200,$response);

});


$app->post("/updateChanelPic/:chanel_id" , 'authenticateAdmin' , function ($chanel_d) use ($app) {

    define ("MAX_SIZE","2000");
    $last_picname =$app->request->post("last_pic_name");

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
    $response  = $db->updateChanelPhoto($chanel_d,$last_picname);
     echoResponse(200,$response);

});
$app->post("/addChanelPic/:chanel_id" , 'authenticateAdmin' , function ($chanel_d) use ($app) {

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
    $response  = $db->addChanelPhoto($chanel_d);
    echoResponse(200,$response);

});
$app->delete("/deleteChanelPhoto/:photo_id" , 'authenticateAdmin' , function ($photo_id) use ($app) {
    $photo_name =$app->request->post("photo_name");
    $db=new Admin_Hanlder();
    $response  = $db->deleteChanelPhoto($photo_id,$photo_name);
    echoResponse(202,$response);
});
$app->get("/getAllChanelsPhotos/:chanel_id"  , function ($chanel_id) {
    $db = new Admin_Hanlder();
    $response = $db->getAllChanelPhotoes($chanel_id);
    echoResponse(201,$response);



});




//////////////////////////////////////////////////////////Messages\\\\\\\\\\\\\\\\\\\\
$app->post(
    "/chanels/:id/message" , "authenticateAdmin" , function ($chanel_id) use ($app) {
     $response = array();

    $jsonContent = $app->request->post("content");
    $content = json_decode($jsonContent,true);



    $db = new Admin_Hanlder();
    $type =  $content['type'];
    if ($type==1) {
     $result =    $db->makePlainMessage($content,$chanel_id);
     if ($result==null) {
         $response['error'] = true;
         $response['message'] = "خطا در ارسال پیام";
     } else {

         $final_result = $result->fetch_assoc();

         require_once __DIR__  . '/../../include/fcm/Firebase.php';
         require_once __DIR__  . '/../../include/fcm/push.php';
         $push  = new Push();

         $firebase = new Firebase();
         $push->setIsBackground(false);
         $push->setPayload($final_result);
         $push->setFlag(PUSH_NEW_MESSAGE);
         $push->setMessage("پیام جدید");
         $firebase->sendToTopic("chanel_".$final_result['chanel_id'],$push->getPush());
         $response['error'] = false ;
         $response['message'] = "پیام جدید ارسال شد" ;
         $response['payload'] = $final_result;
         echoResponse(201,$response);
     }

    }else if ($type==2) {


        if(!isset($_FILES['file']['name']))
        {
            $response['error'] =true ;
            $response['message'] = "عکس مورد نظر انتخاب نشده";
            echoResponse(400,$response);
            $app->stop();
        }

        if ($_FILES['file']['error'] != UPLOAD_ERR_OK ) {
            $response['error'] =true ;
            $response['message'] = "خطلا در اپلود عکس کانال ";
            echoResponse(400,$response);
            $app->stop();
        }

        $info_pic = getimagesize($_FILES['file']['tmp_name']);

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
        $result =    $db->makePicMessage($content,$chanel_id);


        if ($result->error==1) {
            $response["error"] = true;
            $response["message"] = "خطا در اپلود عکس" .$_FILES["file"]["error"];;
            echoResponse(400, $response);
        }else if ($result->error==2) {
            $response["error"] = true;
            $response["message"] = "خطا در وارد کردن اطلاعات ورودی" ;
            echoResponse(400, $response);
        }else {
            $final_result = $result->content->fetch_assoc();

            $response['error'] = false ;
            $response['message'] = "پیام جدید ارسال شد" ;
            $response['payload'] = $final_result ;

            require_once __DIR__  . '/../../include/fcm/Firebase.php';
            require_once __DIR__  . '/../../include/fcm/push.php';
            $push  = new Push();

            $firebase = new Firebase();
            $push->setIsBackground(false);
            $push->setPayload($final_result);
            $push->setFlag(PUSH_NEW_MESSAGE);
            $push->setMessage("پیام جدید");
            $firebase->sendToTopic("chanel_".$final_result['chanel_id'],$push->getPush());
            echoResponse(200,$response);

        }

    }else if ($type==3) {
        $allowedExtensitons = array('flv' ,'FLV' , 'mp4','MP4' , 'm3u8' ,'M3U8' , 'mkv' ,'MKV', 'ts' , 'TS', '3gp','3GP' , 'mov','MOV' , 'avi','AVI'
        , 'wmv' , 'WMV');
        if(!isset($_FILES['file']['name']) || !isset($_FILES['thumb']['name']))
        {
            $response['error'] =true ;
            $response['message'] = " ویدیو مورد نظر  انتخاب نشده";
            echoResponse(400,$response);
            $app->stop();
        }


        $pic_info = pathinfo($_FILES['file']['name']);
        $extension = $pic_info['extension'];
        if (!in_array(strtolower($extension) ,$allowedExtensitons)) {
            $response['error'] =true ;
            $response['message'] = "فرمت ویدیو اپلود شده معتبر نمیباشد";
            echoResponse(400,$response);
            $app->stop();
        }
        die();

        $info_pic = getimagesize($_FILES['thumb']['tmp_name']);

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
        $result = $db->makeVideoMessage($content,$chanel_id);
        if ($result->error==1) {
            $response["error"] = true;
            $response["message"] = "خطا در اپلود عکس ویدیو" .$_FILES["thumb"]["error"];;
            echoResponse(400, $response);
        }else if ($result->error==2) {
            $response["error"] = true;
            $response["message"] = "خطا در اپلود  ویدیو" .$_FILES["file"]["error"];;
            echoResponse(400, $response);
        }else {

            $final_result = $result->content->fetch_assoc();

            $response['error'] = false ;
            $response['message'] = "ویدیو جدید ارسال شد" ;
            $response['payload'] = $final_result ;

            require_once __DIR__  . '/../../include/fcm/Firebase.php';
            require_once __DIR__  . '/../../include/fcm/push.php';
            $push  = new Push();

            $firebase = new Firebase();
            $push->setIsBackground(false);
            $push->setPayload($final_result);
            $push->setFlag(PUSH_NEW_MESSAGE);
            $push->setMessage("پیام جدید");
            $firebase->sendToTopic("chanel_".$final_result['chanel_id'],$push->getPush());
            echoResponse(200,$response);

        }

        }else if ($type==4) {
        $allowedExtensitons = array('mp3' ,'wma', 'aac','wav' , 'amr' ,'flac' , 'ogg');
        if(!isset($_FILES['file']['name']))
        {
            $response['error'] =true ;
            $response['message'] = " فایل صوتی مورد نظر  انتخاب نشده";
            echoResponse(400,$response);
            $app->stop();
        }
        $pic_info = pathinfo($_FILES['file']['name']);
        $extension = $pic_info['extension'];
        if (!in_array($extension,$allowedExtensitons)) {
            $response['error'] =true ;
            $response['message'] = "فرمت فایل صوتی اپلود شده معتبر نمیباشد";
            echoResponse(400,$response);
            $app->stop();

        }
        $db = new Admin_Hanlder();
        $result = $db->makeAudioMessage($content,$chanel_id);
        if ($result->error==1) {
            $response["error"] = true;
            $response["message"] = "خطا در بارگزاری فایل صوتی " ;
            echoResponse(400, $response);
        }else if ($result->error==2) {
            $response["error"] = true;
            $response["message"] = "خطا در ساخت پیام جدید " ;
            echoResponse(400, $response);
        }else {

            $final_result = $result->content->fetch_assoc();

            $response['error'] = false ;
            $response['message'] = " فایل صوتی جدید ارسال شد" ;
            $response['payload'] = $final_result ;

            require_once __DIR__  . '/../../include/fcm/Firebase.php';
            require_once __DIR__  . '/../../include/fcm/push.php';
            $push  = new Push();

            $firebase = new Firebase();
            $push->setIsBackground(false);
            $push->setPayload($final_result);
            $push->setFlag(PUSH_NEW_MESSAGE);
            $push->setMessage("پیام جدید");
            $firebase->sendToTopic("chanel_".$final_result['chanel_id'],$push->getPush());
            echoResponse(200,$response);

        }


    }else if ($type==5) {
        if (!isset($_FILES['file']['name'])) {
            $response['error'] = true;
            $response['message'] = " فایل  مورد نظر  انتخاب نشده";
            echoResponse(400, $response);
            $app->stop();
        }
        $db = new Admin_Hanlder();
        $result = $db->makeFileMessage($content, $chanel_id);
        if ($result->error == 1) {
            $response["error"] = true;
            $response["message"] = "خطا در بارگزاری فایل  ";
            echoResponse(400, $response);
        } else if ($result->error == 2) {
            $response["error"] = true;
            $response["message"] = "خطا در ساخت پیام جدید ";
            echoResponse(400, $response);
        } else {

            $final_result = $result->content->fetch_assoc();

            $response['error'] = false;
            $response['message'] = "فایل جدید ارسال شد";
            $response['payload'] = $final_result;

            require_once __DIR__ . '/../../include/fcm/Firebase.php';
            require_once __DIR__ . '/../../include/fcm/push.php';
            $push = new Push();

            $firebase = new Firebase();
            $push->setIsBackground(false);
            $push->setPayload($final_result);
            $push->setFlag(PUSH_NEW_MESSAGE);
            $push->setMessage("پیام جدید");
            $firebase->sendToTopic("chanel_" . $final_result['chanel_id'], $push->getPush());
            echoResponse(200, $response);
        }
    }

});



