<?php
namespace Wechat;
class Wechat {

	private $config = [];
	private $errorMsg = 'Nothing';

	/**
	 * 构造方法,初始化微信公众号配置.
	 */
	public function __construct() {
		$this->config['appid'] = config('wechat.appid');
		$this->config['appsecret'] = config('wechat.appsecret');
	}

	/**
	 * 获取响应中的原始数据
	 * @return string 原始数据
	 */
	public static function getRawData() {
		return file_get_contents('php://input');
	}

	/**
	 * XML转成数组
	 * @param  string $xml XML
	 * @return array      数组
	 */
	public static function xmlToArray($xml) {
		libxml_disable_entity_loader(true);
		$xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		$value = json_decode(json_encode($xmlstring), true); // json_encode JSON_UNESCAPED_UNICODE
		return $value;
	}

	/**
	 * 取两个字符串中间的字符串
	 * @param  string $str      全部字符串
	 * @param  string $leftStr  左边的字符串
	 * @param  string $rightStr 右边的字符串
	 * @return string           中间的字符串
	 */
	public static function stringCenter($str, $leftStr, $rightStr) {
		$left = strpos($str, $leftStr);
		$right = strpos($str, $rightStr, $left);
		if ($left < 0 or $right < $left) {
			return '';
		}
		return substr($str, $left + strlen($leftStr), $right - $left - strlen($leftStr));
	}
	/**
	 * 获取字符串的左边
	 * @param  string $string 全部字符串
	 * @param  string $right  根据字符的右边
	 * @return string         得到左边的字符串
	 */
	public function stringLeft($string,$right){
		$reg = '/(.*)'.$right.'.*/';
		preg_match($reg,$string,$data);
		if(empty($data[1])){
			return false;
		}
		return $data[1];
	}

	/**
	 * 获取字符串的右边
	 * @param  string $string 全部字符串
	 * @param  string $left   根据字符串左边
	 * @return string         得到右边的字符串
	 */
	public function stringRight($string,$left){
		$leftLength = strlen($left);
		$index = strpos($string,$left);
		if($index == -1){
			return false;
		}
		$result = substr($string, $index+$leftLength); 
		return $result;
	}
	
	/**
	 * 去掉空格和换行
	 * @param  string $str 要处理的字符串
	 * @return string      处理后的字符串
	 */
	public static function removeSpace($str) {
		$qian = array(" ", "　", "\t", "\n", "\r");
		return str_replace($qian, '', $str);
	}

