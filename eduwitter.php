<?php
// TODO: consumer_key の位置
// TODO: 

/**********************************************************
 * Eduwitter v0.3.1
 * Author: poochin
 * LastUpdate: 2010-10-13
 * License: MIT or BSD
 *   MIT: http://www.opensource.org/licenses/mit-license.php
 *   BSD: http://www.opensource.org/licenses/bsd-license.php
 *********************************************************/

/*--------------------------------------------------------
 * EDAssist supply customized data.
 * All members declare as static using like namespace.
 * 
 * Methods
 *   nonce
 -------------------------------------------------------*/
class EDAssist
{
  static $secure_scheme = 'https';    // for get {request,access}token
  static $host = 'api.twitter.com';   // default host name
  
  /* Twitter API Paths */
  static $api_request_token = '/oauth/request_token';
  static $api_access_token = '/oauth/access_token';
  static $api_authenticate = '/oauth/authenticate';
  static $api_authorize = '/oauth/authorize';
  
  /* Hash Algos */
  static $signature_method = 'HMAC-SHA1';
  static $hash_algo = 'sha1';
  
  static $oauth_version = '1.0a';
  
  static $boundary = '--------Eduwitter1d57b8611a6d'; // for multipart/form-data
  
  /* to create oauth_nonce */
  static function nonce () {
    return md5('seita' . microtime() . mt_rand());
  }
}

/*--------------------------------------------------------
 * EDPreparation
 * 
 * Methods
 *   __construct
 * 
 *   buildParameters
 *   createSignature
 *   buildHeaders
 *   buildBody
 *   setup
 *   getAuthorization
 * 
 *   getUrl
 *   getMethod
 *   getPost
 *   getImagePath
 *   getParameters
 *   getHeaderFields
 *   getBodyField
 -------------------------------------------------------*/
class EDPreparation
{
  /* Twitter Consumer & tokens */
  private $consumer_key;
  private $consumer_secret;
  private $oauth_token;
  private $oauth_token_secret;
  
  /* oauth request datas */
  private $url;         // Twitter API URL
  private $method;      // HTTP Method
  private $post;        // HTTP Post data
  private $image_path;  // Upload Image path
  private $parameters;  // OAuth Parameters
  
  /* HTTP datas */
  private $header_fields; // HTTP Header
  private $body_field;   // HTTP Message Body
  
  /*--------------- private function area --------------*/
    /**
     * build Parameters
     * 
     * return
     *   Authorization parameters for OAuth
     */
    private function buildParameters()
    {
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
      $params = array_merge($params, $this->post);
      $params['oauth_signature'] = self::createSignature($params);
      
      $this->parameters = $params;
      
      ksort($this->parameters);
    }
    
    /**
     * createSignature
     * 
     * parameters
     *   OAuth parameters without signature
     * 
     * return
     *   signature created by params
     */
    private function createSignature($params)
    {
      $pu = parse_url($this->url);
      $url = $pu['scheme'] . '://' . $pu['host'] . $pu['path'];
      
      ksort($params);
      $q = rawurldecode(http_build_query($params));
      $key = $this->consumer_secret . '&' . $this->oauth_token_secret;
      $base_string = $this->method . '&' . rawurlencode($url) . '&' . rawurlencode($q);
      
      return rawurlencode(base64_encode(hash_hmac(EDAssist::$hash_algo, $base_string, $key, true)));
    }
    
    /**
     * buildHeaders
     */
    private function buildHeaders()
    {
      $headers = array('Expect:');
      
      switch ($this->method) {
        case 'GET':
          $headers[] = self::createAuthorization();
          break;
          
        case 'POST':
          if (isset($this->image_path)) {
            $headers[] = self::createAuthorization();
            $headers[] = "Content-Type: multipart/form-data; boundary=" . EDAssist::$boundary;
          }
          $headers[] = "Content-Length: " . strlen($this->body_field);
          break;
      }
      
      $this->header_fields = implode("\r\n", $headers);
    }
    
