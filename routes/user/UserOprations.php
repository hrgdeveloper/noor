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

$app->get("/chanels/:chanel_id/getMessages/:page" , 'authenticate' , function ($chanel_id,$page) use ($app) {
    $response= array();
    global $user_id;
    $db = new User_Handler();
    $last_id =  $app->request->get("last_id");

    $result = $db->getAllMessages($chanel_id , $page,$last_id ,$user_id);
    $error = $result->error ;
    if ($error==1) {
        $response['error'] = true ;
        $response['error_type'] = 1 ;
        $response['message'] = "هیچ پیامی ثبت نشده است";
    }else if ($error==2 || $error==3) {
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

//SELECT c.name,c.description, ms1.message,ms1.type
//FROM chanels AS c
//LEFT JOIN message AS ms1 ON c.chanel_id = ms1.chanel_id
//LEFT JOIN message AS ms2 ON c.chanel_id = ms2.chanel_id AND ms1.created_at < ms2.created_at
//WHERE ms2.created_at IS NULL

//SELECT c.name,c.description, ms1.message,ms1.type
//FROM chanels AS c
// JOIN message AS ms1 ON ms1.message_id = (SELECT message_id FROM message WHERE chanel_id = c.chanel_id ORDER BY message_id DESC LIMIT 1)


//SELECT chanel_id,name,description,pic_thumb,updated_at,c.created_at ,a.username,m.message,m.type  FROM chanels c join admin_login a ON c.admin_id = a.admin_id LEFT join message AS m ON m.message_id = (SELECT message_id FROM message WHERE chanel_id = c.chanel_id ORDER BY message_id DESC LIMIT 1)

