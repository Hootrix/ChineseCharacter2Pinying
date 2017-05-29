[PHP]汉字转拼音处理
==

使用Google翻译API获取汉字拼音数据

目前国内的google.cn解封，速度还是不错。

使用
--
```php

$c = ChineseCharacter2Pinying::getInstance();
$c->setMode(ChineseCharacter2Pinying::$RomovePhoneticMode);//设置转拼音的几种模式
//    public static $PhoneticMode = 1;//音标字母 默认模式
//    public static $PhoneticNumberMode = 2;//音标为数字
//    public static $RomovePhoneticMode = 3;//去音标模式 使用纯英文字母表示拼音
//    public static $FirstLetterMode = 4;//整个字符串首字母

//输出
echo $c->setStr('你好啊')->get();
```
 


