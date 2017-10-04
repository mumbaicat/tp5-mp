Thinkphp5 微信公众号扩展
=======
## 介绍
 * 命名空间版本
 * 简单粗暴的使用方法

## 目录结构
<pre>
wechat // 扩展目录,本项目的主体
application // 示例Demo
</pre>

## 配置  
把wechat目录放在Thinkphp5的extend目录下  
先在application应用目录下的config.php里添加如下代码
<pre>
// 微信公众号
'wechat'=>[
    'appid'=>'your appid',
    'appsecret'=>'your appsecret',
],
</pre>

## 使用方法
