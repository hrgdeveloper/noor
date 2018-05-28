<?php
/**
 * Created by PhpStorm.
 * User: hamid
 * Date: 5/25/2018
 * Time: 2:19 PM
 */

  class DbConnect {
      private $conn ;
      function __construct()
      {
          require_once __DIR__.'/Config.php';
      }
      function connect() {
          $this->conn=new mysqli(DB_HOST,DB_USERNAME,DB_PASSWORD,DB_NAME);
          $this->conn->set_charset('utf8');
          if (mysqli_connect_errno()) {
              echo "connect faild";
          }
     return $this->conn;
      }

  }