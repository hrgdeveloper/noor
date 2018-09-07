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
                if ($active > 0) {

                    $stnew = $this->conn->prepare("SELECT  p.pic , p.pic_thumb from users u LEFT join user_profile p on u.user_id = p.user_id WHERE u.user_id like ?");
                    $stnew->bind_param("i", $user_id);
//
//
//
//
                    $stnew->execute();
                   $result = $stnew->get_result()->fetch_assoc();




                    $this->updateUserSmsSate($user_id);
                    $user = array();
                    $user["user_id"] = $user_id;
                    $user["mobile"] = $mobile;
                    $user["apikey"] = $apikey;
                    $user["active"] = $active;
                    $user["username"] = $username;
                    $user["pic"] = $result['pic'] ;
                    $user["pic_thumb"] = $result['pic_thumb'];
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
    public function updateProfilePic($user_id ,$last_picname) {
        $response=array();
        $new_width = "250";
        $new_height = "250";
        $quality = 100 ;

        $profile_pic_path = '../uploads/user_profile/pic/';
        $profile_thumb_path = '../uploads/user_profile/thumb/';
        $pic_info = pathinfo($_FILES['pic']['name']);
        $extension = $pic_info['extension'];

        $pic_name_to_store = $user_id .'_'. rand(1111,9999).'.'. $extension  ;
        $pic_path = $profile_pic_path . $pic_name_to_store;
        $thumb_path = $profile_thumb_path . $pic_name_to_store;

        $filetype =$_FILES['pic']['type'];


        list($width_orig,$height_orig) = getimagesize($_FILES['pic']['tmp_name']);
        $ratio_orig = $width_orig/$height_orig;
        if ($new_width/$new_height > $ratio_orig) {
            $new_width = $new_height*$ratio_orig;
        } else {
            $new_height = $new_width/$ratio_orig;
        }



        if($filetype == "image/jpeg")
        {
            $imagecreate = "imagecreatefromjpeg";
            $imageformat = "imagejpeg";
        }
        if($filetype == "image/png")
        {
            $imagecreate = "imagecreatefrompng";
            $imageformat = "imagepng";
        }
        if($filetype == "image/gif")
        {
            $imagecreate= "imagecreatefromgif";
            $imageformat = "imagegif";
        }

        $image_new = imagecreatetruecolor($new_width, $new_height);

        $uploadedfile = $_FILES['pic']['tmp_name'];

        $image = $imagecreate($uploadedfile);
        // vase inke age png bood back groundesh siah nashe
        if($extension == "gif" or $extension == "png"){
            imagecolortransparent($image_new, imagecolorallocatealpha($image_new, 0, 0, 0, 127));
            imagealphablending($image_new, false);
            imagesavealpha($image_new, true);
        }

        imagecopyresampled($image_new, $image, 0, 0, 0, 0, $new_width, $new_height, $width_orig, $height_orig);
        $imageformat($image_new, $thumb_path,$quality);

        imagedestroy($image_new);



        if (move_uploaded_file($uploadedfile , $pic_path)) {
            $stmtDel = $this->conn->prepare("DELETE FROM user_profile where user_id = ?");
            $stmtDel->bind_param("i", $user_id);
            $stmtDel->execute();

            $stmt = $this->conn->prepare("INSERT INTO user_profile (user_id,pic,pic_thumb)  values (?,?,?)");
            $stmt->bind_param("iss", $user_id, $pic_name_to_store, $pic_name_to_store);
            $stmt->execute();




            if ($this->conn->affected_rows >0 ) {

                $stmt = $this->conn->prepare("SELECT  pic,pic_thumb FROM user_profile where user_id like ?") ;

                $stmt->bind_param("i",$user_id);
                $stmt->execute();
                $stmt->bind_result($pic,$pic_thumb);

                $stmt->store_result();
                $stmt->fetch();
                $stmt->close();
                $response['error'] = false;
                $response['message'] = $pic;
                // inja esme akse qabli ro migirm o az tariqe on akse qabli ro pak mikoim
                if ($last_picname!="n" && $last_picname!=null) {
                    try {
                        unlink($profile_pic_path.$last_picname);
                        unlink($profile_thumb_path.$last_picname);
                    }catch (Exception $e) {

                    }

                }

                return $response;

            }
            else {
                $stmt->close();
                $response['error'] = true;
                $response['message'] = "خطا در ساخت سطر جدید ";
                return $response;

            }

        }else {

            $response['error'] = true;
            $response['message'] = "خطلا در اپلود عسکس";
            return $response;
        }
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
        $stmt = $this->conn->prepare("SELECT c.chanel_id, c.name,c.description,c.thumb, a.username, ms1.message as last_message,ms1.type ,
                                     ms1.updated_at FROM chanels AS c left JOIN message AS ms1 ON ms1.message_id = 
                                    (SELECT message_id FROM message WHERE chanel_id = c.chanel_id and active = 1 ORDER BY message_id DESC LIMIT 1)
                                    left join admin_login a on ms1.admin_id = a.admin_id order by c.chanel_id ");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows>0) {
            $stmt_tead = $this->conn->prepare("SELECT c.chanel_id ,COUNT(m.message_id) as count FROM chanels c left join message m on c.chanel_id = m.chanel_id AND m.active = 1 GROUP BY c.chanel_id
order by c.chanel_id 
");
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
    public function getDeletedCount() {
        $response=array();
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM message where active = 0 ") ;
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->store_result();
        $stmt->fetch();
        $stmt->close();
        $response['error'] = false;
        $response['count'] = $count;
        return $response;
    }
    public function getDeletedList() {
        $response=array();
        $response["message_ids"]  = array();
        $stmt = $this->conn->prepare("SELECT message_id  FROM message where active = 0 ") ;
        $stmt->execute();
        $result = $stmt->get_result();
        while ($single = $result->fetch_assoc()) {
            array_push($response["message_ids"] , $single);
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
public function getAllMessages($chanel_id ,$message_id,$user_id)
{
    $start=0;
    $limit = 20;

    $total = mysqli_num_rows(mysqli_query($this->conn, "SELECT message_id from message where active = 1 and 
 chanel_id like $chanel_id "));

    if ($total==0) {
        $result = (object) [
            "error" => 1

        ];

        return $result;

    }else {

        if ($message_id==0) {
            $stmt = $this->conn->prepare("select sub.message_id , sub.admin_id, sub.chanel_id ,
 sub.message , sub.type,sub.pic_thumb,sub.lenth,sub.time,sub.filename,sub.active , sub.url,sub.updated_at , IFNULL(l.message_id,0) as liked from 
 (select * from message where chanel_id like ? and active like 1 and message_id > ? ORDER by message_id DESC limit $start, $limit) sub 
 left join likes l on l.message_id = sub.message_id and l.user_id like ?  order by sub.message_id ASC
 		");
        }else {
            $limit=12412415124313;
            $stmt = $this->conn->prepare("select sub.message_id , sub.admin_id, sub.chanel_id ,
             sub.message , sub.type,sub.pic_thumb,sub.lenth,sub.time,sub.filename, sub.active , sub.url,sub.updated_at , IFNULL(l.message_id,0) as liked from 
            (select * from message where chanel_id like ? and active like 1 and message_id > ? ORDER by message_id DESC limit $start, $limit) sub 
            left join likes l on l.message_id = sub.message_id and l.user_id like ?  order by sub.message_id ASC
 		");

        }
                  // in code ye selecte khili mamolie az tamamie sotonhaye message join ba like faqat vase inke tartibe desc az aval be akhar she injori kardim



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
                        "error" => 2
                    ];

                    return $result;
                }
    }

}
public function getAllTopMessages($chanel_id ,$top_id,$user_id)
    {
        $start=0;
        $limit = 20;

                $stmt = $this->conn->prepare("select sub.message_id , sub.admin_id, sub.chanel_id ,
 sub.message , sub.type,sub.pic_thumb,sub.lenth,sub.time,sub.filename,sub.active,sub.url,sub.updated_at , IFNULL(l.message_id,0) as liked from 
 (select * from message where chanel_id like ? and active like 1 and message_id < ? ORDER by message_id DESC limit $start, $limit) sub 
 left join likes l on l.message_id = sub.message_id and l.user_id like ?  order by sub.message_id ASC
 		");

            $stmt->bind_param("iii", $chanel_id,$top_id,$user_id);
            $stmt->execute();
            $content = $stmt->get_result();

            if ($content->num_rows>0) {
                $result = (object) [
                    "error" => 0 ,
                    "content" => $content

                ];

                return $result;
            }  else {


                $result = (object) [
                    "error" => 1
                ];

                return $result;
            }


    }
public function getLastCounts($chanel_id,$message_id) {

    $total = mysqli_num_rows(mysqli_query($this->conn, "SELECT message_id from message where active = 1 and 
 chanel_id like $chanel_id  and message_id > $message_id"));

    return $total;
}
public function setLike($type ,$user_id , $message_id) {
        $response = array();
        if ($type==1) {

            $stmt = $this->conn->prepare("INSERT INTO likes VALUES (?,?)");
            $stmt->bind_param("ii",$user_id,$message_id);
            $stmt->execute();
            $result = $this->conn->affected_rows;
            if ($result>0) {
                $response["error"]=false;
                $response["message"]= "liked";

            }else {
                $response["error"]=true;
                $response["message"]= "error in like";
            }
            return $response;


        }else  {
            $stmt = $this->conn->prepare("DELETE FROM likes WHERE user_id like ? and message_id like ?");
            $stmt->bind_param("ii",$user_id,$message_id);
            $stmt->execute();
            $result = $this->conn->affected_rows;
            if ($result>0) {
                $response["error"]=false;
                $response["message"]= "unliked";

            }else {
                $response["error"]=true;
                $response["message"]= "error in unlike";
            }
            return $response;
        }
}
public function makeComment($chanel_id , $text ,$user_id ,$message_id ) {
        $response=array();
         if ($this->isUsernameCompelete($user_id)==0) {
             $response['error'] = true ;
             $response['message'] = 'نام کاربری تکمیل نگردیده است' ;
             return $response;

         }else {
             $stmt = $this->conn->prepare("INSERT INTO comment (chanel_id,text,user_id,message_id)  values (?,?,?,?)");

             $stmt->bind_param("isii",$chanel_id, $text, $user_id, $message_id);

             if ($stmt->execute()) {
                 if ($this->conn->affected_rows >0) {
                     $response['error'] = false ;
                     $response['message'] = 'نظر شما ثبت و بعد از تایید به نمایش در خواهد آمد' ;
                     return $response;
                 }else {
                     $response['error'] = true ;
                     $response['message'] = 'خطا در ثبت نظر' ;
                     return $response;
                 }
             }else {

                 $response['error'] = true ;
                 $response['message'] = 'خطا در ثبت نظر پاییت' ;
                 return $response;

             }
         }




}
public function  getAllComments($message_id) {
        $response=array();

         $stmt = $this->conn->prepare("select count(*) as count from likes where message_id like ? ");
         $stmt->bind_param("i", $message_id);
         $stmt->execute();
         $stmt->bind_result($count);
         $stmt->store_result();
         $stmt->fetch();
         $response['likes'] = $count;

        $stComments= $this->conn->prepare("SELECT c.text , c.created_at , u.username , p.pic_thumb FROM comment c
 join users u on c.user_id = u.user_id LEFT JOIN user_profile p on u.user_id = p.user_id WHERE c.message_id like ? and visible like 1 ");
    $stComments->bind_param("i",$message_id);

       if ($stComments->execute()) {
           $result = $stComments->get_result();
           if ($result->num_rows >0) {
               $response['error'] = false ;
               $response["comments"] = array();
               while ($single = $result->fetch_assoc()) {
                   array_push($response["comments"] , $single);

               }

               return $response;

           } else {
               $response['error'] = true ;
               $response['message'] = "هیج نظری برای این پیام ثبت نگردیده است";
               return $response;
           }

       } else {
           $response['error'] = true ;
           $response['message'] = "خطا در دسترسی به نظرات .. لطفا دوباره تلاش نمایید";
           return $response;
       }
}
public function isUsernameCompelete($user_id){
    $stmt = $this->conn->prepare("select username  from users where user_id like ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($username);
    $stmt->store_result();
    $stmt->fetch();

    if (strcasecmp($username,"e")==0) {
        return 0 ;
    }else {
        return 1 ;
    }
}

}