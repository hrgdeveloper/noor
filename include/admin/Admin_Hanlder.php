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
        $stmt = $this->conn->prepare("SELECT user_id,mobile,username,status,active,created_at FROM users");
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
        $thumb_path = '../uploads/chanel_thumb/';
        $prifle_pic = '../uploads/chanel_pics/';
        $pic_info = pathinfo($_FILES['pic']['name']);
        $pic_name_to_store = $details['name'] . '_' . $pic_info['basename'];
        $pic_path = $prifle_pic . $pic_name_to_store;

        $filetype =$_FILES['pic']['type'];


        $extension = $pic_info['extension'];

        $thumb_path_to_store = $thumb_path . $pic_name_to_store ;

        list($filewidth,$fileheight) = getimagesize($_FILES['pic']['tmp_name']);


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
        $new_width = "250";
        $new_height = "250";
        $image_new = imagecreatetruecolor($new_width, $new_height);

        $uploadedfile = $_FILES['pic']['tmp_name'];
        $image = $imagecreate($uploadedfile);
        // vase inke age png bood back groundesh siah nashe
        if($extension == "gif" or $extension == "png"){
            imagecolortransparent($image_new, imagecolorallocatealpha($image_new, 0, 0, 0, 127));
            imagealphablending($image_new, false);
            imagesavealpha($image_new, true);
        }

        imagecopyresampled($image_new, $image, 0, 0, 0, 0, $new_width, $new_height, $filewidth, $fileheight);
        $imageformat($image_new, $thumb_path_to_store);


        if (!move_uploaded_file($_FILES['pic']['tmp_name'], $pic_path)) {

          return 0 ;
        }
        else {

            $this->conn->begin_transaction();
            $commit = true ;
            try {
                $stmt = $this->conn->prepare("INSERT INTO chanels(name,description,thumb) values(?,?,?)");
                $stmt->bind_param("sss", $details['name'], $details['description'], $pic_name_to_store);
                $stmt->execute();
                $stmt->close();

                // Check for successful insertion

                    $last_id = $this->conn->insert_id;
                    $st= $this->conn->prepare("INSERT INTO chanel_photos(chanel_id,photo) values(?,?)");
                    $st->bind_param("is",$last_id,$pic_name_to_store);
                    $st->execute();
                    $st->close();
                    $this->conn->commit();

            }catch (DOException $e) {
                $this->conn->rollback();
                $commit=false;
            }

            if ($commit) {
                return 1 ;
            }else {
                return 2 ;
            }



                }






    }
}