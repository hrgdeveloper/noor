<?php

class Admin_Hanlder
{

    private $conn;

    function __construct()
    {
        require_once __DIR__ . '/../DbConnect.php';

        $coonect = new DbConnect();
        $this->conn = $coonect->connect();
    }

    // ************************************************************ registeration ********************************************\\

    public function createAdmin($username, $password)
    {

        // Generating password hash
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Generating API key
        $api_key = $this->generateApiKey();

        $stmt = $this->conn->prepare("INSERT INTO admin_login(username, password , apikey) values(?,?,?)");


        $stmt->bind_param("sss", $username, $password_hash, $api_key);

        $result = $stmt->execute();


        $stmt->close();

        // Check for successful insertion
        if ($result) {
            return 1;

        } else {
            // Failed to create user
            return 0;
        }
    }

    public function checkLogin($username, $password)
    {
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

            if (password_verify($password, $password_hash)) {
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

    public function getAdminByUsername($username)
    {
        $stmt = $this->conn->prepare("SELECT  admin_id,username,apikey FROM admin_login WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;

    }

    private function generateApiKey()
    {
        return md5(uniqid(rand(), true));
    }

    // ************************************************************ user_Oprations ********************************************\\

    public function getAllUsers()
    {
        $stmt = $this->conn->prepare("SELECT user_id,mobile,username,status,active,created_at FROM users order by 
user_id DESC 
");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result;
        } else {
            return null;
        }


    }

    public function updateUserActive($status, $user_id)
    {
        $stmt = $this->conn->prepare("update users set active = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $status, $user_id);
        $stmt->execute();
        if ($this->conn->affected_rows == 0) {
            return false;
        } else {
            return true;
        }
    }


// ************************************************************ channel_Oprations ********************************************\\

    public function makeChanel($details)
    {
        if ($this->chanelExists($details['name'])) {

            $oject = (object)[
                "return" => 3,
                "chanel" => null
            ];
            return $oject;

        } else {
            $thumb_path = '../uploads/chanel_thumb/';
            $prifle_pic = '../uploads/chanel_pics/';
            $pic_info = pathinfo($_FILES['pic']['name']);
            $extension = $pic_info['extension'];

            $pic_name_to_store = rand(1111, 9999) . '_' . $pic_info['basename'];
            $pic_path = $prifle_pic . $pic_name_to_store;

            $filetype = $_FILES['pic']['type'];


            $thumb_path_to_store = $thumb_path . $pic_name_to_store;

            list($filewidth, $fileheight) = getimagesize($_FILES['pic']['tmp_name']);


            if ($filetype == "image/jpeg") {
                $imagecreate = "imagecreatefromjpeg";
                $imageformat = "imagejpeg";
            }
            if ($filetype == "image/png") {
                $imagecreate = "imagecreatefrompng";
                $imageformat = "imagepng";
            }
            if ($filetype == "image/gif") {
                $imagecreate = "imagecreatefromgif";
                $imageformat = "imagegif";
            }
            $new_width = "250";
            $new_height = "250";
            $image_new = imagecreatetruecolor($new_width, $new_height);

            $uploadedfile = $_FILES['pic']['tmp_name'];
            $image = $imagecreate($uploadedfile);
            // vase inke age png bood back groundesh siah nashe
            if ($extension == "gif" or $extension == "png") {
                imagecolortransparent($image_new, imagecolorallocatealpha($image_new, 0, 0, 0, 127));
                imagealphablending($image_new, false);
                imagesavealpha($image_new, true);
            }

            imagecopyresampled($image_new, $image, 0, 0, 0, 0, $new_width, $new_height, $filewidth, $fileheight);
            $imageformat($image_new, $thumb_path_to_store);


            if (!move_uploaded_file($_FILES['pic']['tmp_name'], $pic_path)) {
                $oject = (object)[
                    "return" => 0,
                    "chanel" => null
                ];
                return $oject;
            } else {
                $last_insert = null;
                $this->conn->begin_transaction();
                $commit = true;
                try {
                    $stmt = $this->conn->prepare("INSERT INTO chanels(name,description,thumb,admin_id) values(?,?,?,?)");
                    $stmt->bind_param("sssi", $details['name'], $details['description'], $pic_name_to_store, $details['admin_id']);
                    $stmt->execute();
                    $stmt->close();
                    $last_insert = $this->conn->insert_id;
                    // Check for successful insertion

                    $last_id = $this->conn->insert_id;
                    $st = $this->conn->prepare("INSERT INTO chanels_photos(chanel_id,photo) values(?,?)");

                    $st->bind_param("is", $last_id, $pic_name_to_store);
                    $st->execute();
                    $st->close();
                    $this->conn->commit();

                } catch (DOException $e) {
                    $this->conn->rollback();
                    $commit = false;
                }

                if ($commit) {


                    $stmt_last = $this->conn->prepare("SELECT c.chanel_id, c.name,c.description,c.thumb, a.username, ms1.message as last_message,ms1.type , ms1.updated_at
                          FROM chanels AS c join admin_login a on c.admin_id = a.admin_id
                          left JOIN message AS ms1 ON ms1.message_id = (SELECT message_id FROM message
                          WHERE chanel_id = c.chanel_id ORDER BY message_id DESC LIMIT 1) where c.chanel_id like ? ");
                    $stmt_last->bind_param("i", $last_insert);
                    $stmt_last->execute();
                    $chanel = $stmt_last->get_result();

                    $oject = (object)[

                        "return" => 1,
                        "chanel" => $chanel
                    ];
                    return $oject;

                } else {
                    $oject = (object)[
                        "return" => 2,
                        "chanel" => null
                    ];
                    return $oject;
                }


            }
        }


    }

    private function chanelExists($name)
    {
        $stmt = $this->conn->prepare("select chanel_id from chanels where name like ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();

        $result = $stmt->get_result();

        return $result->num_rows > 0;

    }

    public function getAllChenls()
    {
        $response = array();
        $response['chanels'] = array();
        // in query baes mishe ke akharin payam vared shode vase har canalam begirim
        $stmt = $this->conn->prepare("SELECT c.chanel_id, c.name,c.description,c.thumb, a.username,ms1.updated_at, ms1.message as last_message,ms1.type ,
 COUNT(co.comment_id) as cm_count FROM chanels AS c join admin_login a on c.admin_id = a.admin_id left JOIN message AS ms1 ON ms1.message_id = 
 (SELECT message_id FROM message WHERE chanel_id = c.chanel_id ORDER BY message_id DESC LIMIT 1)
  left join comment co on c.chanel_id=co.chanel_id GROUP BY c.chanel_id ");

        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $response['error'] = false;
            while ($single_chanel = $result->fetch_assoc()) {

                array_push($response['chanels'], $single_chanel);
            }

        } else {
            $response['error'] = true;
            $response['message'] = "هیچ کانالی ثبت نشده است";
        }
        return $response;

    }


    public function getAllComments($chanel_id)
    {
        $response = array();
        $stComments = $this->conn->prepare("SELECT c.comment_id , c.text ,c.visible, c.created_at  , u.username , p.pic_thumb 
                                               FROM comment c  
                                              join users u on c.user_id = u.user_id LEFT JOIN user_profile p on u.user_id = p.user_id WHERE c.chanel_id like ? 
                                              ORDER BY c.created_at DESC
                                              ");
        $stComments->bind_param("i", $chanel_id);

        if ($stComments->execute()) {
            $result = $stComments->get_result();
            if ($result->num_rows > 0) {
                $response['error'] = false;
                $response["comments"] = array();
                while ($single = $result->fetch_assoc()) {
                    array_push($response["comments"], $single);

                }

                return $response;

            } else {
                $response['error'] = true;
                $response['message'] = "هیج نظری در  این کانال  ثبت نگردیده است";
                return $response;
            }

        } else {
            $response['error'] = true;
            $response['message'] = "خطا در دسترسی به نظرات .. لطفا دوباره تلاش نمایید";
            return $response;
        }
    }

    public function updateCommentState($comment_id, $state)
    {
        $stmt = $this->conn->prepare("update comment set visible = ? WHERE comment_id = ?");
        $stmt->bind_param("ii", $state, $comment_id);
        $stmt->execute();
        if ($this->conn->affected_rows == 0) {
            return false;
        } else {
            return true;
        }
    }

//============================================================================NewMessageOprations=======================================\\

    public function makePlainMessage($content, $chanel_id)
    {

        $stmt = $this->conn->prepare("INSERT INTO message (admin_id , chanel_id , message , type ) values (?,?,?,?)");
        $stmt->bind_param("iisi", $content['admin_id'], $chanel_id, $content['message'], $content['type']);
        $stmt->execute();
        if ($this->conn->affected_rows > 0) {
            $last_id = $this->conn->insert_id;
            $stmt = $this->conn->prepare("select message_id  ,m.admin_id , chanel_id , message , pic_thumb , type , lenth, time , url , updated_at ,a.username as admin_name  from message m 
                 join admin_login a on m.admin_id = a.admin_id  where message_id like ?");

            $stmt->bind_param("i", $last_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result;


        } else {
            return null;

        }

    }

    public function makePicMessage($content, $chanel_id)
    {
        $new_width = "200";
        $new_height = "200";
        $quality = 9;

        $message_pic_path = '../uploads/message/pic/';
        $message_thumb_path = '../uploads/message/pic_thumb/';
        $pic_info = pathinfo($_FILES['file']['name']);
        $extension = $pic_info['extension'];

        $pic_name_to_store = rand(1111, 9999) . '_' . substr(abs(crc32(uniqid())), 0, 6) . '.' . $extension;
        $pic_path = $message_pic_path . $pic_name_to_store;
        $thumb_path = $message_thumb_path . $pic_name_to_store;

        $filetype = $_FILES['file']['type'];


        list($width_orig, $height_orig) = getimagesize($_FILES['file']['tmp_name']);
        $ratio_orig = $width_orig / $height_orig;
        if ($new_width / $new_height > $ratio_orig) {
            $new_width = $new_height * $ratio_orig;
        } else {
            $new_height = $new_width / $ratio_orig;
        }


        if ($filetype == "image/jpeg" || $filetype == "image/jpg" ) {
            $imagecreate = "imagecreatefromjpeg";
            $imageformat = "imagejpeg";
        }

        if ($filetype == "image/png") {
            $imagecreate = "imagecreatefrompng";
            $imageformat = "imagepng";
        }
        if ($filetype == "image/gif") {
            $imagecreate = "imagecreatefromgif";
            $imageformat = "imagegif";
        }

        $image_new = imagecreatetruecolor($new_width, $new_height);

        $uploadedfile = $_FILES['file']['tmp_name'];

        $image = $imagecreate($uploadedfile);
        // vase inke age png bood back groundesh siah nashe
        if ($extension == "gif" or $extension == "png") {
            imagecolortransparent($image_new, imagecolorallocatealpha($image_new, 0, 0, 0, 127));
            imagealphablending($image_new, false);
            imagesavealpha($image_new, true);
        }

        imagecopyresampled($image_new, $image, 0, 0, 0, 0, $new_width, $new_height, $width_orig, $height_orig);
        $imageformat($image_new, $thumb_path, $quality);


        $temp = file_get_contents($thumb_path);
        imagedestroy($image_new);

//
//    $image_base_64 =  base64_encode($temp);
//    $final_thumb  = $image = 'data:image/'.$extension.';base64,'.$image_base_64;

        $lenth = round(filesize($uploadedfile) / 1024);

        if (move_uploaded_file($uploadedfile, $pic_path)) {
            $stmt = $this->conn->prepare("INSERT INTO message (admin_id , chanel_id , message,type , pic_thumb    , lenth  , url  ) values (?,?,?,? , ? , ? ,?)");
            $stmt->bind_param("iisisss", $content['admin_id'], $chanel_id, $content['message'], $content['type'], $pic_name_to_store, $lenth, $pic_name_to_store);
            $stmt->execute();

            if ($this->conn->affected_rows > 0) {
                $last_id = $this->conn->insert_id;
                $stmt = $this->conn->prepare("select message_id  ,m.admin_id , chanel_id , message , pic_thumb , type , lenth, time , url , updated_at ,a.username as admin_name  from message m 
                 join admin_login a on m.admin_id = a.admin_id  where message_id like ?");

                $stmt->bind_param("i", $last_id);
                $stmt->execute();
                $result = $stmt->get_result();


                $object = (object)[
                    "error" => 0,
                    "content" => $result
                ];
                return $object;

            } else {
                $object = (object)[
                    "error" => 2
                ];

                return $object;


            }

        } else {

            $object = (object)[
                "error" => 1
            ];
            return $object;

        }


    }

    public function makeVideoMessage($content, $chanel_id)
    {
        $video_path = '../uploads/videos/video/';
        $video_thumb_path = '../uploads/videos/thumb/';
        $video_info = pathinfo($_FILES['file']['name']);
        $vide_oextension = $video_info['extension'];
        $video_name_to_store_temp = rand(1111, 9999) . '_' . substr(abs(crc32(uniqid())), 0, 6);
        $video_name_to_store = $video_name_to_store_temp . '.' . $vide_oextension;


        $thumb_info = pathinfo($_FILES['thumb']['name']);
        $thumb_extenstion = $thumb_info['extension'];

        $thumb_name_to_store = $video_name_to_store_temp . '.' . $thumb_extenstion;

        $video_path_toStore = $video_path . $video_name_to_store;

        $thumb_path_toStore = $video_thumb_path . $thumb_name_to_store;

        if (move_uploaded_file($_FILES['thumb']['tmp_name'], $thumb_path_toStore)) {


            $lenth = round(filesize($_FILES['file']['tmp_name']) / 1024);
            if (move_uploaded_file($_FILES['file']['tmp_name'], $video_path_toStore)) {
                $stmt = $this->conn->prepare("INSERT INTO message (admin_id , chanel_id , message,type , pic_thumb    , lenth  , time, url  ) values (?,?,?,? , ? , ?,?,?)");
                $stmt->bind_param("iisissss", $content['admin_id'], $chanel_id, $content['message'], $content['type'], $thumb_name_to_store, $lenth, $content['time'], $video_name_to_store);
                $stmt->execute();

                if ($this->conn->affected_rows > 0) {
                    $last_id = $this->conn->insert_id;
                    $stmt = $this->conn->prepare("select message_id  ,m.admin_id , chanel_id , message , pic_thumb , type , lenth, time , url , updated_at ,a.username as admin_name  from message m 
                 join admin_login a on m.admin_id = a.admin_id  where message_id like ?");

                    $stmt->bind_param("i", $last_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    $object=(object) [
                        "error" => 0 ,
                        "content" => $result

                    ];
                    return $object;

                }
            } else {
                $object=(object) [
                    "error" => 2

                ];
                return $object;
            }


        }else {
            $object=(object) [
                "error" => 1

            ];
            return $object;
        }


    }
}