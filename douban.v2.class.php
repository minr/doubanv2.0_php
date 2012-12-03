<?php

/**
 * 
 * @author 内裤叔叔
 * @param 豆瓣认证类
 *
 */
class DoubanOauthV2{
	
	private $key;
	private $key_secret;
	private $access_token;
	private $refresh_token;
	private $timeout;
	private $connecttimeout;
	private $ssl_verifypeer = false;
	private $debug = false;
	private static $boundary = '';
	private static $auth_uri = "https://www.douban.com/service/auth2/auth";
    private static $token_uri = "https://www.douban.com/service/auth2/token";
    private static $api_uri = "https://api.douban.com/";
    
    /**
     * 初始化豆瓣认证类
     * @param string $key
     * @param string $key_secret
     * @param string $access_token
     * @param string $refresh_token
     */
    public function __construct($key,$key_secret,$access_token = NULL, $refresh_token = NULL){
    	$this->key = $key;
    	$this->key_secret = $key_secret;
    	$this->access_token = $access_token;
    	$this->refresh_token = $refresh_token;
    }
    
    /**
     * 获取授权相关信息
     * @return array
     */
    public function get_info(){
    	return array("key"=>$this->key,"key_secret"=>$this->key_secret,"api_uri"=>$this->api_uri);
    }
    
    /**
     * 获取地址
     * @return string
     */
    public function get_api_uri(){
    	return self::$api_uri;
    }
    
    /**
     * 获取认证跳转地址
     * @param string $redirect_uri 授权回调页
     * @param string $response_type 必选参数，此值可以为 code 或者 token 。在本流程中，此值为 code
     * @return string
     */
    public function getAuthorizeURL($redirect_uri, $response_type = "code" ){
    	$params = array();
    	$params['client_id'] = $this->key;
    	$params['redirect_uri'] = urldecode($redirect_uri);
    	$params['response_type'] = $response_type;
    	return self::$auth_uri."?".http_build_query($params);    	
    }
    
    /**
     * 获取access_token
     * @param string $redirect_uri 回调地址
     * @param string $code $this->getAuthorizeURL 授权受的code值
     * @return array
     */
    public function getAccessToken($redirect_uri,$code){
    	$params = array();
    	$params['client_id'] = $this->key;
    	$params['client_secret'] = $this->key_secret;
    	$params['redirect_uri'] = $redirect_uri;
    	$params['grant_type'] = "authorization_code";
    	$params['code'] = $code;
    	$response = $this->oAuthRequest(self::$token_uri, "POST", $params);
    	$token = json_decode($response, true);
    	if ( is_array($token) && !isset($token['error']) ) {
    		$this->access_token = $token['access_token'];
    		$this->refresh_token = $token['refresh_token'];
    	}
    	return $token;
    }
    
    /**
     * oauth验证请求
     * @param string $url 请求的url地址
     * @param string $method 请求url的方式 可选：get post delete
     * @param array $parameters get post delete所需的参数，必须为array
     * @param bool $multi 是否是二进制上传？ 默认为false，当需要上传图片或者文件时，次参数请保证为true
     * @return array||string
     */
    private function oAuthRequest($url, $method, $parameters, $multi = false){
    	$url = self::$api_uri.$url;
    	switch ($method) {
    		case 'GET':
    			$url = $url . '?' . http_build_query($parameters);
    			return $this->http($url, 'GET');
    		default:
    			$headers = array();
    			if (!$multi && (is_array($parameters) || is_object($parameters)) ) {
    				$body = http_build_query($parameters);
    			} else {
    				$body = self::build_http_query_multi($parameters);
    				$headers[] = "Content-Type: multipart/form-data; boundary=" . self::$boundary;
    			}
    			return $this->http($url, $method, $body, $headers);
    		}
    }
    
