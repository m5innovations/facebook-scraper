<!-- REFERENCES -->
<!-- 
https://developers.facebook.com/docs/graph-api/reference/v5.0/user
https://developers.facebook.com/docs/graph-api/reference/v5.0/post
https://developers.facebook.com/docs/graph-api/reference/v5.0/post/comments
https://developers.facebook.com/docs/graph-api/using-graph-api/
https://www.youtube.com/watch?v=vsuDgQVLKwQ
-->

<?php

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

require_once './Facebook/autoload.php';
require_once './Facebook/Exceptions/FacebookResponseException.php';
require_once './Facebook/Exceptions/FacebookSDKException.php';
require_once './Facebook/Helpers/FacebookRedirectLoginHelper.php';

// FACEBOOK API DATA
$appId = "648631726055035";
$appSecret = "374467a38c1c7f451fb185aed34c96dc";
$accessToken = "replace text with access token";

// CREATING FACEBOOK OBJECT
$fb = new Facebook([
    'app_id' => $appId,
    'app_secret' => $appSecret,
    'default_graph_version' => 'v2.10',
]);

// READING USER-IDS FROM TEXT FILE
$userids_file = file_get_contents("userids.txt");
$userids = array_filter(array_map('trim', explode("\n", $userids_file)));

// ARRAY WHERE ALL DATA IS SAVED
$allUserData = [];

// LOOPING THROUGH EACH USER-ID FOR DATA
foreach ($userids as $uid) {
    $data = [];

    // GETTING USER DATA
    // FULLNAME, PROFILE_PIC, USERNAME
    try {
        $response = $fb->get(
            "/$uid/",
            array ('fields' => 'name','profile_pic','username'),
            $accessToken
        );
    } catch(FacebookResponseException $e) {
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(FacebookSDKException $e) {
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    $graphNode = $response->getGraphNode();
    $data['fullname'] = $graphNode['name'];
    $data['profile_pic'] = $graphNode['profile_pic'];
    $data['username'] = $graphNode['username'];


    // GETTING ALL POST IDS FROM USER FEED
    // THEN SAVING IT IN AN ARRAY
    try {
    $response = $fb->get(
        "/$uid/feed",
        $accessToken
    );
    } catch(FacebookResponseException $e) {
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(FacebookSDKException $e) {
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }
    $graphNode = $response->getGraphNode();

    $posts = [];
    foreach ($grapNode['data'] as $post) {
        array_push($posts, $post['id']);
    }

    // LOOPS THROUGH ALL POSTS
    // GETTING POST-ID, POST-MESSAGE, NUMBER OF LIKES, POST-PIC, POST-VID, REPOST-COUNT, CREATED, POST-URL
    // AND THEN SAVING ALL DATA IN A 2D ARRAY
    $postData = [];
    try {
        foreach ($posts as $post) {
            $response = $fb->get("/$post", $accessToken);
            $graphNode = $response->getGraphNode();
            $postData = $graphNode["data"];
            
            $temp = [];
            $temp['post_id'] = $postData['id'];
            $temp['post_message'] = $postData['message'];
            $temp['likes'] = $postData['publishing_stats'];
            $temp['post_pic'] = $postData['full_picture'];
            $temp['post_vid'] = $postData['source'];
            $temp['repost_count'] = $postData['shares'];
            $temp['created'] = $postData['created_time'];
            $temp['post_url'] = $postData['permalink_url'];

        
            // GETTING COMMENTS COUNT
            try {
                $response = $fb->get(
                "/".$data['post_id']."/comments",
                $accessToken
                );
            } catch(FacebookResponseException $e) {
                echo 'Graph returned an error: ' . $e->getMessage();
                exit;
            } catch(FacebookSDKException $e) {
                echo 'Facebook SDK returned an error: ' . $e->getMessage();
                exit;
            }
            $graphNode = $response->getGraphNode();
            $temp['comment_count'] = $graphNode['data']['total_count'];

            array_push($postData, $temp);
        }
    } catch(FacebookResponseException $e) {
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(FacebookSDKException $e) {
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    $data['post_data'] = $postData;

    array_push($allUserData, $data);
}


// ARRAY STRUCTURE
// [
//     {
//         'fullname': text,
//         'profile_pic': text,
//         'username': text,
//         'post_data': [
//             {
//             'post_id': int,
//             'post_message': text,
//             'likes': int,
//             'post_pic': text,
//             'post_vid': text,
//             'repost_count': int,
//             'created': date,
//             'post_url': text,
//             'comment_count': int
//             },
//             {...}, {...}, ...
//         ]
//     },
//     {...}, {...}, ...
// ]

// DB CONNECTION
$conn = mysqli_connect($servername, $username, $password, $database);
$conn->set_charset("utf8mb4");

// $date = date("Y/m/d");
// $q = "CREATE TABLE IF NOT EXISTS `".$date."` (
//      id VARCHAR(177) NOT NULL PRIMARY KEY, [post_id]
// 		sm VARCHAR(177) 'facebook', [dont know]
//      name VARCHAR(177) NOT NULL, [fullname]
// 		profileimg VARCHAR(177), [profile_pic]
// 		inpost_url VARCHAR(255), [post_url]
//      screen_name VARCHAR(177) NOT NULL, [username]
//      text TEXT, [post_message]
//      created_at DATETIME, [created]
// 		repost_count INT, [repost_count]
// 		likes_count INT, [likes]
// 		comments_count INT, [comment_count]
// 		media_url_image VARCHAR(255), [post_pic]
// 		media_url_video VARCHAR(255) [post_vid]
//      )";
// $res = mysqli_query($conn, $q);

foreach ($posts as $post) {
    $sql="insert into fb_data (id,name,likes,profileimg, inpost_url, screen_name,text,created_at,repost_count, likes_count, comments_count, media_url_image, media_url_video) VALUES ({$allUserData['post_id']},{$allUserData['fullname']},{$allUserData['profile_pic']},{$allUserData['post_url']},{$allUserData['username']},{$allUserData['post_message']},{$allUserData['created']} , {$allUserData['repost_count']}, {$allUserData['likes']}, {$allUserData['comment_count']}, {$allUserData['post_pic']}, {$allUserData['post_vid']})";
    if (mysqli_multi_query($conn, $sql)) {
        echo "New records created successfully";
      } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
      }
      
      mysqli_close($conn);
      
}


?>