	/**
	 * CURL
	 * @param  string  $url      目标地址
	 * @param  array $postData POST数据,若填写则为POST方式请求
	 * @param  boolean $json     是否以POST形式发送Json数据
	 * @param  array   $header   头部信息
	 * @return string            返回结果
	 */
	public function http($url, $postData = false, $json = false, $header = []) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if ($postData != false or $json != false) {
			if ($json != false) {
				$jsonPostData = json_encode($postData, JSON_UNESCAPED_UNICODE);
				$tempHeader = [
					'Content-Type: application/json',
					'Content-Length: ' . strlen($jsonPostData),
				];
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPostData);
			} else {
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
			}

		}
		if ($header != [] or $json == true) {
			if ($json == true) {
				$header = array_merge($tempHeader, $header);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}
		// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 是否跟着301跳转
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}

	/**
	 * 微信公众号解密
	 * @param  string $data 要解密的信息
	 * @return string       解密完成的
	 */
	protected function decrypt($data) {
		return $data;
	}

	/**
	 * 错误码反馈
	 * @param  mixed $res 错误码或微信返回错误提示的Json
	 * @return bool      错误码是否错误,错误返回false
	 */
	protected function errorCode($res) {
		if (!is_array($res)) {
			$res = json_decode($res, true);
		}
		if(empty($res['errcode'])){
		    return true;
		}
		switch ($res['errcode']) {
		case '0':
			return true;
			break;
		case '-1':
			$this->errorMsg = '[-1]系统繁忙，此时请开发者稍候再试';
			break;
		case '40001':
			$this->errorMsg = '[40001]AppSecret错误或者AppSecret不属于这个公众号，请开发者确认AppSecret的正确性';
			break;
		case '40002':
			$this->errorMsg = '[40002]请确保grant_type字段值为client_credential';
			break;
		case '40003':
			$this->errorMsg = '[40003]不合法的OpenID，请开发者确认OpenID（该用户）是否已关注公众号，或是否是其他公众号的OpenID';
		case '40164':
			$this->errorMsg = '[40164]调用接口的IP地址不在白名单中，请在接口IP白名单中进行设置';
			break;
		case '40013':
			$this->errorMsg = '[40013]AppID无效';
			break;
		case '40018':
			$this->errorMsg = '[40018]无效菜单名长度';
			break;
		default:
			$this->errorMsg = '[' . $res['errcode'] . ']' . $res['errmsg'];
			break;
		}
		return false;
	}

	/**
	 * 获取上一条错误信息
	 * @return string
	 */
	public function getError() {
		return $this->errorMsg;
	}

	/**
	 * 微信配置服务器的验证服务器
	 * @return string
	 */
	public static function openServer() {
		echo $_GET['echostr'];
		return $_GET['echostr'];
	}

	/**
	 * 获取access_token
	 * @param  boolean $raw 是否返回原始数据
	 * @return array       返回access_token和有效期
	 */
	public function getAccessToken($raw = false) {
		$res = $this->http('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->config['appid'] . '&secret=' . $this->config['appsecret']);
		if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res, true);
		if (empty($data['access_token'])) {
			$this->errorCode($data);
			return false;
		}
		return $data;
	}

	/**
	 * 获取微信服务器IP
	 * @param  string  $access_token accesstoken
	 * @param  boolean $raw          是否返回原始数据
	 * @return array                服务器IP和端口列表
	 */
	public function getWechatServer($access_token, $raw = false) {
		$res = $this->http('https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=' . $access_token);
		if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $raw;
		}
		$data = json_decode($res, true);
		if (empty($data['ip_list'])) {
			$this->errorCode($data);
			return false;
		} else {
			return $data['ip_list'];
		}

	}

	/**
	 * 自定义菜单创建 生效需等待几分钟
	 * @param  string  $access_token access_token
	 * @param  array  $data         结构字段
	 * @param  boolean $raw          是否返回原始数据
	 * @return boolean                成功返回true
	 */
	public function menuCreate($access_token, $data, $raw = false) {
		$res = $this->http('https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $access_token, $data, true);
		if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res, true);
		if ($this->errorCode($data)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 自定义菜单查询
	 * @param  string  $access_token access_token
	 * @param  boolean $raw          是否返回原始数据
	 * @return array                结构字段
	 */
	public function menuQuery($access_token, $raw = false) {
		$res = $this->http('https://api.weixin.qq.com/cgi-bin/menu/get?access_token=' . $access_token);
		if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res, true);
		if (!empty($data['errcode'])) {
			$this->errorCode($data);
			return false;
		}
		return $data;
	}

	/**
	 * 自定义菜单删除
	 * @param  string  $access_token access_token
	 * @param  boolean $raw          是否返回原始数据
	 * @return boolean                成功返回true
	 */
	public function menuDelete($access_token, $raw = false) {
		$res = $this->http('https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . $access_token);
		if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res, true);
		if ($this->errorCode($data)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 创建个性化菜单
	 * @param  string  $access_token access_token
	 * @param  array  $data         数组信息结构
	 * @param  boolean $raw          是否返回原始数据
	 * @return Boolean                成功返回true
	 */
	public function menuCreateCharacter($access_token, $data, $raw = false) {
		$res = $this->http('https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=' . $access_token, $data, true);
		if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res, true);
		if ($this->errorCode($data)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 删除个性化菜单
	 * @param  string  $access_token access_token
	 * @param  string  $menuid       个性化菜单ID
	 * @param  boolean $raw          是否返回原始数据
	 * @return Boolean                操作成功返回true
	 */
	public function menuDeleteCharacter($access_token, $menuid, $raw = false) {
		$data['menuid'] = $menuid;
		$res = $this->http('https://api.weixin.qq.com/cgi-bin/menu/delconditional?access_token=' . $access_token, $data, true);
		if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res, true);
		if ($this->errorCode($data)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 测试个性化菜单
	 * @param  string  $access_token access_token
	 * @param  string  $user_id      用户的微信号或OpenID
	 * @param  boolean $raw          上是否返回原始数据
	 * @return array                成功返回结构,是否返回false
	 */
	public function menuTestCharacter($access_token, $user_id, $raw = false) {
		$data['$user_id'] = $user_id;
		$res = $this->http('https://api.weixin.qq.com/cgi-bin/menu/trymatch?access_token=' . $access_token, $data, true);
		if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res, true);
		if (!empty($data['errcode'])) {
			$this->errorCode($data);
			return false;
		}
		return $data;
	}

	/**
	 * 查询已配置好的菜单结构
	 * @param  string  $access_token acccess_token
	 * @param  boolean $raw          是否返回原始数据
	 * @return array                信息结构数组
	 */
	public function menuQueryFull($access_token, $raw = false) {
		$res = $this->http('https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info?access_token=' . $access_token);
		if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res, true);
		if (!empty($data['errcode'])) {
			$this->errorCode($data);
			return false;
		}
		return $data;
	}

	/**
	 * 生成普通消息XML
	 * @param  array $xmlData 经过处理的请求中的XML
	 * @param  string $text    回复的消息内容
	 * @return string          生成的XML
	 */
	public static function makeTextXml($xmlData, $text) {
		$respone = '<xml>
        <ToUserName><![CDATA[' . $xmlData['FromUserName'] . ']]></ToUserName>
        <FromUserName><![CDATA[' . $xmlData['ToUserName'] . ']]></FromUserName>
        <CreateTime>' . time() . '</CreateTime>
        <MsgType><![CDATA[text]]></MsgType>
        <Content><![CDATA[' . $text . ']]></Content>
        </xml>';
		return $respone;
	}

	/**
	 * 生成图片消息XML
	 * @param  array $xmlData 经过处理的请求中的XML
	 * @param  string $MediaId 素材库中的ID
	 * @return string          生成的XML
	 */
	public static function makeImageXml($xmlData, $MediaId) {
		$respone = '<xml>
        <ToUserName><![CDATA[' . $xmlData['FromUserName'] . ']]></ToUserName>
        <FromUserName><![CDATA[' . $xmlData['ToUserName'] . ']]></FromUserName>
        <CreateTime>' . time() . '</CreateTime>
        <MsgType><![CDATA[image]]></MsgType>
        <Image>
        <MediaId><![CDATA[' . $MediaId . ']]></MediaId>
        </Image>
        </xml>';
		return $respone;
	}

	/**
	 * 生成语音消息XML
	 * @param  array $xmlData 经过处理的请求中的XML
	 * @param  string $MediaId 素材库中的ID
	 * @return string          生成的XML
	 */
	public static function makeVoiceXml($xmlData, $MediaId) {
		$respone = '<xml>
        <ToUserName><![CDATA[' . $xmlData['FromUserName'] . ']]></ToUserName>
        <FromUserName><![CDATA[' . $xmlData['ToUserName'] . ']]></FromUserName>
        <CreateTime>' . time() . '</CreateTime>
        <MsgType><![CDATA[voice]]></MsgType>
        <Voice>
        <MediaId><![CDATA[' . $MediaId . ']]></MediaId>
        </Voice>
        </xml>';
		return $respone;
	}

	/**
	 * 生成视频消息XML
	 * @param  array  $xmlData     经过处理的请求中的XML
	 * @param  string  $MediaId     素材库中的ID
	 * @param  string $Title       视频标题,默认不填写
	 * @param  string $Description 视频简介,默认不填写
	 * @return string               生成的XML
	 */
	public static function makeVideoXml($xmlData, $MediaId, $Title = false, $Description = false) {
		$respone = '<xml>
        <ToUserName><![CDATA[' . $xmlData['FromUserName'] . ']]></ToUserName>
        <FromUserName><![CDATA[' . $xmlData['ToUserName'] . ']]></FromUserName>
        <CreateTime>' . time() . '</CreateTime>
        <MsgType><![CDATA[video]]></MsgType>
        <Video>
        <MediaId><![CDATA[' . $MediaId . ']]></MediaId>';
		if ($Title != false) {
			$respone = $respone . '<Title><![CDATA[' . $Title . ']]></Title>';
		}
		if ($Description != false) {
			$respone = $respone . '<Description><![CDATA[' . $Description . ']]></Description>';
		}
		$respone = $respone . '</Video>
        </xml>';
		return $respone;
	}

	/**
	 * 生成音乐消息XML
	 * @param  array   $xmlData      经过处理的请求中的XNL
	 * @param  string $Title        标题,默认不填写
	 * @param  string $Description  描述,默认不填写
	 * @param  string $MusicURL     链接,默认不填写
	 * @param  string $HQMusicUrl   高质量链接,默认不填写
	 * @param  string $ThumbMediaId 缩略图素材库中的ID
	 * @return string                生成的XML
	 */
	public static function makeMusicXml($xmlData, $Title = false, $Description = false, $MusicURL = false, $HQMusicUrl = false, $ThumbMediaId = false) {
		$respone = '<xml>
        <ToUserName><![CDATA[' . $xmlData['FromUserName'] . ']]></ToUserName>
        <FromUserName><![CDATA[' . $xmlData['ToUserName'] . ']]></FromUserName>
        <CreateTime>' . time() . '</CreateTime>
        <MsgType><![CDATA[music]]></MsgType>
        <Music>';
		if ($Title != false) {
			$respone = $respone . '<Title><![CDATA[' . $Title . ']]></Title>';
		}
		if ($Description != false) {
			$respone = $respone . '<Description><![CDATA[' . $Description . ']]></Description>';
		}
		if ($MusicURL != false) {
			$respone = $respone . '<MusicUrl><![CDATA[' . $MusicURL . ']]></MusicUrl>';
		}
		if ($HQMusicUrl != false) {
			$respone = $respone . '<HQMusicUrl><![CDATA[' . $HQMusicUrl . ']]></HQMusicUrl>';
		}
		if ($ThumbMediaId != false) {
			$respone = $respone . '<ThumbMediaId><![CDATA[' . $ThumbMediaId . ']]></ThumbMediaId>';
		}
		$respone = $respone . '</Music>
        </xml>';
		return $respone;
	}

	/**
	 * 生成图片消息XML
	 * @param  array $xmlData      经过处理的请求中的XML
	 * @param  integer $ArticleCount 数量,最大为8个.
	 * @param  array $News         每个信息体(标题,描述,封面,链接)
	 * @return string               生成的XML
	 */
	public static function makeNewsXml($xmlData, $ArticleCount, $News) {
		$respone = '<xml>
        <ToUserName><![CDATA[' . $xmlData['FromUserName'] . ']]></ToUserName>
        <FromUserName><![CDATA[' . $xmlData['ToUserName'] . ']]></FromUserName>
        <CreateTime>' . time() . '</CreateTime>
        <MsgType><![CDATA[news]]></MsgType>
        <ArticleCount>' . $ArticleCount . '</ArticleCount>
        <Articles>';
		foreach ($News as $one) {
			$respone = $respone . '<item>
            <Title><![CDATA[' . $one['title'] . ']]></Title>
            <Description><![CDATA[' . $one['description'] . ']]></Description>
            <PicUrl><![CDATA[' . $one['picurl'] . ']]></PicUrl>
            <Url><![CDATA[' . $one['url'] . ']]></Url>
            </item>';
		}
		$respone = $respone . '</Articles>
        </xml>';
		return $respone;
	}
	
	public function uploadImage($access_token,$file,$raw=false){
	    $data = [
	        'file'=>'@'.$file,
	    ];
	    $res = $this->http('https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token='.$access_token,$data);
	    if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
	    $data = json_decode($res,true);
	    if ($this->errorCode($data)) {
			return $data['url'];
		} else {
			return false;
		}
	}
	
	// https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1481187827_i0l21
	// $news 数组['thumb_media_id'=>'..',...] 
	public function uploadNews($access_token,$news,$raw=false){
	    $postData = [
	        'articles'=>$news,
	    ];
	    $res = $this->http('https://api.weixin.qq.com/cgi-bin/media/uploadnews?access_token='.$access_token,$postData,true);
	    if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res,true);
		if($this->errorCode($data)){
		    return $data;
		}else{
		    return false;
		}
	}
	
	// 根据标签进行群发 https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1481187827_i0l21
	// $tag 填写数字则为按这个标签ID的人群发送;若填写为openid数组,则按照openid发送;如果是'preview'则为预览发送,每天默认100次;默认全部发送
	// 当 send_ignore_reprint 参数设置为1时，文章被判定为转载时，且原创文允许转载时，将继续进行群发操作。
	// 当 send_ignore_reprint 参数设置为0时，文章被判定为转载时，将停止群发操作。
	public function send($access_token,$msgType,$data,$tag=false,$send_ignore_reprint=0,$raw=false){
	    $postData = [];
	    if($tag=='preview'){
	        $postData['touser']=$tag;
	        $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=';
	    }elseif(is_array($tag)){
	        // 按openid发
	        $postData['touser']=$tag;
	        $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=';
	    }elseif(!is_array($tag)){
	        // 按标签编号发
	        $postData['filter']=[
	            'is_to_all'=>false,
	            'tag_id'=>$tag,
	        ];
	        $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=';
	    }else{
	        // 群发
	        $postData['filter']=[
	            'is_to_all'=>true,
	        ];
	        $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=';
	    }
	    $postData[$msgType]=$data;
	    $postData['msgtype']=$msgType;
	    $postData['send_ignore_reprint']=$send_ignore_reprint;
	    $res = $this->http($url.$access_token,$postData,true);
	    if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res,true);
		if($this->errorCode($data)){
		    return $data;
		}else{
		    return false;
		}
	}
	
	// $article_idx 要删除的文章在图文消息中的位置，第一篇编号为1，该字段不填或填0会删除全部文章
	public function sendDelete($access_token,$msg_id,$article_idx=0,$raw=false){
	    $postData = [
	        'msg_id'=>$msg_id,
	        'article_idx'=>$article_idx,
	    ];
	    $res = $this->http('https://api.weixin.qq.com/cgi-bin/message/mass/delete?access_token='.$access_token,$postData,true);
	    if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res,true);
		if($this->errorCode($data)){
		    return true;
		}else{
		    return false;
		}
	}
	
	// 消息发送后的状态，SEND_SUCCESS表示发送成功，SENDING表示发送中，SEND_FAIL表示发送失败，DELETE表示已删除
	public function getSendStatus($access_token,$msg_id,$raw=false){
	    $postData['msg_id']=$msg_id;
	    $res = $this->http('https://api.weixin.qq.com/cgi-bin/message/mass/get?access_token='.$access_token,$postData,true);
	    if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res,true);
		if($this->errorCode($data)){
		    return $data['msg_status'];
		}else{
		    return false;
		}
	}
	
    // 	0:80w/分钟 1:60w/分钟 2:45w/分钟 3:30w/分钟 4:10w/分钟
	public function setSendSpeed($access_token,$msg_id,$speed){
	    $postData['speed'] = $speed;
	    $res = $this->http('https://api.weixin.qq.com/cgi-bin/message/mass/speed/set?access_token='.$access_token,$postData,true);
	    if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res,true);
		if($this->errorCode($data)){
		    return true;
		}else{
		    return false;
		}
	}
	
	
	/**
	 * 设置模板行业 编号参考:https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1433751277
	 * @param  string  $access_token access_token
	 * @param  string  $industry_id1 行业1编号
	 * @param  string  $industry_id2 行业2编号
	 * @param  boolean $raw          是否返回原始数据
	 * @return Boolean                成功返回true
	 */
	public function templateSet($access_token,$industry_id1,$industry_id2,$raw=false){
	    $data = [
	        'industry_id1'=>$industry_id1,
	        'industry_id2'=>$industry_id2
	    ];
	    $res = $this->http('https://api.weixin.qq.com/cgi-bin/template/api_set_industry?access_token='.$access_token,$data,true);
	    if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res, true);
		if ($this->errorCode($data)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 获取已设置的行业信息
	 * @param  string  $access_token access_token
	 * @param  boolean $raw          是否返回原始数据
	 * @return array           
	 */
	public function templateGetset($access_token,$raw=false){
	    $res = $this->http('https://api.weixin.qq.com/cgi-bin/template/get_industry?access_token='.$access_token);
	    if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res, true);
		if (!$this->errorCode($data)) {
			$this->errorCode($data);
			return false;
		}
		return $data;
	}
	
	/**
	 * 获取模板ID
	 * @param  string  $access_token      access_token
	 * @param  string  $template_id_short 模板库中模板的编号
	 * @param  boolean $raw               是否返回原始数据
	 * @return string                     模板ID
	 */
	public function templateGetId($access_token,$template_id_short,$raw=false){
	    $data = [
	        'template_id_short'=>$template_id_short,
	    ];
	    $res = $this->http('https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token='.$access_token,$data,true);
	    if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res, true);
		if (!$this->errorCode($data)) {
			return false;
		}
		return $data['template_id'];
	}
	
	/**
	 * 获取已添加的模板列表
	 * @param  string  $access_token access_token
	 * @param  boolean $raw          是否返回原始数据
	 * @return array
	 */
	public function templateGetList($access_token,$raw=false){
	    $res = $this->http('https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token='.$access_token);
	    if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res, true);
		if (!$this->errorCode($data)) {
			return false;
		}
		return $data;
	}
	
	/**
	 * 删除已添加的模板
	 * @param  string $access_token access_token
	 * @param  string $template_id  模板ID
	 * @return Boolean               成功返回true
	 */
	public function templateDelete($access_token,$template_id){
	    $data = [
	        'template_id'=>$template_id,
	    ];
	    $res = $this->http('https://api.weixin.qq.com/cgi-bin/template/del_private_template?access_token='.$access_token,$data,true);
	    if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res, true);
		if ($this->errorCode($data)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 发送模板 (URL和小程序都填写优先打开小程序)
	 * @param  string  $access_token access_token
	 * @param  string  $openid       用户的OpenID
	 * @param  string  $template_id  模板编号
	 * @param  array  $data         发送数据的数组  ['first'=>['value'=>'名称','color'=>'#173177']]
	 * @param  boolean $url          点击跳转的URL,默认不填写
	 * @param  array $miniprogram  点击跳转的小程序,默认不填写 ['appid'=>'appid','pagepath'=>"index?foo=bar"]
	 * @param  boolean $raw          是否返回原始数据
	 * @return boolean                发送成功返回true
	 */
	public function templateSend($access_token,$openid,$template_id,$data,$url=false,$miniprogram=false,$raw=false){
	    $postData = [
	        'touser'=> $openid,
	        'template_id'=>$template_id
	    ];
	    if($url!=false){
	        $postData['url']=$url;
	    }
	    if($miniprogram!=false){
	        $postData['miniprogram']=$miniprogram;
	    }
	    $postData['data']=$data;
	    $res = $this->http('https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token,$postData,true);
	    if (!$res) {
			$this->errorMsg = 'CURL超时';
			return false;
		}
		if ($raw == true) {
			return $res;
		}
		$data = json_decode($res, true);
		if ($this->errorCode($data)) {
			return true;
		} else {
			return false;
		}
	}

}