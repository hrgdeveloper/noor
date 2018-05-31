<?php
/**
 * Created by PhpStorm.
 * User: hamid
 * Date: 5/2/2018
 * Time: 4:17 PM
 */
error_reporting(E_ALL);
ini_set('display_errors',1);
class DbHanlder {
    private $conn ;
    function __construct()
    {
        require_once __DIR__ . '../DbConnect.php';
        $coonect = new DbConnect();
        $this->conn = $coonect->connect();

    }


    public function isValidApikey($apikey) {
   $stmt = $this->conn->prepare("select user_id from users where apikey like ? ");

   $stmt->bind_param("s" , $apikey);
   $stmt->execute();
   $result =   $stmt->get_result();
   return $result->num_rows > 0 ;

    }


    public function getUserIdByApikey($apikey) {
        $stmt =$this->conn->prepare("select user_id from users where apikey like ? ");

        $stmt->bind_param("s",$apikey);

        if ($stmt->execute())  {
            $stmt->bind_result($user_id);
            $stmt->store_result();
            $stmt->fetch();
            $stmt->close();
            return $user_id;

        }else {
            return null ;
        }
    }



}