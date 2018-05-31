<?php
class Admin_Hanlder {
    private $conn;
    function __construct()
    {
        require_once __DIR__ .  '/../DbConnect.php';
        $coonect = new DbConnect();
        $this->conn = $coonect->connect();
    }
    // ************************************************************ registeration ********************************************\\

    public function createAdmin($username, $password ) {

            // Generating password hash
            $password_hash = password_hash($password,PASSWORD_DEFAULT);

            // Generating API key
            $api_key = $this->generateApiKey();

            $stmt = $this->conn->prepare("INSERT INTO admin_login(username, password , apikey) values(?,?,?)");


            $stmt->bind_param("sss", $username, $password_hash, $api_key);

            $result = $stmt->execute();


            $stmt->close();

            // Check for successful insertion
            if ($result) {
            return 1 ;

            } else {
                // Failed to create user
                return 0;
            }
        }
    public function checkLogin($username, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password  FROM admin_login WHERE username= ?");

        $stmt->bind_param("s", $username);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (password_verify($password,$password_hash)) {
                // User password is correct
                return 1;
            } else {
                // user password is incorrect
                return 2;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return 3;
        }
    }
    public function getAdminByUsername($username) {
        $stmt = $this->conn->prepare("SELECT  admin_id,username,apikey FROM admin_login WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;

    }

    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    // ************************************************************ adminOprations ********************************************\\

    public function getAllUsers() {
        $stmt = $this->conn->prepare("SELECT user_id,mobile,username,status,active,created_at FROM users");
        $stmt->execute();
        $result = $stmt->get_result();
       if ($result->num_rows>0) {
           return $result;
       }else {
           return null ;
       }


}



}