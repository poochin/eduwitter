<?php
/*---------------------------------------------------------
                    Configure Section
---------------------------------------------------------*/
$consumer_key = '<Consumer key>';
$consumer_secret = '<Consumer secret>';

/*---------------------------------------------------------
                include and require Section
---------------------------------------------------------*/
require_once 'eduwitter.php';

/*---------------------------------------------------------
                     Process Section
---------------------------------------------------------*/
/**
 * SESSION saving
 *   request_token
 *   request_token_secret
 *   access_token
 *   access_token_secret
 */
session_start();

$content = null;
$messages = null;
$eduwitter = new Eduwitter($consumer_key, $consumer_secret);

if (isset($_GET['request_token']))
{
  /**
   * oauth/request_token
   */
  $request_tokens = $eduwitter->getRequestToken();
  
  if (!empty($request_tokens)) {
    /* saving request tokens */
    $_SESSION['request_token'] = $request_tokens['oauth_token'];
    $_SESSION['request_token_secret'] = $request_tokens['oauth_token_secret'];
    
    $messages .= '<p>Getting Request token.<br/>リクエストトークンを作成しました。</p>';
    $content .= "<p>to Accept and Exchange request_token, access this link.<br/>トークンを有効にする場合は以下のリンクに進んでください。<br/><a href='http://twitter.com/oauth/authenticate?oauth_token={$request_tokens['oauth_token']}'>Eduwitter's Authentication Link.</a></p>";
  }
  else {
    $messages .= '<p>You failed getting Request token was fail.<br/>リクエストトークンの作成に失敗しました。</p>';
  }
}
else if (isset($_GET['oauth_token']))
{
  /**
   * oauth/access_token
   */
  $eduwitter->setRequestToken($_SESSION['request_token'], $_SESSION['request_token_secret']);
  $access_tokens = $eduwitter->getAccessToken();
  
  /* saving access tokens */
  $_SESSION['access_token'] = $access_token = $access_tokens['oauth_token'];
  $_SESSION['access_token_secret'] = $access_token_secret = $access_tokens['oauth_token_secret'];
  
  /* getting user_id and screen_name */
  $user_id = $access_tokens['user_id'];
  $screen_name = $access_tokens['screen_name'];
  
  $messages .= '<p>Getting Access token.<br/>アクセストークンを受けとりました。</p>';
  $messages .= "<dl><dt>Access Token</dt><dd>{$access_token}</dd><dt>Access Token Secret</dt><dd>{$access_token_secret}</dd><dt>user_id</dt><dd>{$user_id}</dd><dt>screen_name</dt><dd>{$screen_name}</dd></dl>";
  
  $content .= '<h2>Test commands</h2>';
  $content .= '<ul>';
  $content .= '<li><a href="?command=home">Home</a>: To watch Timeline(タイムライン).</li>';
  $content .= '<li><a href="?command=update">Update status</a>: Post sample tweet(ツイートテスト送信).</li>';
  $content .= '<li><a href="?command=replies">Replies</a>: Replies to you(リプライ).</li>';
  $content .= '<li><a href="?command=lists">Lists</a>: Lists you registered(リスト一覧).</li>';
  $content .= '<li><a href="?command=newlist">create List</a>: create "dummy" list.</li>';
  $content .= '<li><a href="?command=dellist">delete List</a>: delete "dummy" list.</li>';
  $content .= '</ul>';
}
else if (isset($_GET['command']))
{
  /**
   * access to OAuth API
   */
  $eduwitter->setAccessToken($_SESSION['access_token'], $_SESSION['access_token_secret']);
  
  $post = array();
  switch ($_GET['command']) {
    case 'home':
      $url = 'http://api.twitter.com/1/statuses/home_timeline.xml';
      $method = 'GET';
      break;
    case 'update':
      $url = 'http://twitter.com/statuses/update.xml';
      $method = 'POST';
      $post['status'] = 'hello, world! and eduwitter! time:' . date('Y-m-d H:i:s');
      break;
    case 'replies':
      $url = 'http://twitter.com/statuses/replies.xml';
      $method = 'GET';
      break;
    case 'lists':
      $user_id = preg_just_match("/^\d+/", $_SESSION['access_token']);
      $url = "http://api.twitter.com/1/{$user_id}/lists.xml";
      $method = 'GET';
      break;
    case 'newlist':
      $user_id = preg_just_match("/^\d+/", $_SESSION['access_token']);
      $url = "http://api.twitter.com/1/{$user_id}/lists.xml";
      $method = 'POST';
      $post['name'] = 'dummy';
      break;
    case 'dellist':
      $user_id = preg_just_match("/^\d+/", $_SESSION['access_token']);
      $url = "http://api.twitter.com/1/{$user_id}/lists/dummy.xml";
      $method = 'DELETE';
      break;
    default:
      break;
  }
  $response = $eduwitter->requestOAuth($url, $method, $post);
  header('Content-type: text/xml; charset=UTF-8');
  echo $response;
  
  exit; // output response XML and quit
}
else{
  $content = 'to Get Request Token, click this link.<br/>ja(リクエストトークンを発行します)<br/><a href="?request_token">Eduwitter\'s requestToken Link.</a>';
}
/*---------------------------------------------------------
                     Functions Section
---------------------------------------------------------*/
function preg_just_match($reg, $src)
{
  preg_match ($reg, $src, $m);
  return @$m[0];
}
/*---------------------------------------------------------
                      Output Section
---------------------------------------------------------*/
// To avoid ads, outputing XHTML
header('Content-type: text/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';

?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="jp" lang="jp">
<head>
  <title>Eduwitter Callback Page</title>
</head>
<body>
  <h1>Eduwitter Callback Page</h1>
  <?="{$messages}\n";?>
  <hr/>
  <?="{$content}\n";?>
  <hr/>
  <p>Eduwitter ver0.1 (<a href="http://www13.atpages.jp/llan/archive/eduwitter_001.zip">zip</a>) (<a href="http://www13.atpages.jp/llan/archive/eduwitter_001.tar.gz">tar.gz</a>)</p>
</body>
</html>
