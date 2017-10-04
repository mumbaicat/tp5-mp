<?php
namespace Wechat;
class Wechat{
    
    private $config = [];
    private $errorMsg = 'Nothing';
    
    /**
     * 构造方法,初始化微信公众号配置.
     */
    public function __construct(){
        $this->config['appid']=config('wechat.appid');
        $this->config['appsecret']=config('wechat.appsecret');
    }
    
    /**
     * CURL
     * @param  string  $url      目标地址
     * @param  array $postData POST数据,若填写则为POST方式请求
     * @param  boolean $json     是否以POST形式发送Json数据
     * @param  array   $header   头部信息
     * @return string            返回结果
     */
    protected function http($url,$postData=false,$json=false,$header=[]){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if($postData != false or $json!=false){
            if($json!=false){
                $jsonPostData = json_encode($postData,JSON_UNESCAPED_UNICODE);
                $tempHeader = [
                    'Content-Type: application/json',
                    'Content-Length: '.strlen($jsonPostData)
                ];
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPostData);
            }else{
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            }

        }
        if($header!= [] or $json==true){
            if($json==true){
                $header = array_merge($tempHeader,$header);
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
    protected function decrypt($data){
        return $data;
    }
    
    /**
     * 错误码反馈
     * @param  mixed $res 错误码或微信返回错误提示的Json
     * @return bool      错误码是否错误,错误返回false
     */
    protected function errorCode($res){
        if(!is_array($res)){
            $res = json_decode($res,true);
        }
        switch ($res['errcode']) {
            case '0':
                return true;
                break;
            case '-1':
                $this->errorMsg='[-1]系统繁忙，此时请开发者稍候再试';
                break;
            case '40001':
                $this->errorMsg='[40001]AppSecret错误或者AppSecret不属于这个公众号，请开发者确认AppSecret的正确性';
                break;
            case '40002':
                $this->errorMsg='[40002]请确保grant_type字段值为client_credential';
                break;
            case '40003':
                $this->errorMsg ='[40003]不合法的OpenID，请开发者确认OpenID（该用户）是否已关注公众号，或是否是其他公众号的OpenID';
            case '40164':
                $this->errorMsg='[40164]调用接口的IP地址不在白名单中，请在接口IP白名单中进行设置';
                break;
            case '40013':
                $this->errorMsg='[40013]AppID无效';
                break;
            case '40018':
                $this->errorMsg='[40018]无效菜单名长度';
                break;
            default:
                $this->errorMsg='['.$res['errcode'].']'.$res['errmsg'];
                break;
        }
        return false;
    }
    
    /**
     * 获取上一条错误信息
     * @return string
     */
    public function getError(){
        return $this->errorMsg;
    }
    
    /**
     * 微信配置服务器的验证服务器
     * @return string 
     */
    public static function openServer(){
        echo $_GET['echostr'];
        return $_GET['echostr'];
    }
    
    /**
     * 获取access_token
     * @param  boolean $raw 是否返回原始数据
     * @return array       返回access_token和有效期
     */
    public function getAccessToken($raw=false){
        $res=$this->http('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->config['appid'].'&secret='.$this->config['appsecret']);
        if(!$res){
            $this->errorMsg = 'CURL超时';
            return false;
        }
        if($raw == true){
            return $res;
        }
        $data = json_decode($res,true);
        if(empty($data['access_token'])){
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
    public function getWechatServer($access_token,$raw=false){
        $res = $this->http('https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token='.$access_token);
        if(!$res){
            $this->errorMsg = 'CURL超时';
            return false;
        }
        if($raw==true){
            return $raw;
        }
        $data = json_decode($res,true);
        if(empty($data['ip_list'])){
            $this->errorCode($data);
            return false;
        }else{
            return $data['ip_list'];
        }
        
    }
    
    /**
     * 自定义菜单创建
     * @param  string  $access_token access_token
     * @param  array  $data         结构字段
     * @param  boolean $raw          是否返回原始数据
     * @return boolean                成功返回true
     */
    public function menuCreate($access_token,$data,$raw=false){
        $res = $this->http('https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token,$data,true);
        if(!$res){
            $this->errorMsg = 'CURL超时';
            return false;
        }
        if($raw==true){
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
     * 自定义菜单查询
     * @param  string  $access_token access_token
     * @param  boolean $raw          是否返回原始数据
     * @return array                结构字段
     */
    public function menuQuery($access_token,$raw=false){
        $res = $this->http('https://api.weixin.qq.com/cgi-bin/menu/get?access_token='.$access_token);
        if(!$res){
            $this->errorMsg = 'CURL超时';
            return false;
        }
        if($raw==true){
            return $res;
        }
        $data = json_decode($res,true);
        if(!empty($data['errcode'])){
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
    public function menuDelete($access_token,$raw=false){
        $res = $this->http('https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$access_token);
        if(!$res){
            $this->errorMsg = 'CURL超时';
            return false;
        }
        if($raw==true){
            return $res;
        }
        $data = json_decode($res,true);
        if($this->errorCode($data)){
            return true;
        }else{
            return false;
        }
    }
    
    public function menuPush($access_token,$raw=false){
        // 懒得弄了
    }
    
}