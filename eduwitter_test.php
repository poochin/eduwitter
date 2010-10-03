<?php
require_once 'eduwitter.php';

// require_once 'auth.php';
$consumer_key = '';
$consumer_secret = '';
$oauth_token = '';
$oauth_token_secret = '';

$user_id = 1;
if (preg_match ("/^\d+/", $oauth_token, $m)) {
  $user_id = $m[0];
}

$run_request_token = true;
$run_home_timeline = true;
$run_show = true;
$run_update_status = true;
$run_destroy_status = true;
$run_friends = true;
$run_show_user = true;
$run_user_search = true;
$run_direct_messages = true;
$run_update_profile_image = true;
$run_update_profile_background_image = true;
$run_rate_limit_status = true;

/**
 * token API
 */
$eduwitter = new Eduwitter($consumer_key, $consumer_secret);

if ($run_request_token) {
  /* Request token */
  echo "--------------------\n";
  echo "get Request Token\n";
  $eduwitter->getRequestToken();
  echo 'HTTP Status Code: ' . $eduwitter->getLastStatusCode() . "\n";
  echo 'HTTP Status Reason: ' . $eduwitter->getLastStatusReason() . "\n";
}
/* Access token */
// none

unset($eduwitter);

/**
 * other API
 */
$eduwitter = new Eduwitter($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);

if ($run_home_timeline) {
  /* home_timeline */
  echo "--------------------\n";
  echo "get Home Timeline\n";
  $url = 'http://api.twitter.com/1/statuses/home_timeline.xml';
  $method = 'GET';

  $eduwitter->requestOAuth($url, $method);
  echo 'URL: ' . $url . "\n";
  echo 'HTTP Status Code: ' . $eduwitter->getLastStatusCode() . "\n";
  echo 'HTTP Status Reason: ' . $eduwitter->getLastStatusReason() . "\n";
}
if ($run_show) {
  /* show last status by id */
  echo "--------------------\n";
  echo "get Last Status\n";
  $url = "http://api.twitter.com/1/statuses/show/{$user_id}.xml";
  $method = 'GET';

  $eduwitter->requestOAuth($url, $method);
  echo 'URL: ' . $url . "\n";
  echo 'HTTP Status Code: ' . $eduwitter->getLastStatusCode() . "\n";
  echo 'HTTP Status Reason: ' . $eduwitter->getLastStatusReason() . "\n";
}
if ($run_update_status) {
  /* update status */
  echo "--------------------\n";
  echo "update status\n";
  $url = 'http://api.twitter.com/1/statuses/update.xml';
  $method = 'POST';
  $post = array('status' => 'Eduwitter test ツイート(tweet)'); // to test UTF-8 use Japanese

  $response = $eduwitter->requestOAuth($url, $method, $post);
  $xml = simplexml_load_string($response);
  $status_id = (int)$xml->id;
  echo 'new status id: ' . $status_id . "\n";
  echo 'URL: ' . $url . "\n";
  echo 'HTTP Status Code: ' . $eduwitter->getLastStatusCode() . "\n";
  echo 'HTTP Status Reason: ' . $eduwitter->getLastStatusReason() . "\n";
}
if ($run_destroy_status) {
  /* destroy status */
  echo "--------------------\n";
  echo "destroy status\n";
  $url = "http://api.twitter.com/1/statuses/destroy/{$status_id}.xml";
  $method = 'POST';

  $eduwitter->requestOAuth($url, $method);
  echo 'URL: ' . $url . "\n";
  echo 'HTTP Status Code: ' . $eduwitter->getLastStatusCode() . "\n";
  echo 'HTTP Status Reason: ' . $eduwitter->getLastStatusReason() . "\n";
}
if ($run_friends) {
  /* get friends(following) */
  echo "--------------------\n";
  echo "friends(following)\n";
  $url = 'http://api.twitter.com/1/statuses/friends.xml';
  $method = 'GET';

  $eduwitter->requestOAuth($url, $method);
  echo 'URL: ' . $url . "\n";
  echo 'HTTP Status Code: ' . $eduwitter->getLastStatusCode() . "\n";
  echo 'HTTP Status Reason: ' . $eduwitter->getLastStatusReason() . "\n";
}
if ($run_show_user) {
  /* user detail info */
  echo "--------------------\n";
  echo "show user's detail info \n";
  $url = "http://api.twitter.com/1/users/show/{$user_id}.xml";
  $method = 'GET';

  $eduwitter->requestOAuth($url, $method);
  echo 'URL: ' . $url . "\n";
  echo 'HTTP Status Code: ' . $eduwitter->getLastStatusCode() . "\n";
  echo 'HTTP Status Reason: ' . $eduwitter->getLastStatusReason() . "\n";
}
if ($run_user_search) {
  /* user search */
  echo "--------------------\n";
  echo "show user's detail info \n";
  $url = "http://api.twitter.com/1/users/search.xml";
  $method = 'GET';
  $post = array('q' => 'poochin');

  $eduwitter->requestOAuth($url, $method, $post);
  echo 'URL: ' . $url . "\n";
  echo 'HTTP Status Code: ' . $eduwitter->getLastStatusCode() . "\n";
  echo 'HTTP Status Reason: ' . $eduwitter->getLastStatusReason() . "\n";
}
if ($run_direct_messages) {
  /* get direct messages */
  echo "--------------------\n";
  echo "get direct messages \n";
  $url = "http://api.twitter.com/1/direct_messages.xml";
  $method = 'GET';

  $eduwitter->requestOAuth($url, $method);
  echo 'URL: ' . $url . "\n";
  echo 'HTTP Status Code: ' . $eduwitter->getLastStatusCode() . "\n";
  echo 'HTTP Status Reason: ' . $eduwitter->getLastStatusReason() . "\n";
}
if ($run_update_profile_image) {
  /* get direct messages */
  echo "--------------------\n";
  echo "update profile background image\n";
  $url = "http://api.twitter.com/1/account/update_profile_image.xml";
  $method = 'POST';
  $post = array();
  $image_path = "sample.png";

  $eduwitter->requestOAuth($url, $method, $post, $image_path);
  echo 'URL: ' . $url . "\n";
  echo 'HTTP Status Code: ' . $eduwitter->getLastStatusCode() . "\n";
  echo 'HTTP Status Reason: ' . $eduwitter->getLastStatusReason() . "\n";
}
if ($run_update_profile_background_image) {
  /* get direct messages */
  echo "--------------------\n";
  echo "update profile background image\n";
  $url = "http://api.twitter.com/1/account/update_profile_background_image.xml";
  $method = 'POST';
  $post = array('tile' => 'true');
  $image_path = "sample.png";
  
  $eduwitter->requestOAuth($url, $method, $post, $image_path);
  echo 'URL: ' . $url . "\n";
  echo 'HTTP Status Code: ' . $eduwitter->getLastStatusCode() . "\n";
  echo 'HTTP Status Reason: ' . $eduwitter->getLastStatusReason() . "\n";
}
if ($run_rate_limit_status) {
  /* rate limit status */
  echo "--------------------\n";
  echo "rate limit status\n";
  $url = "http://api.twitter.com/1/account/rate_limit_status.xml";
  $method = 'GET';
  
  $eduwitter->requestOAuth($url, $method);
  echo 'URL: ' . $url . "\n";
  echo 'HTTP Status Code: ' . $eduwitter->getLastStatusCode() . "\n";
  echo 'HTTP Status Reason: ' . $eduwitter->getLastStatusReason() . "\n";
}
