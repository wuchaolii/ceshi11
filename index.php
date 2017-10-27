<?php
/*
    方倍工作室
    http://www.fangbei.org/
    CopyRight 2011-2017 All Rights Reserved
*/

header('Content-type:text');
define("TOKEN", "weixin");

$wechatObj = new wechatCallbackapiTest();
if (!isset($_GET['echostr'])) {
	$wechatObj->responseMsg();
}else{
    $wechatObj->valid();
}

class wechatCallbackapiTest
{
    public function creat_menu(){
         /*生成自定义菜单开始*/
                $xjson = '{ 
                 "button":[
                     {
                           "name":"课程相关",
                           "sub_button":[
                                {
                                   "type":"view",
                                   "name":"选cc课",
                                   "url":"www.scuec.edu.cn"
                                },
                                {
                                   "type":"view",
                                   "name":"成绩查询",
                                   "url":"http://www.baidu.com"
                                }
                            ]
                       },
                       {
                           "name":"生活相关",
                           "sub_button":[
                                {
                                   "type":"view",
                                   "name":"宿舍报修",
                                   "key":"http://www.baidu.com"
                                },
                                {
                                   "type":"click",
                                   "name":"选宿舍",
                                   "key":"V1001_PAIQIU"
                                }
                            ]
                       },
                       {
                           "name":"新闻",
                           "sub_button":[
                                {
                                   "type":"click",
                                   "name":"国内新闻",
                                   "key":"V1001_GNNEWS"
                                },
                                {
                                   "type":"click",
                                   "name":"国际新闻",
                                   "key":"V1001_GJNEWS"
                                },

                                {
                                   "type":"click",
                                   "name":"地方新闻",
                                   "key":"V1001_AREANEWS"
                                },
                                {
                                   "type":"click",
                                   "name":"家庭新闻",
                                   "key":"V1001_HOMENEWS"
                                }
                            ]
                       }
                   ]
                }';
                $jsonMenu = json_encode($xjson);
                $get_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wxe8ff2ae435357f30&secret=55b61b7105939d08ad84ac4b4bbd0821';
                $get_return = file_get_contents($get_url);
                $get_return = (array)json_decode($get_return);
                if( !isset($get_return['access_token']) ){exit( '获取access_token失败！' );}
                $post_url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$get_return['access_token'];
                $ch = curl_init($post_url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS,$xjson);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($xjson))
                );
                $respose_data = curl_exec($ch);
                echo $respose_data;exit;
            /*生成自定义菜单结束*/
    }
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
         echo $echoStr;
            
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if($tmpStr == $signature){
            return true;
        }else{
            return false;
        }
    }

    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $this->logger("R ".$postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);

            switch ($RX_TYPE)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
            }
            $this->logger("T ".$result);
            echo $result;
        }else {
            echo "";
            exit;
        }
    }
    
    private function receiveEvent($object)
    {
        $content = "";
        switch ($object->Event)
        {
            case "subscribe":
                $content = "欢迎关注电信院";
                break;
            case "unsubscribe":
                $content = "取消关注";
                break;
        }
        $result = $this->transmitText($object, $content);
        return $result;
    }
    
    //接收文本消息
    private function receiveText($object)
    {
        $keyword = trim($object->Content);
        $content = date("Y-m-d H:i:s",time())."\n欢迎关注电信院资讯vvvvvvvv";
        
        if(is_array($content)){
            if (isset($content[0]['PicUrl'])){
                $result = $this->transmitNews($object, $content);
            }else if (isset($content['MusicUrl'])){
                $result = $this->transmitMusic($object, $content);
            }
        }else{
            $result = $this->transmitText($object, $content);
        }

        return $result;
    }

    
    private function transmitText($object, $content)
    {
        $textTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

    private function transmitNews($object, $arr_item)
    {
        if(!is_array($arr_item))
            return;

        $itemTpl = "    <item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
    </item>
";
        $item_str = "";
        foreach ($arr_item as $item)
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);

        $newsTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<Content><![CDATA[]]></Content>
<ArticleCount>%s</ArticleCount>
<Articles>
$item_str</Articles>
</xml>";

        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($arr_item));
        return $result;
    }

    private function transmitMusic($object, $musicArray)
    {
        $itemTpl = "<Music>
    <Title><![CDATA[%s]]></Title>
    <Description><![CDATA[%s]]></Description>
    <MusicUrl><![CDATA[%s]]></MusicUrl>
    <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
</Music>";

        $item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);

        $textTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[music]]></MsgType>
$item_str
</xml>";

        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }
    
    private function logger($log_content)
    {
        if(isset($_SERVER['HTTP_APPNAME'])){   //SAE
            sae_set_display_errors(false);
            sae_debug($log_content);
            sae_set_display_errors(true);
        }else if($_SERVER['REMOTE_ADDR'] != "127.0.0.1"){ //LOCAL
            $max_size = 10000;
            $log_filename = "log.xml";
            if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
            file_put_contents($log_filename, date('H:i:s')." ".$log_content."\r\n", FILE_APPEND);
        }
    }
}


?>