    private function http($url, $method, $postfields = NULL, $headers = array()) {
    	$this->http_info = array();
    	$ci = curl_init();
    	/* Curl settings */
    	curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    	curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
    	curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
    	curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
    	curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
    	curl_setopt($ci, CURLOPT_ENCODING, "");
    	curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
    	curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
    	curl_setopt($ci, CURLOPT_HEADER, FALSE);
    
    	switch ($method) {
    		case 'POST':
    			curl_setopt($ci, CURLOPT_POST, TRUE);
    			if (!empty($postfields)) {
    				curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
    				$this->postdata = $postfields;
    			}
    			break;
    		case 'DELETE':
    			curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
    			if (!empty($postfields)) {
    				$url = "{$url}?{$postfields}";
    			}
    	}
    
    	if ( isset($this->access_token) && $this->access_token ) $headers[] = "Authorization: Bearer ".$this->access_token;
    	$headers[] = "API-RemoteIP: " . $_SERVER['REMOTE_ADDR'];
    	curl_setopt($ci, CURLOPT_URL, $url );
    	curl_setopt($ci, CURLOPT_HTTPHEADER, $headers );
    	curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
    
    	$response = curl_exec($ci);
    	$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    	$this->http_info = array_merge($this->http_info, curl_getinfo($ci));
    	$this->url = $url;
    	//是否开始调试模式？
    	if ($this->debug) {
    		echo "=====post data======\r\n";
    		var_dump($postfields);
    
    		echo '=====info====='."\r\n";
    		print_r( curl_getinfo($ci) );
    
    		echo '=====$response====='."\r\n";
    		print_r( $response );
    	}
    	curl_close ($ci);
    	return $response;
    }
    
    /**
     * 获取header长度和大小
     * @param object $ch
     * @param array $header
     * @return number
     */
    private function getHeader($ch, $header) {
    	$i = strpos($header, ':');
    	if (!empty($i)) {
    		$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
    		$value = trim(substr($header, $i + 2));
    		$this->http_header[$key] = $value;
    	}
    	return strlen($header);
    }
    
    /**
     * 采用get模式进行curl处理数据
     * @param string $url uri地址
     * @param array $parameters 需要处理的数据
     * @return Ambigous <multitype:, string, mixed>
     */
    public function get($url, $parameters = array()) {
    	$response = $this->oAuthRequest($url, 'GET', $parameters);
    	return $response;
    }
    
    /**
     * 采用POST模式进行curl处理数据
     * @param string $url uri地址
     * @param array $parameters 需要处理的数据
     * @return Ambigous <multitype:, string, mixed>
     */
    public function post($url, $parameters = array(), $multi = false) {
    	$response = $this->oAuthRequest($url, 'POST', $parameters, $multi );
    	return $response;
    }
    
    /**
     * 采用DELETE模式进行curl处理数据
     * @param string $url uri地址
     * @param array $parameters 需要处理的数据
     * @return Ambigous <multitype:, string, mixed>
     */
    public function delete($url, $parameters = array()) {
    	$response = $this->oAuthRequest($url, 'DELETE', $parameters);
    	return $response;
    }
    
    /**
     * 创建content/type
     * 		在豆瓣发送一条带有图片的我说时，需要指定content/type，curl @http://www.xxx.com/a.jpg方式又不能完美觉得，固采用curl模拟表单提交的方法进行处理文件或者图片
     * @param array $params post||delete数据
     * @return string
     */
    public static function build_http_query_multi($params) {
    	if (!$params) return '';
 
    	$pairs = array();
    
    	self::$boundary = $boundary = uniqid('------------------');
    	$MPboundary = '--'.$boundary;
    	$endMPboundary = $MPboundary. '--';
    	$multipartbody = '';
    
    	foreach ($params as $parameter => $value) {
    
    		if( in_array($parameter, array('pic', 'image')) && $value{0} == '@' ) {
    			$url = ltrim( $value, '@' );
    			$content = file_get_contents( $url );
    			$array = explode( '?', basename( $url ) );
    			$filename = $array[0];
    
    			$multipartbody .= $MPboundary . "\r\n";
    			$multipartbody .= 'Content-Disposition: form-data; name="' . $parameter . '"; filename="' . $filename . '"'. "\r\n";
    			$multipartbody .= "Content-Type: image/jpeg\r\n\r\n";
    			$multipartbody .= $content. "\r\n";
    		} else {
    			$multipartbody .= $MPboundary . "\r\n";
    			$multipartbody .= 'content-disposition: form-data; name="' . $parameter . "\"\r\n\r\n";
    			$multipartbody .= $value."\r\n";
    		}
    
    	}
    	$multipartbody .= $endMPboundary;
    	return $multipartbody;
    }
}

