����oauth 2.0 ��Ȩ�Ͳ�����
�ļ�˵����
	douban_class.php ��Ȩʵ��
	douban.v2.class.php ������Ȩ������
		class DoubanOauthV2 ������Ȩ��֤��
		class DoubanTClientV2 ����ͻ��˲�����

ʹ�ò�����
1.��ȷ��session_start()�������������Ҫ�õ�session�Ļ�;
2.��д���apikey��apisecrect
3.ʵ����������Ȩ��

��Ȩ���裺
<?php
	$douban = new DoubanOauthV2($apikey, $apisSecret);
	//��ת����Ȩ��ַ
	header("Location:".$douban->getAuthorizeURL($callback));
	//�õ���ȨAccessToken
	$token = $douban->getAccessToken($callback, $_GET['code']);
?>
��ȡ��ǰ��Ȩ�û�����
<?php
	$dou = new DoubanTClientV2($apikey, $apisSecret, $token['access_token']);
	$info = $dou->get_my_info();
	print_r($info);
?>
����һ����̬
<?php
	$dou = new DoubanTClientV2($apikey, $apisSecret, $token['access_token']);
	$info = $dou->update($apikey,"hellow,douban");
	print_r($info);
?>
����һ��ͼƬ��̬
<?php
	$dou = new DoubanTClientV2($apikey, $apisSecret, $token['access_token']);
	$info = $dou->upload($apikey,"hellow,douban","http://img3.douban.com/icon/g76728-1.jpg");
	print_r($info);
?>
���ϲ���������Ҫ��Ȩ

���������
<?php
session_start();
header("Content-type:text/html;charset=utf-8");
require_once("douban.v2.class.php");
    //˽�л�����
$apikey = ""; //��Ķ���API KEY
$apisSecret = ""; //��Ķ��� API KEY_SECRET
$callback = "http://127.0.0.1:8085/douban_class.php"; //��Ķ���ص���ַ
$douban = new DoubanOauthV2($apikey, $apisSecret);
if($_GET['code']){
	if(!$_SESSION['token']){
		$token = $douban->getAccessToken($callback, $_GET['code']);
		$_SESSION['token'] = json_encode($token);
	}else{
		$token = json_decode($_SESSION['token'],true);
		//��ȡ�Լ�
		$dou = new DoubanTClientV2($apikey, $apisSecret, $token['access_token']);
		print_r($dou->update($apikey, "hello,douban��"));
	} 
}else{
	header("Location:".$douban->getAuthorizeURL($callback));
}
?>

�������⣬��ӭ��Ҳ�������Ƶ�ǰ��

