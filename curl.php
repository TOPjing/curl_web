<?php

$phone = isset($_GET['phone'])?$_GET['phone']:'';   //最好加个默认值
$Test = new curlGetPhoneInfo($phone);
$Test->setGetUrl("https://www.biaojiquxiao.com/");

if($Test->curlGetCookie()) {

	$Test->getPhotoUrl("https://www.biaojiquxiao.com/code");
	if($Test->curlGetImg()) {
	
		if($Test->txGetWords()) {

			if($Test->ourGetXy()) {

				$Test->setPostUrl("https://www.biaojiquxiao.com/checkCodeExc");
				$Test->postCon();
			}
		}
	}
}


class curlGetPhoneInfo{
	private $phone;
	private $string;
	private $cookie;
	private $x;
	private $y;
	private $getCookieUrl;
	private $postDataUrl;
	private $getPhotoUrl;

	//构造函数
	public function __construct($phone) {
		$this->phone = $phone;
	}

	//设置获取Cookie的url网址
	public function setGetUrl($url) {
		$this->getCookieUrl = $url;
	}

	//设置发送数据的url网址
	public function setPostUrl($url) {
		$this->postDataUrl = $url;
	}

	//设置请求图片地址
	public function getPhotoUrl($url) {
		$this->getPhotoUrl = $url;
	}

	//获取Cookie信息
	public function curlGetCookie() {
		//访问链接时要发送的头信息
		$header = array("content-type: application/x-www-form-urlencoded; charset=UTF-8");
		//curl初始化
		$curl = curl_init();
		//设置要爬取的网页的网址 
		curl_setopt($curl, CURLOPT_URL, $this->getCookieUrl);
		//一个用来设置HTTP头字段的数组。使用如下的形式的数组进行设置： array('Content-type: text/plain', 'Content-length: 100')
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		//如果你想把一个头包含在输出中，设置这个选项为一个非零值，我这里是要输出，所以为 1
		curl_setopt($curl, CURLOPT_HEADER, 1);
		//将 curl_exec()获取的信息以文件流的形式返回，而不是直接输出。设置为0是直接输出
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
		//设置跟踪页面的跳转，有时候你打开一个链接，在它内部又会跳到另外一个，就是这样理解
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		//获取的cookie 保存到指定的 文件路径，我这里是相对路径，可以是$变量
		//linux系统设置权限777
		curl_setopt($curl, CURLOPT_COOKIEJAR, 'cookie.txt');
		//执行curl,抓取内容
		$content = curl_exec($curl);
		//判断是否报错
		if(curl_errno($curl)){
			//这里是设置个错误信息的反馈
			echo 'Curl error: '.curl_error($ch);exit(); 
		}    
		if($content==false){
			echo "get_content_null";exit();
		}
		//这里采用正则匹配来获取cookie并且保存它到变量$str里，这就是为什么上面可以发送cookie变量的原因
		preg_match('/Set-Cookie:(.*);/iU',$content,$str); 
		//获得COOKIE（SESSIONID）
		$cookie = $str[1]; 
		//关闭会话
		curl_close($curl);
		$this->cookie = $cookie;
		return true;
	}

	//获取图片
	public function curlGetImg() {

		$curl = curl_init();
		$url = $this->getPhotoUrl;
		//设置图片爬取地址
		curl_setopt($curl, CURLOPT_URL, $url);
		//不直接打印输出
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//得到图片数据信息
		$output = curl_exec($curl);
		//关闭curl
		curl_close($curl);
		//将数据写入图片格式的文件
		//linux设置权限777
		$myfile = fopen("tt.jfif", "w");
		//写入文件
		fwrite($myfile, $output);
		fclose($myfile);
		// var_dump($output);
		return true;
	}

	//上传腾讯Api获得文字
	public function txGetWords() {

		$curl = curl_init();
		//Api传送数据
		$data = array(
			'appid' => '',  //腾讯api的appid
			'url' => '',    //文件位置
		);
		//转Json格式
		$Json = json_encode($data);
		//头信息
		$header = array(
			'Authorization: +4NHGsK/sJh9TjmEcLdCJbu44oRhPTEyNTU2MDc4NjgmYj0maz1BS0lEOEIza0diUGxiR2oxN0hOOHBSdEFHQnZvZUozanRYZ2QmZT0xNTQ0MDgxNzI5JnQ9MTU0MTQ4OTcyOSZyPTE2MTY0JmY9',
			'Host: recognition.image.myqcloud.com',
			'Content-Length: '.strlen($Json),
			'Content-Type: application/json',
		);
		//设置网址
		curl_setopt($curl, CURLOPT_URL, "http://recognition.image.myqcloud.com/ocr/general");
		//设置发送头信息
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		//不直接打印结果
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//post发送请求
		curl_setopt($curl, CURLOPT_POST, true);
		//发送数据
		curl_setopt($curl, CURLOPT_POSTFIELDS, $Json);
		//执行，得到的结果传入This->String
		$this->string = curl_exec($curl);
		curl_close($curl);
		return true;
	}

