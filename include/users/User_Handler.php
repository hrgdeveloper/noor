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
/////////////////////////////////////////////////////// User Registration whit sms Part \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\

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
       $response =array();

        $st = $this->conn->prepare("select u.user_id,u.mobile,u.apikey ,active, u.username, u.created_at from users u join sms_codes s on u.user_id = s.user_id where s.mobile = ? and s.code = ?");
        $st->bind_param("ss", $mobile, $otp);

        if ($st->execute()) {
            $st->bind_result($user_id, $mobile, $apikey,$active, $username, $created_at);
            $st->store_result();
            $num_rows = $st->num_rows;
            if ($num_rows > 0) {
                $st->fetch();
                if ($active==1) {

                    $this->updateUserSmsSate($user_id);
                    $user = array();
                    $user["user_id"] = $user_id;
                    $user["mobile"] = $mobile;
                    $user["apikey"] = $apikey;
                    $user["username"] = $username;
                    $user["created_at"] = $created_at;

                    $response['error'] = false ;
                    $response['user'] = $user ;
                    return $response;
                }else {
                    $response['error'] = true ;
                    $response['message'] = "کاربری شما غیر فعال شده است" ;
                    return $response;

                }






            } else {
                $response['error'] = true ;
                $response['message'] = "کد ارسال شده اشتباه میباشد" ;
                return $response;
            }

        } else {
           $response['error'] = true ;
            $response['message'] = "خطلا در ثبت نام ! لطفا دوباره تلاش نمایید" ;
            return $response;
        }


    }


    public function updateUserSmsSate($user_id)
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

    public function updateFcmCode($user_id , $fcm_code) {
        $response = array();
        $stmt = $this->conn->prepare("UPDATE users SET fcm_code = ? WHERE user_id = ?");
        $stmt->bind_param("si", $fcm_code, $user_id);
        $stmt->execute();

        if ($this->conn->affected_rows > 0) {
            // User successfully updated
            $response["error"] = false;
            $response["message"] = 'کد فایربیس به روز رسانی شد';
        } else {
            // Failed to update user
            $response["error"] = true;
            $response["message"] = "خطلا در به روز رسانی کد فایر بیس";
            $stmt->error;
        }

        $stmt->close();
        return $response;
}

    public function updateUsername($user_id , $username) {
        $response = array();

        if ($this->isUsernameExists($username)) {
            $response["error"] = true;
            $response["message"] = "این نام کاربری قبلا انتخاب شده است";
            return $response;
        }else {
            $stmt = $this->conn->prepare("UPDATE users SET username = ? WHERE user_id = ?");
            $stmt->bind_param("si", $username, $user_id);
            $stmt->execute();

            if ($this->conn->affected_rows > 0) {
                // User successfully updated
                $response["error"] = false;
                $response["message"] = 'نام کاربری به روز شد';
            } else {
                // Failed to update user
                $response["error"] = true;
                $response["message"] = "خطلا در به روز رسانی نام کاربری";
                $stmt->error;
            }

            $stmt->close();
            return $response;
        }





    }

    public function isUsernameExists($usernamme) {
        $st = $this->conn->prepare("select user_id from users where username like ? ;");
        $st->bind_param("s", $usernamme);
        $st->execute();
        $st->store_result();
        $num_rows = $st->num_rows;
        $st->close();
        return $num_rows > 0;
    }


    private function generateApiKey()
    {
        return md5(uniqid(rand(), true));
    }

    /////////////////////////////////////////////////////// ChanelsOprations \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\

    public function getAllChenls() {
        $response = array();
        $response['chanels'] = array();
        // in query baes mishe ke akharin payam vared shode vase har canalam begirim
        $stmt = $this->conn->prepare("SELECT c.chanel_id, c.name,c.description,c.thumb, a.username, ms1.message as last_message,ms1.type , ms1.updated_at
FROM chanels AS c join admin_login a on c.admin_id = a.admin_id
left JOIN message AS ms1 ON ms1.message_id = (SELECT message_id FROM message WHERE chanel_id = c.chanel_id ORDER BY message_id DESC LIMIT 1) ");
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows>0) {
            $stmt_tead = $this->conn->prepare("SELECT c.chanel_id ,COUNT(m.message_id) as count FROM chanels c left join message m on c.chanel_id = m.chanel_id AND m.active = 1 GROUP BY c.chanel_id");
            $stmt_tead->execute();
            $result_tedad = $stmt_tead->get_result();

            $response['error'] = false;
            while ($single_chanel = $result->fetch_assoc()) {
                $single_tedad = $result_tedad->fetch_assoc();
                $temp = array();
                $temp['chanel_id'] = $single_chanel['chanel_id'];
                $temp['name'] = $single_chanel['name'];
                $temp['description'] = $single_chanel['description'];
                $temp['thumb'] = $single_chanel['thumb'];
                $temp['username'] = $single_chanel['username'];
                $temp['last_message'] = $single_chanel['last_message'];
                $temp['type'] = $single_chanel['type'];
                $temp['updated_at'] = $single_chanel['updated_at'];
                $temp['count'] = $single_tedad['count'];
                array_push($response['chanels'] ,$temp);
            }

        }else {
            $response['error'] = true;
            $response['message'] = "هیچ کانالی ثبت نشده است" ;
        }
        return $response;

    }
    public function getUnreads() {
        $response = array();
        $response['unreads'] = array();
        // in query baes mishe ke akharin payam vared shode vase har canalam begirim
        $stmt_tead = $this->conn->prepare("SELECT c.chanel_id ,COUNT(m.message_id) as count FROM chanels c left join message m on c.chanel_id = m.chanel_id AND m.active = 1 GROUP BY c.chanel_id ");
        $stmt_tead->execute();
        $result = $stmt_tead->get_result();
        if ($result->num_rows>0) {
            $response['error'] = false;
            while ($single_unread= $result->fetch_assoc()) {

                array_push($response['unreads'] ,$single_unread);
            }

        }else {
            $response['error'] = true;
            $response['message'] = "هیچ کانالی ثبت نشده است" ;
        }
        return $response;

    }
    public function getUserCount() {
        $response = array();
        $stmt_tead = $this->conn->prepare("SELECT count(user_id) as count  from users where users.status=1 and users.active=1 ");

        if ( $stmt_tead->execute()) {
            $result = $stmt_tead->get_result();
            $real_result = $result->fetch_assoc();
            $response['error'] = false ;
            $response['user_count'] = $real_result['count'];
            return $response;

        }else {
          $response["error"] = true ;
            return $response;
        }

    }