    /**
     * buildBody
     */
    private function buildBody()
    {
      $this->body_field = "";
      if (isset($this->image_path)) {
        $boundary = EDAssist::$boundary;
        $bname = basename($this->image_path);
        $this->body_field = "--{$boundary}\r\n"
                           ."Content-Disposition: form-data; name=\"image\"; filename=\"{$bname}\"\r\n"
                           ."Content-Type: " . mime_content_type($this->image_path) . "\r\n"
                           ."\r\n"
                           .file_get_contents($this->image_path) . "\r\n"
                           ."--{$boundary}--";
      }
      else if ($this->method == 'POST') {
        $this->body_field = rawurldecode(http_build_query($this->parameters));
      }
    }
  
  /*-------------------- Public area -------------------*/
    /**
     * __construct
     * 
     * parameters
     *   consumer_key -- Consumer key provided by twitter
     *   consumer_secret -- Consumer secret provided by twitter
     *   oauth_token -- request/access token
     *   oauth_token_secret -- request/access token secret
     */
    public function __construct($consumer_key, $consumer_secret, $oauth_token = null, $oauth_token_secret = null)
    {
      $this->consumer_key = $consumer_key;
      $this->consumer_secret = $consumer_secret;
      $this->oauth_token = $oauth_token;
      $this->oauth_token_secret = $oauth_token_secret;
    }
    
    /**
     * prepare
     * 
     * parameters
     *   url -- URL of API
     *   method -- HTTP Method of API
     *   post -- post datas of API
     *   image_path -- image path or null(none-uploading image)
     */
    public function setup($url, $method, $post = array(), $image_path = null)
    {
      $this->url = $url;
      $this->method = $method;
      $this->post = array_map("rawurlencode", $post);
      $this->image_path = $image_path;
      
      /**
       * Note: if (metthod == GET or image_path != null) and post data exists,
       *   eduwitter create signature including post data,
       *   and set post datas to query string.
       */
      if (($method == 'GET' || isset($image_path)) && !empty($post)) {
         $this->url .= (!empty($post) ? ('?' . http_build_query($post)) : (""));
      }
      
      self::buildParameters();
      self::buildBody();
      self::buildHeaders();
    }
    
    /**
     * getAuthorization
     * 
     * return
     *   HTTP Authorization Header
     */
    public function createAuthorization()
    {
      preg_match("/https?:\/\/[^\/]+\//", $this->url, $m);
      $realm = $m[0];
      
      $params = array();
      foreach ($this->parameters as $k => $v) {
        $params[] = "{$k}=\"{$v}\"";
      }
      
      return "Authorization: OAuth realm=\"{$realm}\", " . implode(", ", $params);
    }
    
    /**
     * getter
     */
    public function getUrl() { return $this->url; }
    public function getMethod() { return $this->method; }
    public function getPost() { return $this->post; }
    public function getImagePath() { return $this->image_path; }
    public function getParameters() { return $this->parameters; }
    public function getHeaderFields() { return $this->header_fields; }
    public function getBodyField() { return $this->body_field; }
}

/*--------------------------------------------------------
 * Eduwitter library main class.
 * 
 * Methods
 *   eduwitterConnect()
 *   
 *   __construct($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret)
 *   setOAuthToken($oauth_token, $oauth_token_secret = null)
 *   
 *   getRequestToken
 *   getAccessToken
 *   requestOAuth($url, $method, $post)
 *   
 *   lastStatusCode()
 *   lastStatusReason()
 -------------------------------------------------------*/
class Eduwitter
{
  private $consumer_key, $consumer_secret;    // Consumer key/secret of Twitter OAuth
  private $oauth_token, $oauth_token_secret;  // oauth_token/token_secret of Twitter OAuth
  