	//得到XY坐标
	public function ourGetXy() {
		//将腾讯Api返回的字符串进行处理，得到我们需要的X和Y值
		$arr = json_decode($this->string,true);
		$word = $arr['data']['items'][0]['words'][4]['character'];
		$count = count($arr['data']['items']);
		for($i = 1 ; $i < $count ; $i++) {
			if($arr['data']['items'][$i]['words'][0]['character'] == $word) {
				$this->x = $arr['data']['items'][$i]['itemcoord']['x'];
				$this->y = $arr['data']['items'][$i]['itemcoord']['y'];
				return true;
			}
		}
		return false;
	}

	//Post发送信息得到返回内容
	public function postCon() {
		//post的数据
		$post_data = array(
			'x' => (int)$this->x,
			'y' => (int)$this->y,
			'number' => $this->phone
		);
		//将数据转成Json格式
		$post_data = json_encode($post_data);
		//产生一个urlencode之后的请求字符串，因为我们post，传送给网页的数据都是经过处理，一般是urlencode编码后才发送的
		// $post_data = is_array($post_data)?http_build_query($post_data):$post_data;
		//头部信息，上面的函数已说明
		$header = array(
			'Accept:*/*',
			'Accept-Charset:text/html,application/xhtml+xml,application/xml;q=0.7,*;q=0.3',
			'Accept-Encoding:gzip,deflate,sdch',
			'Accept-Language:zh-CN,zh;q=0.8',
			'Connection:keep-alive',
			'Content-Type:application/x-www-form-urlencoded',
			//'CLIENT-IP:'.$ip, 
			//'X-FORWARDED-FOR:'.$ip,
		);
		// echo 'This';
		//再次初始化curl
		$curl = curl_init();
		//设置网址
		curl_setopt($curl, CURLOPT_URL, $this->postDataUrl);
		//头部信息
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		//对认证证书来源的检查，不开启次功能
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		//从证书中检测 SSL 加密算法
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		//模拟用户使用的浏览器，自己设置
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:23.0) Gecko/20100101 Firefox/23.0");
		//自动设置referer
		curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
		//开启post
		curl_setopt($curl, CURLOPT_POST, 1);
		//HTTP请求头中"Accept-Encoding: "的值。支持的编码有"identity"，"deflate"和"gzip"。如果为空字符串""，请求头会发送所有支持的编码类型。
		// curl_setopt($curl, CURLOPT_ENCODING, "");
		//要传送的数据
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		//以变量形式发送cookie
		curl_setopt($curl, CURLOPT_COOKIE, $this->cookie);
		//网页跟随跳转
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		//设置超时限制，防止死循环
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		// 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
		curl_setopt($curl, CURLOPT_HEADER, 1);
		//是否不需要响应内部信息
		curl_setopt($curl, CURLOPT_NOBODY, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$tmpInfo = curl_exec($curl);
		if (curl_errno($curl)) {
			echo  'Curl error: ' . curl_errno($curl);exit();
		}
		curl_close($curl);
		// echo 'This';
		var_dump($tmpInfo);
	}
}





