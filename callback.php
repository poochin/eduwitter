<?php
/**********************************************************
 * Eduwitter callback Test
 * Author: poochin
 * blog: http://poochin.blogspot.com/
 *********************************************************/
/*---------------------------------------------------------
  Custom configure
---------------------------------------------------------*/
/* Consumer settings */
$consumer_key = 'ConsumerKey';
$consumer_secret = 'ConsumerSecret';

/*---------------------------------------------------------
  Setting output format
---------------------------------------------------------*/
header('Content-type: text/xml; charset=UTF-8');

/*---------------------------------------------------------
  Include and Require
---------------------------------------------------------*/
require_once 'eduwitter.php';

/*---------------------------------------------------------
  Common proccess
---------------------------------------------------------*/
$twitter = new Eduwitter($consumer_key, $consumer_secret);

$statuses = null;
$messages = null;
$errors = null;
$contents = null;

/*---------------------------------------------------------
  Parse Cookie datas
---------------------------------------------------------*/
if (isset($_COOKIE['eduwitter']))
{
  $oauth_token = isset($_COOKIE['eduwitter']['oauth_token']) ? ($_COOKIE['eduwitter']['oauth_token']) : (null);
  $oauth_token_secret = isset($_COOKIE['eduwitter']['oauth_token_secret']) ? ($_COOKIE['eduwitter']['oauth_token_secret']) : null;
  $user_id = isset($_COOKIE['eduwitter']['user_id']) ? ($_COOKIE['eduwitter']['user_id']) : (null);
  $screen_name = isset($_COOKIE['eduwitter']['screen_name']) ? ($_COOKIE['eduwitter']['screen_name']) : (null);
  
  $twitter->setOAuthToken($oauth_token, $oauth_token_secret);
}

/*---------------------------------------------------------
  Callback Proccess
---------------------------------------------------------*/
if (isset($_GET['oauth_token']))
{
  $twitter->setRequestToken($_GET['oauth_token'], null);
  $access_tokens = $twitter->getAccessToken();
  
  setcookie('eduwitter[oauth_token]', $access_tokens['oauth_token'], 0, '/');
  setcookie('eduwitter[oauth_token_secret]', $access_tokens['oauth_token_secret'], 0, '/');
  setcookie('eduwitter[user_id]', $access_tokens['user_id'], 0, '/');
  setcookie('eduwitter[screen_name]', $access_tokens['screen_name'], 0, '/');
  
  
  // to trim oauth=xxx of query-string
  header('Location: ' . $_SERVER["PHP_SELF"]);
}

/*---------------------------------------------------------
  New request token
---------------------------------------------------------*/
if (isset($_GET['new_token']))
{
  $request_tokens = $twitter->getRequestToken();
  
  if (empty($request_tokens))
  {
    $errors = "Failed to get request token.<br/>please reload here.";
  }
  else
  {
    $messages = "Success to get request token.<br/><a href=\"https://twitter.com/oauth/authenticate?oauth_token={$request_tokens['oauth_token']}\">Auth this token.</a>";
  }
}

/*---------------------------------------------------------
  Custom commands
---------------------------------------------------------*/
if (isset($_GET['command']))
{
  $post = array();
  switch ($_GET['command'])
  {
    case 'home':
      $url = 'http://api.twitter.com/statuses/home_timeline.xml';
      $method = 'GET';
      break;
    case 'mentions':
      $url = 'http://api.twitter.com/statuses/mentions.xml';
      $method = 'GET';
      break;
    case 'lists':
      $url = "http://api.twitter.com/{$user_id}/lists.xml";
      $method = 'GET';
      break;
    case 'tweet':
      $url = 'http://api.twitter.com/statuses/update.xml';
      $method = 'POST';
      $post['status'] = 'hello, eduwitter! : ' . (time()%60) . 'sec';
      break;
    case 'logout':
      setcookie('eduwitter[oauth_token]', false, 0, '/');
      setcookie('eduwitter[oauth_token_secret]', false, 0, '/');
      setcookie('eduwitter[user_id]', false, 0, '/');
      setcookie('eduwitter[screen_name]', false, 0, '/');
      header('Location: ' . $_SERVER["PHP_SELF"]);
      break;
    default:
      die('error: deny or unknown command');
  }
  $response = $twitter->requestOAuth($url, $method, $post);
  echo $response;
  exit;
}

/*---------------------------------------------------------
  Viewer statuses
---------------------------------------------------------*/
$statuses  = '<dl>';
$statuses .= '<dt>Status</dt>';
$statuses .= isset($oauth_token) ? '<dd>logined</dd>' : '<dd>not logined</dd>';
$statuses .= '<dt>oauth_token</dt>';
$statuses .= isset($oauth_token) ? "<dd>$oauth_token</dd>" : '<dd>Nohing</dd>';
$statuses .= '<dt>oauth_token_secret</dt>';
$statuses .= isset($oauth_token_secret) ? "<dd>$oauth_token_secret</dd>" : '<dd>Nothing</dd>';
$statuses .= '<dt>user_id</dt>';
$statuses .= isset($user_id) ? "<dd>$user_id</dd>" : '<dd>Nothing</dd>';
$statuses .= '<dt>screen_name</dt>';
$statuses .= isset($screen_name) ? "<dd>$screen_name</dd>" : '<dd>Nothing</dd>';
$statuses .= '</dl>';

/*---------------------------------------------------------
  Toppage
---------------------------------------------------------*/
if (empty($_GET) && !isset($_COOKIE['eduwitter']))
{
  $contents = "<a href=\"{$_SERVER['PHP_SELF']}?new_token\">create new token</a>";
}
else if (isset($_COOKIE['eduwitter']))
{
  $contents = <<<EOF
  Commands:
  <ul>
    <li><a href="{$_SERVER['PHP_SELF']}?command=home">Timeline</a></li>
    <li><a href="{$_SERVER['PHP_SELF']}?command=mentions">Mentions</a></li>
    <li><a href="{$_SERVER['PHP_SELF']}?command=lists">your Lists</a></li>
    <li><a href="{$_SERVER['PHP_SELF']}?command=tweet">Post test tweet</a></li>
    <li><a href="{$_SERVER['PHP_SELF']}?command=logout">Logout</a></li>
  </ul>
EOF;
}

?><?='<?xml version="1.0" encoding="UTF-8"?>';?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="jp" lang="jp">
<head>
  <title>Eduwitter</title>
  <style type="text/css">
ul { margin: 0; }

div#statuses > dl > dt { width: 10em; float: left; font-weight: bold;}
div#statuses > dl > dd:after { height: 0; visibility: hidden; content: ''; display: block; clear: both; }
div#statuses > dl > dd { margin-left: 12em;}

div#statuses,
div#messages,
div#contents,
div#errors {
  margin: 10px;
}
div#errors { color: red; }
  </style>
</head>
<body>
  <h1>Eduwitter</h1>
  <div id="statuses"><?=$statuses;?></div>
  <div id="messages"><?=$messages;?></div>
  <div id="errors"><?=$errors;?></div>
  <div id="contents"><?=$contents;?></div>
  <hr/>
  <div>
    get Eduwitter from <a href="http://github.com/poochin/eduwitter">github</a>.
  </div>
  <div>
    <a href="http://twitter.com/settings/connections">Revoke</a> this consumer.
  </div>
</body>
</html>
