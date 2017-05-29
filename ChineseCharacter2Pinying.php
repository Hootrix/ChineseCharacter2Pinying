<?php
/**
 * 使用google翻译api 获取汉语拼音
 *
 * 调用：
 * $c = ChineseCharacter2Pinying::getInstance();
 * $c->setMode(ChineseCharacter2Pinying::$RomovePhoneticMode);
 * echo $c->setStr('你好啊')->get();
 */
class ChineseCharacter2Pinying
{
    public static $PhoneticMode = 1;//音标字母 默认模式
    public static $PhoneticNumberMode = 2;//音标为数字
    public static $RomovePhoneticMode = 3;//去音标模式 使用纯英文字母表示拼音
    public static $FirstLetterMode = 4;//整个字符串首字母
//    public static $FirstLetterPhraseMode =  3;//各单字 首字母 【未解决】

    private static $_instance = null;
    private $_mode =  null;
    private $_str = '';


    //带音标字符
    private $_phoneticharactersList = array(
        "ā"=>"a1",
        "á"=>"a2",
        "ǎ"=>"a3",
        "à"=>"a4",
        "ē"=>"e1",
        "é"=>"e2",
        "ě"=>"e3",
        "è"=>"e4",
        "ō"=>"o1",
        "ó"=>"o2",
        "ǒ"=>"o3",
        "ò"=>"o4",
        "ī"=>"i1",
        "í"=>"i2",
        "ǐ"=>"i3",
        "ì"=>"i4",
        "ū"=>"u1",
        "ú"=>"u2",
        "ǔ"=>"u3",
        "ù"=>"u4",
        "ü"=>"v0",
        "ǘ"=>"v2",
        "ǚ"=>"v3",
        "ǜ"=>"v4",
        "ń"=>"n2",
        "ň"=>"n3",
        ""=>"m2"
    );


    private function __construct()
    {
        $this->setMode(self::$PhoneticMode);
    }
    private function __clone(){}

    public static function getInstance(){
        if(! (self::$_instance instanceof self)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * @return string
     */
    public function getStr()
    {
        return $this->_str;
    }

    /**
     * @param string $str
     * @return $this
     */
    public function setStr($str)
    {
        $this->_str = $str;
        return $this;
    }


    public function setMode($mode)
    {
        $this->_mode = $mode;
        return $this;
    }

    public function get(){
        return $this->convert($this->_str);
    }

    private function convert($str){
        if(empty($str)) return '';
        $str = urlencode($str);
        $u = 'https://translate.google.cn/translate_a/single?client=gtx&sl=zh-CN&tl=en&hl=zh-CN&dt=at&dt=bd&dt=ex&dt=ld&dt=md&dt=qca&dt=rw&dt=rm&dt=ss&dt=t&ie=UTF-8&oe=UTF-8&otf=2&ssel=0&tsel=0&kc=5&tk=708660.820543&q=' . $str;
        //百度汉语APP的api不支持自定义汉语句子。 遂记录
        //        $u = 'http://hanyu.baidu.com/hanyu/ajax/sugs?mainkey=%E9%98%BF%E6%88%BF%E5%AE%AB%E8%B5%8B' ;
        //        $u = 'http://app.dict.baidu.com/dictapp/v2/search_allinfo?from=sug&ptype=zici&source=wenzi&mainkey=%E9%98%BF%E6%88%BF%E5%AE%AB%E8%B5%8B&osVersionName=5.0.2&model=Xiaomi_MI+2SC&versionName=2.1.2&deviceid=f8%3Aa4%3A5f%3A56%3A18%3A67%7C99000505541760&versionCode=24&platform=Android' ;
        $c = $this->curl_get($u, array('PC_UA' => true));
        $o = json_decode($c);
        $word = isset($o[0][$o[0][0][4]][3])?$o[0][$o[0][0][4]][3]:$o[0][1][3];
        if(!isset($word)){
            foreach ($o[0] as $item){
                if(isset($item[3]) && !empty($item[3])){
                    $word = $item[3];
                    break;
                }
            }
        }
        if(self::$PhoneticNumberMode === $this->_mode){
            return $this->filterPhonetic($word);
        }elseif (self::$RomovePhoneticMode === $this->_mode){
            return $this->filterPhonetic($word,false);
        }elseif (self::$PhoneticMode === $this->_mode){
            return $word;
        }elseif (self::$FirstLetterMode === $this->_mode){
            $word = mb_substr($word, 0, 1);
            return $word;
        }
        return strtoupper($word);
    }

    /**
     * 过滤音标字幕
     *
     * @param $str
     * @param bool $needPhoneticNumbers
     * @return mixed
     */
    private function filterPhonetic($str,$needPhoneticNumbers = true){
        $phonetic = array_keys($this->_phoneticharactersList);
        $phoneticAfter = array_values($this->_phoneticharactersList);
        if(!$needPhoneticNumbers){
            foreach ($phoneticAfter as &$item){
                $item = mb_substr($item,0,-1);
            }
            unset($item);
        }
        return str_replace($phonetic,$phoneticAfter,$str);
    }

    /**
     * curl 操作方法
     * @param $url
     * @param array $options
     * @return mixed
     */
    private function curl_get($url, $options = array())
    {
        $defaultOptions = array(
            'IPHONE_UA' => true,
            'PC_UA' => false,
            'SSL' => false,
            'TOU' => false,//响应头
            'ADD_HEADER_ARRAY' => false,
            'POST' => false,
            'REFERER' => false,
            'USERAGENT' => false,
            'CURLOPT_FOLLOWLOCATION' => false,
            'NO_BODY' => false,//响应内容
            'TIMEOUT' => false,//最低超时时间 秒
        );
        $options = array_merge($defaultOptions, $options);

        if (strpos($url, 'https://') === 0) {
            $options['SSL'] = true;
        }

        $ch = curl_init($url);
        if ($options['SSL']) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        if ($options['IPHONE_UA']) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_1_2 like Mac OS X; en-us) AppleWebKit/528.18 (KHTML, like Gecko) Version/4.0 Mobile/7D11 Safari/528.16'));
        }
        if ($options['PC_UA']) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.98 Safari/537.36'));
        }
        if (is_array($options['ADD_HEADER_ARRAY'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $options['ADD_HEADER_ARRAY']);
        }
        if ($options['POST']) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['POST']);
        }
        if ($options['REFERER']) {
            curl_setopt($ch, CURLOPT_REFERER, $options['REFERER']);
        }
        if ($options['USERAGENT']) {
            curl_setopt($ch, CURLOPT_USERAGENT, $options['USERAGENT']);
        }
        if ($options['TOU']) {
            curl_setopt($ch, CURLOPT_HEADER, 1); //输出响应头
        }
        if ($options['CURLOPT_FOLLOWLOCATION']) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);//自动跟踪跳转的链接
        }
        if ($options['NO_BODY']) {
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_HEADER, 1); //输出响应头
        }
        if ($options['TIMEOUT']) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $options['TIMEOUT']);   //只需要设置一个秒的数量就可以
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }
}