/**
curl状态码
状态原因
解释

0
正常访问
	
1
错误的协议
未支持的协议。此版cURL 不支持这一协议。

2
初始化代码失败
初始化失败。

3
URL格式不正确
URL 格式错误。语法不正确。

4
请求协议错误
	

5
无法解析代理
无法解析代理。无法解析给定代理主机。

6
无法解析主机地址
无法解析主机。无法解析给定的远程主机。

7
无法连接到主机
无法连接到主机。

8
远程服务器不可用
FTP 非正常的服务器应答。cURL 无法解析服务器发送的数据。

9
访问资源错误
FTP 访问被拒绝。服务器拒绝登入或无法获取您想要的特定资源或目录。最有可
能的是您试图进入一个在此服务器上不存在的目录。

11
FTP密码错误
FTP 非正常的PASS 回复。cURL 无法解析发送到PASS 请求的应答。

13
结果错误
FTP 非正常的的PASV 应答，cURL 无法解析发送到PASV 请求的应答。

14
FTP回应PASV命令
FTP 非正常的227格式。cURL 无法解析服务器发送的227行。

15
内部故障
FTP 无法连接到主机。无法解析在227行中获取的主机IP。

17
设置传输模式为二进制
FTP 无法设定为二进制传输。无法改变传输方式到二进制。

18
文件传输短或大于预期
部分文件。只有部分文件被传输。

19
RETR命令传输完成
FTP 不能下载/访问给定的文件， RETR (或类似)命令失败。

21
命令成功完成
FTP quote 错误。quote 命令从服务器返回错误。

22
返回正常
HTTP 找不到网页。找不到所请求的URL 或返回另一个HTTP 400或以上错误。
此返回代码只出现在使用了-f/--fail 选项以后。

23
数据写入失败
写入错误。cURL 无法向本地文件系统或类似目的写入数据。

25
无法启动上传
FTP 无法STOR 文件。服务器拒绝了用于FTP 上传的STOR 操作。

26
回调错误
读错误。各类读取问题。

27
内存分配请求失败
内存不足。内存分配请求失败。

28
访问超时
操作超时。到达指定的超时期限条件。

30
FTP端口错误
FTP PORT 失败。PORT 命令失败。并非所有的FTP 服务器支持PORT 命令，请
尝试使用被动(PASV)传输代替！

31
FTP错误
FTP 无法使用REST 命令。REST 命令失败。此命令用来恢复的FTP 传输。

33
不支持请求
HTTP range 错误。range "命令"不起作用。

34
内部发生错误
HTTP POST 错误。内部POST 请求产生错误。

35
SSL/TLS握手失败
SSL 连接错误。SSL 握手失败。

36
下载无法恢复
FTP 续传损坏。不能继续早些时候被中止的下载。

37
文件权限错误
文件无法读取。无法打开文件。权限问题？

38
LDAP可没有约束力
LDAP 无法绑定。LDAP 绑定(bind)操作失败。

39
LDAP搜索失败
LDAP 搜索失败。

41
函数没有找到
功能无法找到。无法找到必要的LDAP 功能。

42
中止的回调
由回调终止。应用程序告知cURL 终止运作。

43
内部错误
内部错误。由一个不正确参数调用了功能。

45
接口错误
接口错误。指定的外发接口无法使用。

47
过多的重定向
过多的重定向。cURL 达到了跟随重定向设定的最大限额跟

48
无法识别选项
指定了未知TELNET 选项。

49
TELNET格式错误
不合式的telnet 选项。

51
远程服务器的SSL证书
peer 的SSL 证书或SSH 的MD5指纹没有确定。

52
服务器无返回内容
服务器无任何应答，该情况在此处被认为是一个错误。

53
加密引擎未找到
找不到SSL 加密引擎。

54
设定默认SSL加密失败
无法将SSL 加密引擎设置为默认。

55
无法发送网络数据
发送网络数据失败。

56
衰竭接收网络数据
在接收网络数据时失败。

57
	
58
本地客户端证书
本地证书有问题。

59
无法使用密码
无法使用指定的SSL 密码。

60
凭证无法验证
peer 证书无法被已知的CA 证书验证。

61
无法识别的传输编码
无法辨识的传输编码。

62
无效的LDAP URL
无效的LDAP URL。

63
文件超过最大大小
超过最大文件尺寸。

64
FTP失败
要求的FTP 的SSL 水平失败。

65
倒带操作失败
发送此数据需要的回卷(rewind)失败。

66
SSL引擎失败
初始化SSL 引擎失败。

67
服务器拒绝登录
用户名、密码或类似的信息未被接受，cURL 登录失败。

68
未找到文件
在TFTP 服务器上找不到文件。

69
无权限
TFTP 服务器权限有问题。

70
超出服务器磁盘空间
TFTP 服务器磁盘空间不足。

71
非法TFTP操作
非法的TFTP 操作。

72
未知TFTP传输的ID
未知TFTP 传输编号(ID)。

73
文件已经存在
文件已存在(TFTP) 。

74
错误TFTP服务器
无此用户(TFTP) 。

75
字符转换失败
字符转换失败。

76
必须记录回调
需要字符转换功能。

77
CA证书权限
读SSL 证书出现问题(路径？访问权限？ ) 。

78
URL中引用资源不存在
URL 中引用的资源不存在。

79
错误发生在SSH会话
SSH 会话期间发生一个未知错误。

80
无法关闭SSL连接
未能关闭SSL 连接。

81
服务未准备
	
82
无法载入CRL文件
无法加载CRL 文件，丢失或格式不正确(在7.19.0版中增加) 。

83
发行人检查失败
签发检查失败(在7.19.0版中增加) 。
