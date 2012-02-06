<?php

define("GRAPH_URL", "https://graph.facebook.com/");

class myFB
{

    private $access_token;
    private $profile_obj;
    private $permission_obj;
    private $log = array();
    private $privacy = array("value" => "EVERYONE");

    function __construct($access_token)
    {
        echo "Constructing user object... <br />";

        $this->access_token = $access_token;

        // simpan data profil
        $action = "me";
        $field  = array("id", "name", "link", "username", "birthday", "hometown", "gender", "email");
        $data = array(
            "fields"      => implode(",", $field),
        );
        $response_obj = $this->graphGet($action, $data);
        if (!$response_obj)
        {
            $this->logAdd("Fatal Error: tidak bisa konstruksi objek user.");
        }
        else
        {
            $this->profile_obj = $response_obj;
        }

        // simpan data permision
        $action       = "me/permissions";
        $response_obj = $this->graphGet($action)->data[0];
        if (!$response_obj)
        {
            $this->logAdd("Fatal Error: tidak bisa mendapatkan data permissions.");
        }
        else
        {
            $this->permission_obj = $response_obj;
        }
    }

    function __destruct()
    {
        echo "Destroying object...";
    }

    private function doCURL($type, $action, $data)
    {
        $data['access_token'] = $this->access_token;

        if ($type == "GET")
        {
            $query = http_build_query($data);
            $url   = GRAPH_URL . "$action?$query";
            $url2  = GRAPH_URL . "$action";
        }
        else
        {
            $url  = GRAPH_URL . "$action";
            $url2 = $url;
        }

        $this->logAdd("$type $url2<pre>" . var_export($data, true) . " </pre>");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($type == "POST")
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $responses = curl_exec($ch);
        curl_close($ch);

        if ($responses)
        {
            $responses = json_decode($responses);
            if ($responses->error)
            {
                $error_type    = $responses->error->type;
                $error_message = $responses->error->message;
                $this->logAdd("$error_type : $error_message");
                return false;
            }
            else
            {
                $this->logAdd("Responses:<pre>" . var_export($responses, true) . " </pre>");
                return $responses;
            }
        }
        else
        {
            $this->logAdd("Fatal Error: gagal koneksi cURL ke server FB");
            return false;
        }
    }

    private function graphGet($action, $data = array())
    {
        return $this->doCURL("GET", $action, $data);
    }

    private function graphPost($action, $data = array())
    {
        return $this->doCURL("POST", $action, $data);
    }

    public function permissionHas($permission)
    {
        $out = $this->permission_obj->$permission;
        $this->logAdd("Has $permission = $out");
        return $out;
    }

    private function logAdd($string)
    {
        $this->log[] = $string;
    }

    public function logPrint()
    {
        $n = 1;
        foreach ($this->log as $log)
        {
            echo "[$n] $log <br />";
            $n++;
        }
    }

    public function uidGet()
    {
        return $this->profile_obj->id;
    }

    public function getProfile()
    {
        var_dump($this->profile_obj);
    }

    public function feedPost($message = "", $caption = "", $name = "", $link = "", $description = "", $picture = "", $actions = "")
    {
        $this->logAdd("Posting feed...");

        if (!$this->permissionHas("publish_stream"))
        {
            return false;
        }

        $post_data = array(
            "message"     => $message,
            "caption"     => $caption,
            "name"        => $name,
            "link"        => $link,
            "description" => $description,
            "picture"     => $picture,
            "actions"     => json_encode($actions),
            "privacy"     => json_encode($this->privacy),
        );

        if (empty($actions))
            unset($post_data['actions']);
        if (empty($this->privacy))
            unset($post_data['privacy']);

        $action       = "me/feed";
        $response_obj = $this->graphPost($action, $post_data);
        $feed_id      = $response_obj->id;
        if ($feed_id)
        {
            return $feed_id;
        }
        else
        {
            $this->logAdd("Gagal memposting feed.");
            return false;
        }
    }

    public function albumExist($album_id)
    {
        $this->logAdd("Is album $album_id exist?");
        $action       = "$album_id";
        $response_obj = $this->graphGet($action)->id;
        if (!$response_obj)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function albumCreate($name, $message)
    {
        $this->logAdd("Creating album...");

        if (!$this->permissionHas("publish_stream"))
        {
            return false;
        }

        $post_data = array(
            "name"    => $name,
            "message" => $message,
            "privacy" => json_encode($this->privacy),
        );

        $action       = "me/albums";
        $response_obj = $this->graphPost($action, $post_data);
        $album_id     = $response_obj->id;
        if ($album_id)
        {
            return $album_id;
        }
        else
        {
            $this->logAdd("Gagal membuat album.");
            return false;
        }
    }

    public function photoUpload($album_id, $file, $message)
    {
        $this->logAdd("Uploading photo...");

        if (!$this->permissionHas("publish_stream"))
        {
            return false;
        }

        $post_data = array(
            "source"  => "@" . $file,
            "message" => $message,
        );

        $action       = "$album_id/photos";
        $response_obj = $this->graphPost($action, $post_data);
        $photo_id     = $response_obj->id;
        if ($photo_id)
        {
            return $photo_id;
        }
        else
        {
            $this->logAdd("Gagal upload foto.");
            return false;
        }
    }

}

//$fb = new myFB("AAABZBG3J08o4BAMNU2PMb20xGz3eRQNe8Y38ClaJRK1JrZAkEWbdOfIXuTzdz2uisy4UquZB9o4vdRa4cZA4CBUUf0olsJsZD");
//$fb->albumCreate("coba", "nyoba lagi");
//$fb->feedPost();
//$fb->albumExist("10150555751307818")
//$album_id = $fb->albumCreate("coba", "nyoba lagi");
//$fb->photoUpload($album_id, "teladan.jpg", "coba");
//$fb->logPrint();