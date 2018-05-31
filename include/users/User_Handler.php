<?php
class User_Handler
{
    private $conn;

    function __construct()
    {
        require_once  __DIR__ . '/../DbConnect.php';
        $coonect = new DbConnect();
        $this->conn = $coonect->connect();

    }


    public function createUser($mobile, $otp)
    {

        if ($this->isnotuserExists($mobile)) {

            $apikey = $this->generateApiKey();

            $stmt = $this->conn->prepare("insert into users(mobile,apikey) values (?,?)");

            $stmt->bind_param("ss", $mobile, $apikey);
            $result = $stmt->execute();

            if ($result) {
                $inserted_id = $this->conn->insert_id;

                if ($this->create_sms_code($inserted_id, $otp, $mobile)) {

                    return 1;
                } else {
                    return 3;
                }

            }


        } else {
             $user_id = $this->getUserIdByMObile($mobile);
             if ($this->create_sms_code($user_id,$otp,$mobile))
             {
                 return 2 ;
             }else {
                 return 4;
             }


        }

    }
      public function getUserIdByMObile($mobile) {
        $stmt = $this->conn->prepare("select user_id from users where mobile like ? ");
        $stmt->bind_param("s",$mobile);
        $stmt->execute();
        $stmt->bind_result($user_id);
        $stmt->store_result();
        $stmt->fetch();
        $stmt->close();
        return $user_id;
      }

    public function create_sms_code($user_id, $otp, $mobile )
    {

        // delete the old otp if exists
        $stmt = $this->conn->prepare("DELETE FROM sms_codes where user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt = $this->conn->prepare("INSERT INTO sms_codes (user_id,code,mobile)  values (?,?,?)");

        $stmt->bind_param("iss", $user_id, $otp, $mobile);
        $result = $stmt->execute();
        $stmt->close();
        return $result;


    }

    public function activeUser($mobile, $otp)
    {

        $st = $this->conn->prepare("select u.user_id,u.mobile,u.apikey ,u.created_at from users u join sms_codes s on u.user_id = s.user_id where s.mobile = ? and s.code = ?");
        $st->bind_param("ss", $mobile, $otp);

        if ($st->execute()) {
            $st->bind_result($user_id, $mobile, $apikey, $created_at);
            $st->store_result();
            $num_rows = $st->num_rows;
            if ($num_rows > 0) {
                $st->fetch();
                $this->updateUser($user_id);
                $user = array();
                $user["user_id"] = $user_id;
                $user["mobile"] = $mobile;
                $user["apikey"] = $apikey;
                $user["created_at"] = $created_at;

                return $user;


            } else {
                return null;
            }

        } else {
            null;
        }


    }


    public function updateUser($user_id)
    {
        $st = $this->conn->prepare("update users set status = 1 where user_id = ?");
        $st->bind_param("i", $user_id);
        $st->execute();
        $st = $this->conn->prepare("update sms_codes set status = 1 where user_id = ?");
        $st->bind_param("i", $user_id);
        $st->execute();
    }


    public function isnotuserExists($mobile)
    {
        $st = $this->conn->prepare("select user_id from users where mobile like ? ;");
        $st->bind_param("s", $mobile);
        $st->execute();
        $st->store_result();
        $num_rows = $st->num_rows;
        $st->close();


        return $num_rows == 0;


    }

    private function generateApiKey()
    {
        return md5(uniqid(rand(), true));
    }
}