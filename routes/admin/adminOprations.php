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