  private $last_status_code,    // HTTP last status code of Twitter OAuth
          $last_status_reason;  // HTTP last status reason of Twitter OAuth
  
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
  protected function eduwitterConnect($prepare)
  {
    /**
     * collecting datas to open socket
     */
    $pu = parse_url($prepare->getUrl());
    
    $port = getservbyname($pu['scheme'], 'tcp');
    $path = $pu['path'] . (isset($pu['query']) ? ("?" . $pu['query']) : "");
    $fsock_host = ($port == 443 ? 'tls://' : '') . $pu['host'];
    
    /**
     * opening socket and recieving
     */
    $fp = fsockopen($fsock_host, $port);
    if (!$fp) {
      die("Can not open socket\n");
    }
    
    fwrite($fp, $prepare->getMethod() . " {$path} HTTP/1.1\r\n");
    fwrite($fp, "Host: {$pu['host']}\r\n");
    fwrite($fp, $prepare->getHeaderFields() . "\r\n");
    fwrite($fp, "\r\n");
    fwrite($fp, $prepare->getBodyField());
    
    $buf = "";
    while (!feof($fp)) {
      $buf .= fgets($fp);
    }
    fclose($fp);
    
    /**
     * end of connection and parse response data
     */
    /* split response to header and body */
    $split_pos = strpos($buf, "\r\n\r\n");
    $response_header = substr($buf, 0, $split_pos);
    $response_body = substr($buf, $split_pos + 4);
    
    /* get http status code */
    preg_match ("/^HTTP\/[\d\.]+ (\d+) (.+)/", $response_header, $m);
    $this->last_status_code = $m[1];
    $this->last_status_reason = trim($m[2]); // trim word(\r)
    
    return $response_body;
  }

  /**
   * __construct
   * 
   * parameters
   *   consumer_key -- provided consumer key
   *   consumer_secret -- provided consumer secret
   *   oauth_token -- provided oauth, request or access, token 
   *   oauth_token_secret -- provided oauth, request or access, token secret
   */
  public function __construct($consumer_key, $consumer_secret,
                              $oauth_token = null, $oauth_token_secret = null)
  {
    $this->consumer_key = $consumer_key;
    $this->consumer_secret = $consumer_secret;
    $this->oauth_token = $oauth_token;
    $this->oauth_token_secret = $oauth_token_secret;
  }
  
  /**
   * getRequestToken
   * 
   * return
   *   oauth token and token secret, oauth/request_token provided.
   *   fail: http raw response string
   */
  public function getRequestToken()
  {
    $url = EDAssist::$secure_scheme . '://' . EDAssist::$host . EDAssist::$api_request_token;
    $method = 'GET';
    
    $prepare = new EDPreparation($this->consumer_key, $this->consumer_secret, $this->oauth_token, $this->oauth_token_secret);
    $prepare->setup($url, $method);
    
    $response = self::eduwitterConnect($prepare);
    
    if ($self::getLastStatusCode() != 200) {
      return $response;
    }
    parse_str($response, $request_tokens);
    return $request_tokens;
  }
  
  /**
   * getAccessToken
   * 
   * return
   *   oauth token and token secret, oauth/access_token provided.
   *   fail: http raw response string
   */
  public function getAccessToken($oauth_token, $oauth_token_secret)
  {
    $url = EDAssist::$secure_scheme . '://' . EDAssist::$host . EDAssist::$api_access_token;
    $method = 'GET';
    
    $prepare = new EDPreparation($this->consumer_key, $this->consumer_secret, $oauth_token, $oauth_token_secret);
    $prepare->setup($url, $method);
    
    $response = self::eduwitterConnect($prepare);
    
    if ($self::getLastStatusCode() != 200) {
      return $response;
    }
    parse_str($response, $oauth_tokens);
    return $oauth_tokens;
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
  public function requestOAuth($url, $method, $post = array(), $image_path = null)
  {
    $prepare = new EDPreparation($this->consumer_key, $this->consumer_secret, $this->oauth_token, $this->oauth_token_secret);
    $prepare->setup($url, $method, $post, $image_path);
    
    $response = self::eduwitterConnect($prepare);
    
    return $response;
  }
  
  /**
   * getter
   */
  public function getLastStatusCode() { return $this->last_status_code; }
  public function getLastStatusReason() { return $this->last_status_reason; }
}