/**
 * 豆瓣客户端操作类V2
 * @author 内裤叔叔
 *
 */
class DoubanTClientV2{
	private $oauth;
	
	
	/**
	 * 初始化豆瓣客户端操作类
	 * @param string $akey 
	 * @param string $skey  
	 * @param string $access_token 
	 * @param string $refresh_token
	 * 需在用户进行授权后才能执行此操作
	 */
	public function __construct($akey, $skey, $access_token, $refresh_token = NULL){
		$this->oauth = new DoubanOauthV2($akey, $skey, $access_token, $refresh_token);
	}
	
	/**
	 * 获取当前授权用户的个人信息
	 * @return array
	 */
	public function get_my_info(){
		$params = array();
		$response = $this->oauth->get("v2/user/~me",$params);
		$json = json_decode($response,true);
		return $json;
	}
	
	/**
	 * 获取指定用户资料
	 * @param string $uname 用户ID或者名号 （唯一）
	 * @return array
	 */
	public function get_user($uname){
		$params = array();
		$response = $this->oauth->get("v2/user/{$uname}",$params);
		$json = json_decode($response,true);
		return $json;
	}
	
	/**
	 * 搜索用户
	 * @param string $q 搜索用于关键字
	 * @param int $start 开始位置，默认为0
	 * @param int $count 返回搜索结构总数，默认为20
	 * @return array
	 */
	public function search_user($q,$start = NULL,$count = Null){
		$params = array();
		$params['q'] = $q;
		$params['start'] = $start?$start:"";
		$params['count'] = $count?$count:"";
		$response = $this->oauth->get("v2/user",$params);
		$json = json_decode($response,true);
		return $json;
	}
	
	/**
	 * 发送一条纯文字我说动态，
	 * @param string $key 豆瓣应用的key *必填
	 * @param string $status  我说内容，不超过140个字（建议）*必填
	 * @param string $rec_title  需要分享网址的标题
	 * @param string $rec_url 需要分享的网址连接
	 * @param string $rec_desc 网址说明
	 * @return array
	 */
	public function update($key ,$status ,$rec_title = NULL, $rec_url = NULL, $rec_desc = NULL){
		$params = array();
		$params['source'] = $key;
		$params['text'] = $status;
		$params['rec_title'] = $rec_title;
		$params['rec_url'] = $rec_url;
		$params['rec_desc'] = $rec_desc;
		$response = $this->oauth->post("shuo/v2/statuses/", $params);
		$json = json_decode($response,true);
		return $json;
	}
	
	/**
	 * 发送一条包含图片信息的我说
	 * @param string $key 应用KEY 必填
	 * @param string $status 分享内容 必填
	 * @param string $pic 图片地址，请使用绝对地址，必填
	 * @param string $rec_title  需要分享网址的标题
	 * @param string $rec_url 需要分享的网址连接
	 * @param string $rec_desc 网址说明
	 * @return array
	 */
	public function upload($key ,$status , $pic ,$rec_title = NULL, $rec_url = NULL, $rec_desc = NULL){
		$params = array();
		$params['source'] = $key;
		$params['text'] = $status;
		$params['image'] = "@".$pic;
		$params['rec_title'] = $rec_title;
		$params['rec_url'] = $rec_url;
		$params['rec_desc'] = $rec_desc;
		$response = $this->oauth->post("shuo/v2/statuses/", $params,true);
		$json = json_decode($response,true);
		return $json;
	}
	
	/**
	 * 获取当前登录用户及其所关注用户的最新广播(友邻广播)
	 * @param int $since_id 若指定此参数，则只返回ID比since_id大的广播消息（即比since_id发表时间晚的广播消息）。
	 * @param int $until_id 若指定此参数，则返回ID小于或等于until_id的广播消息
	 * @param int $count 一次获取的总数 默认20，最大200
	 * @param int $start 分页标识 默认0
	 * @return array
	 */
	public function home_timeline($since_id = NULL, $until_id = NULL, $count = Null, $start = NULL){
		$params = array();
		$params['since_id'] = $since_id?floatval($since_id):"";
		$params['until_id'] = $until_id?floatval($until_id):"";
		$params['count'] = $count?floatval($count):"";
		$params['start'] = $start?floatval($start):"";
		$response = $this->oauth->get("shuo/v2/statuses/home_timeline",$params);
		$json = json_decode($response,true);
		return $json;
	}
	
