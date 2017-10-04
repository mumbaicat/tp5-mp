<?php
namespace Wechat;
class Wechat{
    
    private $config = [];
    
    private $errorMsg = 'Nothing';
    
    public function __construct(){
        $this->config['appid']=config('wechat.appid');
        $this->config['appsecret']=config('wechat.appsecret');
    }
    
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
    
    protected function decrypt($data){
        return $data;
    }
    
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
    
    public function getError(){
        return $this->errorMsg;
    }
    
    public static function openServer(){
        echo $_GET['echostr'];
        return $_GET['echostr'];
    }
    
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