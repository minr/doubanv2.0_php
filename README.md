豆瓣oauth 2.0 授权和操作类
文件说明：
	douban_class.php 授权实例
	douban.v2.class.php 豆瓣授权操作类
		class DoubanOauthV2 豆瓣授权认证类
		class DoubanTClientV2 豆瓣客户端操作类

使用操作：
1.请确保session_start()开启，如果你需要用到session的话;
2.填写你的apikey和apisecrect
3.实例化豆瓣授权类

授权步骤：
<?php
	$douban = new DoubanOauthV2($apikey, $apisSecret);
	//跳转到授权地址
	header("Location:".$douban->getAuthorizeURL($callback));
	//得到授权AccessToken
	$token = $douban->getAccessToken($callback, $_GET['code']);
?>
获取当前授权用户资料
<?php
	$dou = new DoubanTClientV2($apikey, $apisSecret, $token['access_token']);
	$info = $dou->get_my_info();
	print_r($info);
?>
发送一条动态
<?php
	$dou = new DoubanTClientV2($apikey, $apisSecret, $token['access_token']);
	$info = $dou->update($apikey,"hellow,douban");
	print_r($info);
?>
发送一条图片动态
<?php
	$dou = new DoubanTClientV2($apikey, $apisSecret, $token['access_token']);
	$info = $dou->upload($apikey,"hellow,douban","http://img3.douban.com/icon/g76728-1.jpg");
	print_r($info);
?>
以上操作，均需要授权

具体操作：
<?php
session_start();
header("Content-type:text/html;charset=utf-8");
require_once("douban.v2.class.php");
    //私有化变量
$apikey = ""; //你的豆瓣API KEY
$apisSecret = ""; //你的豆瓣 API KEY_SECRET
$callback = "http://127.0.0.1:8085/douban_class.php"; //你的豆瓣回调地址
$douban = new DoubanOauthV2($apikey, $apisSecret);
if($_GET['code']){
	if(!$_SESSION['token']){
		$token = $douban->getAccessToken($callback, $_GET['code']);
		$_SESSION['token'] = json_encode($token);
	}else{
		$token = json_decode($_SESSION['token'],true);
		//获取自己
		$dou = new DoubanTClientV2($apikey, $apisSecret, $token['access_token']);
		print_r($dou->update($apikey, "hello,douban！"));
	} 
}else{
	header("Location:".$douban->getAuthorizeURL($callback));
}
?>

如有问题，欢迎大家补充和完善当前类