	/**
	 * 获取用户发布的广播列表
	 * @param int(string) $userid 用户ID
	 * @param int $since_id 若指定此参数，则只返回ID比since_id大（即比since_id发表时间晚的）的广播消息。
	 * @param int $until_id 若指定此参数，则返回ID小于或等于until_id的广播消息
	 * * 如果:id、user_id、screen_name三个参数均未指定，则返回当前登录用户最近发表的广播消息列表。*
	 * @return array
	 */
	public function user_timeline($userid , $since_id = NULL, $until_id = NULL){
		$params = array();
		$params['since_id'] = $since_id?floatval($since_id):"";
		$params['until_id'] = $until_id?floatval($until_id):"";
		$response = $this->oauth->get("shuo/v2/statuses/user_timeline/{$userid}",$params);
		$json = json_decode($response,true);
		return $json;
	}
	
	/**
	 * 读取（或者删除）一条广播
	 * @param string $method 单条广播动作 get为读取 delete 为删除
	 * @param int $sid 广播ID
	 * @return array
	 */
	public function statuses($method,$sid){
		$params = array();
		if(strtoupper($method) == "GET"){
			$response = $this->oauth->get("shuo/v2/statuses/{$sid}");
		}elseif(strtoupper($method) == "DELETE"){
			$response = $this->oauth->delete("shuo/v2/statuses/{$sid}");
		}
		$json = json_decode($response,true);
		return $json;
	}
	
	/**
	 * 获取单条回复的内容或者删除该回复
	 * @param string $method 获取或者删除动作 get 为获取 delete为删除 || 注，楼主、发帖人、管理员能删除
	 * @param int $sid 广播ID
	 * @return mixed
	 */
	public function statuses_comment($method,$sid){
		$params = array();
		if(strtoupper($method) == "GET"){
			$response = $this->oauth->get("shuo/v2/statuses/comment/{$sid}");
		}elseif(strtoupper($method) == "DELETE"){
			$response = $this->oauth->delete("shuo/v2/statuses/comment/{$sid}");
		}
		$json = json_decode($response,true);
		return $json;
	}
	
	/**
	 * 转播
	 * @param string $method 转播获取方式 GET  获取最近转播的用户列表  POST 转播
	 * @param int $sid 广播ID
	 * @return array
	 */
	public function reshare($method,$sid){
		$params = array();
		if(strtoupper($method) == "GET"){
			$response = $this->oauth->get("shuo/v2/statuses/{$sid}/reshare");
		}elseif(strtoupper($method) == "POST"){
			$response = $this->oauth->post($"shuo/v2/statuses/{$sid}/reshare");
		}
		$json = json_decode($response,true);
		return $json;
	}
	
	/**
	 * 赞
	 * @param string $method 赞的方式 
	 * 						 1. 获取一条广播的赞相关信息 GET
							 2. 赞一条广播 POST
							 3. 取消赞 DELETE
	 * @param unknown_type $sid
	 * @return array
	 */
	public function like($method,$sid){
		$params = array();
		if(strtoupper($method) == "GET"){
			$response = $this->oauth->get("shuo/v2/statuses/{$sid}/like");
		}elseif(strtoupper($method) == "POST"){
			$response = $this->oauth->post("shuo/v2/statuses/{$sid}/like");
		}elseif(strtoupper($method) == "DELETE"){
			$response = $this->oauth->delete("shuo/v2/statuses/{$sid}/like");
		}
		$json = json_decode($response,true);
		return $json;
	}
	
	/**
	 * 获取用户关注列表
	 * @param string||int $uid 用户ID
	 * @param string||int $tag 该tag的id
	 * @return array
	 */
	public function following($uid,$tag = NULL){
		$params = array();
		$params["tag"] = $tag?$tag:"";
		$response = $this->oauth->get("shuo/v2/users/{$uid}/follower",$params);
		$json = json_decode($response,true);
		return $json;
	}
}