//////////////////////////////////////////////////////////////////////MessageOprations\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
public function getAllMessages($chanel_id,$page ,$message_id,$user_id)
{
    $limit = 20;
    $total = mysqli_num_rows(mysqli_query($this->conn, "SELECT message_id from message where active = 1 and 
 chanel_id like $chanel_id "));

    if ($total==0) {
        $result = (object) [
            "error" => 1

        ];

        return $result;

    }else {
        $page_limit = $total / $limit;
        if (is_float($page_limit) || $total % $limit == 0) {
            $page_limit = $page_limit + 1;
            if ($page < $page_limit) {

                $start = ($page - 1) * $limit;
                  // in code ye selecte khili mamolie az tamamie sotonhaye message join ba like faqat vase inke tartibe desc az aval be akhar she injori kardim
                $stmt = $this->conn->prepare("select sub.message_id , sub.admin_id, sub.chanel_id ,
 sub.message , sub.type,sub.pic_thumb,sub.lenth,sub.time,sub.url,sub.updated_at , IFNULL(l.message_id,0) as liked from 
 (select * from message where chanel_id like ? and active like 1 and message_id > ? ORDER by message_id DESC limit $start, $limit) sub 
 left join likes l on l.message_id = sub.message_id and l.user_id like ?  order by sub.message_id ASC
 		");


                //select sub.message_id , sub.admin_id, sub.chanel_id , sub.message , sub.type,sub.pic_thumb,sub.lenth,sub.time,sub.url,sub.updated_at , l.message_id , l.user_id from (select * from message where chanel_id like 1 and active like 1 and message_id > 100 ORDER by message_id DESC limit 20, 20) sub left join likes l on l.message_id = sub.message_id  order by sub.message_id ASC
                $stmt->bind_param("iii", $chanel_id,$message_id,$user_id);
                $stmt->execute();


                $content = $stmt->get_result();

                if ($content->num_rows>0) {
                    $result = (object) [
                        "error" => 0 ,
                        "content" => $content

                    ];

                    return $result;
                }  else {

                    // yani dige bozorgtar az id akhar messagi sabt nashode
                    $result = (object) [
                        "error" => 3
                    ];

                    return $result;
                }


            } else {

                $result = (object) [
                    "error" => 2

                ];

                return $result;

            }
    }



    }
}





}