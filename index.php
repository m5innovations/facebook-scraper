<?php

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

require_once './Facebook/autoload.php';
require_once './Facebook/Exceptions/FacebookResponseException.php';
require_once './Facebook/Exceptions/FacebookSDKException.php';
require_once './Facebook/Helpers/FacebookRedirectLoginHelper.php';


function filter_array_keys($key_list, $array) {
    $newarray = [];
    foreach($array as $k=>$v) {
        if(in_array($k, $key_list)) {
            $newarray[$k] = $v;
        }
    }
    return $newarray;
}


$appId = "648631726055035";
$appSecret = "374467a38c1c7f451fb185aed34c96dc";

ini_set('display_errors', 1);
error_reporting(E_ALL);

$fb = new Facebook([
    'app_id' => $appId,
    'app_secret' => $appSecret,
    'default_graph_version' => 'v8.0'
]);


// Replace value in Quotes
$accessToken = "replace text with access token";

$userids_file = file_get_contents("userids.txt");

$userids = array_filter(array_map('trim', explode("\n", $userids_file)));


$requied_fields = ['from', 'message', 'parent_id', 'comments', 'publishing_stats', 'shares'];

foreach ($userids as $uid) {
    $postData = "";
    try {
        $userPosts = $fb->get("/$uid/posts", $accessToken);
        // echo $userPosts.'<br>';
        $postBody = $userPosts->getDecodedBody();
        // echo $postBody.'<br>';
        $postData = $postBody["data"];
        // echo $postData.'<br>';
    } catch (FacebookResponseException $e) {
        echo '<pre>' . $e . '</pre>';
        continue;
    } catch (FacebookSDKException $e) {

        echo '<pre>' . $e . '</pre>';
        continue;
    }

    $all_posts = [];
    foreach($all_posts as $post) {
        $all_posts[] = filter_array_keys($requied_fields, $post);
    }

    file_put_contents("user_posts_$uid.txt", json_encode($all_posts));
}

$conn = mysqli_connect($servername, $username, $password, $database);
$conn->set_charset("utf8mb4");

$date = date("Y/m/d");

$q = "CREATE TABLE IF NOT EXISTS `".$date."` (
        id VARCHAR(177) NOT NULL PRIMARY KEY,
		sm VARCHAR(177) 'facebook',
        name VARCHAR(177) NOT NULL,
		profileimg VARCHAR(177),
		inpost_url VARCHAR(255),
        screen_name VARCHAR(177) NOT NULL,
        text TEXT,
        created_at DATETIME,
		repost_count INT,
		likes_count INT,
		comments_count INT,
		media_url_image VARCHAR(255),
		media_url_video VARCHAR(255)

        )";
$res = mysqli_query($conn, $q);

?>