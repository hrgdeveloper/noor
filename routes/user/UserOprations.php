<?php
$app->get("/getAllChanelsUser" , 'authenticate' ,  function () {
    $db=new User_Handler();
    $response=$db->getAllChenls();
    echoResponse(200,$response);

});
$app->get("/getUnRead" , 'authenticate' ,  function () {
    $db=new User_Handler();
    $response=$db->getUnreads();
    echoResponse(200,$response);

});
$app->get("/getUserCount" , function() {

   $db= new User_Handler();
   $response = $db->getUserCount();
   echoResponse(200,$response);
});
$app->get("/chanels/:chanel_id/getMessages" , 'authenticate' , function ($chanel_id) use ($app) {
    $response= array();
    global $user_id;
    $db = new User_Handler();
    $last_id =  $app->request->get("last_id");

    $result = $db->getAllMessages($chanel_id ,$last_id ,$user_id);
    $error = $result->error ;
    if ($error==1) {
        $response['error'] = true ;
        $response['error_type'] = 1 ;
        $response['message'] = "هیچ پیامی ثبت نشده است";
    }else if ($error==2 ) {
        $response['error'] = true ;
        $response['error_type'] = 2 ;
        $response['message'] = "پیام دیگری موجود نیست";
    }else if ($error==0) {
        $content = $result->content ;
        $response['messages'] = array();
        while ($single_message = $content->fetch_assoc()) {

            array_push($response["messages"],$single_message);
        }
        $response['error'] = false ;
        $response['error_type'] = 0 ;

    }
    echoResponse(201,$response);

});

$app->get("/chanels/:chanel_id/getMessagesTop" , 'authenticate' , function ($chanel_id) use ($app) {
    $response= array();
    global $user_id;
    $db = new User_Handler();
    $top_id =  $app->request->get("top_id");

    $result = $db->getAllTopMessages($chanel_id ,$top_id ,$user_id);
    $error = $result->error ;
  if ($error==1 ) {
        $response['error'] = true ;
        $response['error_type'] = 2 ;
        $response['message'] = "پیام دیگری موجود نیست";
    }else if ($error==0) {
        $content = $result->content ;
        $response['messages'] = array();
        while ($single_message = $content->fetch_assoc()) {

            array_push($response["messages"],$single_message);
        }
        $response['error'] = false ;
        $response['error_type'] = 0 ;

    }
    echoResponse(201,$response);

});
//in link vase in estefade mishe ke tedade payami ke ezafe shode  ro ke to mohgei ke offline boodim begirim o vaghti ke to chanelim o ye payam
// jadid push mishe check koim bebin age 1 bood yani faqat haoomn ye done payam ezaf mishe vali age az 1 bozogrtar bood bayad chanta payam ezafe she yani
//az akharin id ke darim ta akharin payam ezafe shode
$app->get("/chanels/:chanel_id/getlastCount",'authenticate' , function ($chanel_id) use ($app){
    $response= array();
    $db = new User_Handler();
    $last_id =  $app->request->get("last_id");
   $totoal=    $db->getLastCounts($chanel_id,$last_id);
   $response['last_count'] = $totoal;
   echoResponse(200,$response);
});
$app->post("/likeMessage/:message_id" ,'authenticate' , function ($message_id) use ($app) {

    global  $user_id;

    //0 for unlike (delete) like and 1 for like
   $type = $app->request->post("type");
    $db=new User_Handler();
   $response = $db->setLike($type,$user_id,$message_id);
   echoResponse(200,$response);


});
$app->post("/comment/:message_id" , 'authenticate' , function ($message_id) use ($app) {
    global $user_id;
    verifyRequiredParams(array("text"));
    $text = $app->request->post("text");
    $chanel_id = $app->request->post("chanel_id");
    $db = new User_Handler();
    $response = $db->makeComment($chanel_id, $text,$user_id,$message_id);
    echoResponse(201,$response);

});
$app->get("/comment/:message_id" , 'authenticate' , function ($message_id) use ($app) {


    $db = new User_Handler();
    $response =   $db->getAllComments($message_id);
    echoResponse(201,$response);

});