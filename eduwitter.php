<?php
/**********************************************************
 * Eduwitter v0.2
 * @poochin - http://www13.atpages.jp/llan/
 * LastUpdate: 2010-04-28
 * License: MIT or BSD
 *   MIT: http://www.opensource.org/licenses/mit-license.php
 *   BSD: http://www.opensource.org/licenses/bsd-license.php
 * モデリング: http://
 * 
 *   Twitter で OAuth を勉強したい方へ向けてのライブラリ
 *********************************************************/
/*--------------------------------------------------------
 * EDAssist
 * 
 *   it declear some variables and functions to assist
 * Eduwitter.
 -------------------------------------------------------*/
EDAssist::initEDAssist();
class EDAssist
{
  static $scheme; // 'https://' or 'http://'
  static $host = 'twitter.com';
  
  static $api_request_token = '/oauth/request_token';
  static $api_access_token = '/oauth/access_token';
  static $api_authenticate = '/oauth/authenticate';
  static $api_authorize = '/oauth/authorize';
  
  static $signature_method = 'HMAC-SHA1';
  static $hash_algo = 'sha1';
  
  static $oauth_version = '1.0a';
  
  static $ssl_cainfo = ''; // './twitter.com.crt';
  static $ssl_verifypeer = true;
  static $ssl_version = 3;
  
  /* callback rawurlencode */
  static function ref_rawurlencode(&$str)
  {
    $str = rawurlencode($str);
  }
  
  /* callback rawurldecode */
  static function ref_rawurldecode(&$str)
  {
    $str = rawurldecode($str);
  }
  
  /* params to http-query like http_build_query without urlencode. */
  static function params2Query($params)
  {
    $parts = array();
    foreach ($params as $k => $v) {
      $parts[] = "{$k}={$v}";
    }
    return implode('&', $parts);
  }

  /* http-query to params as array using parse_str */
  static function query2Params($query)
  {
    $params = array();
    parse_str($query, $params);
    return $params;
  }
  
  /* params to http-Authorization string */
  static function params2Authorization($params)
  {
    $query = array();
    foreach ($params as $key => $value) {
      $query[] = "{$key}=\"{$value}\"";
    }
    return 'Authorization: ' . implode (', ', $query);
  }
  
  /* to create oauth_nonce */
  static function nonce () {
    return md5('seita' . microtime() . mt_rand());
  }
  
  /* to initialize EDAssist non-initialized static variables*/
  static function initEDAssist() {
    /* to initialize EDAssist::$scheme */
    $v = curl_version();
    EDAssist::$scheme = ((array_search('https', $v['protocols'])!==false)
                      ? ('https://') : ('http://'));
  }
}
/*-------------------------------------------------------*/
class Eduwitter
{
  /*------------------- Protected area ------------------*/
    protected $consumer_key,    // provided Consumer key
              $consumer_secret; // provided Consumer secret
              
    protected $user_id;         // authenticated user id
    
    protected $oauth_token,         // oauth token of request_token or access_token
              $oauth_token_secret;  // oauth token secret of request_token or access_token
    
    /**
     * eduwitterConnect
     * 
     * parameters
     *   url -- connection url
     *   method -- 'GET', 'POST' or 'DELETE'
     *   params -- request parameters oauth and post-field
     * 
     * return
     *   response of request
     */
    protected function eduwitteConnect($url, $method, $params)
    {
      $query = EDAssist::params2Query($params);
      $header = array();
      $header[] = 'Expect:';
      $header[] = EDAssist::params2Authorization($params);
      
      /* curl connection */
      $ch = curl_init();
      
      /* branch using method */
      switch ($method) {
        case 'GET':
          $url .= '?' . $query.
          $header[] = 'Content-type: application/x-www-form-urlencoded';
          break;
        case 'POST':
          curl_setopt($ch, CURLOPT_POST, true);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
          break;
        case 'DELETE':
          $url .= '?' . $query;
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
          break;
      }
      
      /* to customize curl */
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
      curl_setopt($ch, CURLOPT_SSLVERSION, EDAssist::$ssl_version);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, EDAssist::$ssl_verifypeer);
      
      if (!empty(EDAssist::$ssl_cainfo)) {
        /* setting option crt file path */
        curl_setopt($ch, CURLOPT_CAINFO, EDAssist::$ssl_cainfo);
      }
      
