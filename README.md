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

## 进度
 * [√]XML转成数组(xmlToArray)
 * [√]获取原始数据(getRawData)
 * [√]获取最后一条错误信息(getError)
 * [√]验证开启服务器(openServer)
 * [√]获取access_token(getAccessToken)
 * [√]获取微信服务器(getWechatServer)
 * [√]自定义菜单创建(menuCreate)(参考示例Demo) [JSON结构说明](https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141013)
 * [√]自定义菜单查询(menuQuery)
 * [√]自定义菜单删除(menuDelete)
 * [√]自定义菜单推送(参考示例Demo) [XML结构说明](https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141013)
 * [√]个性化菜单创建(menuCreateCharacter)
 * [√]个性化菜单删除(menuDeleteCharacter)
 * [√]个性化菜单测试(menuTestCharacter)
 * [√]获取菜单配置(通用)(menuQueryFull)
 * [√]接收消息类型说明(参考示例Demo) [XML结构说明](https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140453)
 * [√]接收事件类型说明(参考示例Demo) [XML结构说明](https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140454)
 * [√]回复消息结构说明(参考示例Demo) [XML结构说明](https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140543)
 *  * [√]生成普通消息XML(makeTextXml)
 *  * [√]生成图片消息XML(makeImageXml)
 *  * [√]生成语音消息XML(makeVoiceXml)
 *  * [√]生成视频消息XML(makeVideoXml)
 *  * [√]生成音乐消息XML(makeMusicXml)
 *  * [√]生成图文混合XML(makeNewsXml)
 * 客服消息
 *  * 添加客服
 *  * 修改客服
 *  * 删除客服
 *  * 设置客服头像
 *  * 获取所有客服列表
 *  * 发送客服消息
 *  * 发送输入状态
 * 群发接口
 *  * 上传图文消息内的图片获取URL
 *  * 上传图文消息素材
 *  * 根据标签进群发
 *  * 根据OpenID列表群发
 *  * 删除群发
 *  * 预览接口
 *  * 查询群发消息发送状态
 *  * 时间推送群发结果
 *  * 检测重复推送
 *  * 控制群发速度
 * 
 * .....