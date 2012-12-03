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