      $response = curl_exec($ch);
      curl_close($ch);
      return $response;
    }
    
    /**
     * createParams
     * 
     * parameters
     *   post -- post-field(array)
     * 
     * return
     *   request parameters oauth and post-field without oauth_signature
     */
    protected function createParams($post = null)
    {
      /* oauth 1.0a refference #5 */
      $params = array(
        'oauth_consumer_key'     => $this->consumer_key,
        'oauth_signature_method' => EDAssist::$signature_method,
        'oauth_timestamp'        => time(),
        'oauth_nonce'            => EDAssist::nonce(),
        'oauth_version'          => EDassist::$oauth_version,
      );
      
      if (isset($this->oauth_token)) {
        $params['oauth_token'] = $this->oauth_token;
      }
      
      /* params including post-filed */
      if (isset($post)) {
        $params = array_merge($params, $post);
      }
      
      return $params;
    }
    
    /**
     * createSignature
     *   oauth 1.0a refference #9
     * 
     * parameters
     *   url -- connection url
     *   method -- 'GET', 'POST' or 'DELETE'
     *   params -- request parameters oauth and post-field
     * 
     * return
     *   response of request
     */
    protected function createSignature($url, $method, $params)
    {
      /* normalize */
      ksort($params);
      $query = EDAssist::params2Query($params);
      
      /* collect key and base string */
      $key = $this->consumer_secret . '&' . $this->oauth_token_secret;
      $base_string = $method.'&'
                    .rawurlencode($url).'&'
                    .rawurlencode($query);
      
      $signature = 
        rawurlencode(base64_encode(hash_hmac(EDAssist::$hash_algo, $base_string, $key, true)));
      
      return $signature;
    }
  
  /*-------------------- Public area --------------------*/
    /**
     * __construct
     * 
     * parameters
     *   consumer_key -- provided consumer key
     *   consumer_secret -- provided consumer secret
     *   oauth_token -- provided oauth, request or access, token 
     *   oauth_token_secret -- provided oauth, request or access, token secret
     */
    public function __construct($consumer_key = null, $consumer_secret = null,
                                $oauth_token = null, $oauth_token_secret = null)
    {
      if (isset($consumer_key) && isset($consumer_secret)) {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
      }
      if (isset($oauth_token) && isset($oauth_token_secret)) {
        self::setOAuthToken($oauth_token, $oauth_token_secret);
      }
    }
    
    /**
     * setOAuthToken
     * 
     * parameters
     *   oauth_token -- provided oauth, request or access, token
     *                  or array(oauth_token, oauth_token_secret)
     *   oauth_token_secret -- provided oauth, request or access, token secret
     */
    public function setOAuthToken($oauth_token, $oauth_token_secret = null)
    {
      if (gettype($oauth_token) == 'array') {
        $this->oauth_token = $oauth_token['oauth_token'];
        $this->oauth_token_secret = $oauth_token['oauth_token_secret'];
      }
      else {
        $this->oauth_token = $oauth_token;
        if (isset($oauth_token_secret)) {
          $this->oauth_token_secret = $oauth_token_secret;
        }
      }
      /* pick out user id */
      preg_match("/^\d+(?=-)/", $this->oauth_token, $m);
      if (!empty($m)) {
        $this->user_id = $m[0];
      }
    }
    
  /*----------------- Request token area ----------------*/
    /**
     * getParameter_RequestToken
     * 
     * return
     *   http request parameters(array)
     */
    protected function getParameter_RequestToken()
    {
      $url = EDAssist::$scheme . EDAssist::$host . EDAssist::$api_request_token;
      $method = 'GET';
      
      $params = self::createParams();
      $params['oauth_signature'] = self::createSignature($url, $method, $params);
      
      return $params;
    }
    
    /**
     * getRequestToken
     * 
     * return
     *   oauth token and token secret, oauth/request_token provided
     */
    public function getRequestToken()
    {
      $url = EDAssist::$scheme . EDAssist::$host . EDAssist::$api_request_token;
      $method = 'GET';
      
      $params = self::getParameter_RequestToken();
      $response = self::eduwitteConnect($url, $method, $params);
      
      return EDAssist::query2Params($response);
    }
    
    /**
     * setRequestToken
     * 
     * parameters
     *   oauth_token -- provided oauth, request, token
     *                  or array(oauth_token, oauth_token_secret)
     *   oauth_token_secret -- provided oauth, request, token secret
     */
    public function setRequestToken($oauth_token, $oauth_token_secret = null)
    {
      self::setOAuthToken($oauth_token, $oauth_token_secret);
    }
    
  /*----------------- Aceess token area -----------------*/
    /**
     * getParameter_AccessToken
     * 
     * return
     *   http request parameters(array)
     */
    protected function getParameter_AccessToken()
    {
      $url = EDAssist::$scheme . EDAssist::$host . EDAssist::$api_access_token;
      $method = 'GET';
      
      $params = self::createParams();
      $params['oauth_signature'] = self::createSignature($url, $method, $params);
      
      return $params;
    }
    
    /**
     * getAccessToken
     * 
     * return
     *   oauth token and token secret, oauth/access_token provided
     */
    public function getAccessToken()
    {
      $url = EDAssist::$scheme . EDAssist::$host . EDAssist::$api_access_token;
      $method = 'GET';
      
      $params = self::getParameter_AccessToken();
      $response = self::eduwitteConnect($url, $method, $params);
      
      return EDAssist::query2Params($response);
    }
    
    /**
     * setAccessToken
     * 
     * parameters
     *   oauth_token -- provided oauth, request, token
     *                  or array(oauth_token, oauth_token_secret)
     *   oauth_token_secret -- provided oauth, request, token secret
     */
    public function setAccessToken($oauth_token, $oauth_token_secret = null)
    {
      self::setOAuthToken($oauth_token, $oauth_token_secret);
    }
    
  /*--------------------- OAuth area --------------------*/
    /**
     * getParameter_OAuth
     * 
     * parameters
     *   url -- connection url
     *   method -- 'GET', 'POST' or 'DELETE'
     *   params -- request parameters oauth and post-field
     * 
     * return
     *   http request parameters(array)
     */
    protected function getParameter_OAuth($url, $method, $post)
    {
      $params = self::createParams($post);
      $params['oauth_signature'] = self::createSignature($url, $method, $params);
      
      return $params;
    }
    
    /**
     * requestOAuth
     * 
     * parameters
     *   url -- connection url
     *   method -- 'GET', 'POST' or 'DELETE'
     *   params -- post-field
     * 
     * return
     *   response of http-request
     */
    public function requestOAuth($url, $method, $post)
    {
      array_walk($post, 'EDAssist::ref_rawurlencode');
      
      $params = self::getParameter_OAuth($url, $method, $post);
      $response = self::eduwitteConnect($url, $method, $params);
      
      return $response;
    }
}
