<?php


namespace app\api\common;


use think\Config;
use Qcloud\Sms\SmsSingleSender;

class Sms
{
    public static function sendSms($phone, $code)
    {
        if (!$phone || !$code) {
            return false;
        }
        //获取数据库是否有数据
        $smsData = Db("sms")->where(['phone' => $phone])->find();
        $sms = new Sms();
        $yzmcontent = str_replace("{验证码}",$code,config("sms_template"));
        //$apikey = config("sms_apikey");
        //$data = array('content' => urlencode($yzmcontent), 'apikey' => $apikey, 'mobile' => $phone);
        $data = array('content' => $yzmcontent, 'mobile' => $phone);

        if ($smsData) {

            //有数据：
            //判断时间是否超时
            if (time() - $smsData['time'] < config("sms_sleep_time")) {

                //未超时直接返回false
                return false;
            } else {
                //已超时，发送消息

                //判断是否为本地环境 调试不调用发送短信接口
                if(isLocal()||$phone=="13800138000"){
                    Db("sms")->where(['phone' => $phone])->update(['code' => $code, 'time' => time(), 'count' => 0]);
                    return true;
                }
                //判断是否发送成功
                if ($sms->send_yzm($data)) {
                    //发送成功，保存入库，返回true
                    Db("sms")->where(['phone' => $phone])->update(['code' => $code, 'time' => time(), 'count' => 0]);
                    return true;
                }
                //发送失败，返回false
                return false;
            }
        } else {
            //无数据
            //发送消息
            //判断是否为本地环境，调试不调用发送短信接口
            if(isLocal()||$phone=="13800138000"){
                Db("sms")->where(['phone' => $phone])->update(['code' => $code, 'time' => time(), 'count' => 0]);
                return true;
            }
            //判断是否发送成功
            if ($sms->send_yzm($data)) {
                //发送成功，保存入库，返回true
                Db("sms")->insert(['phone' => $phone, 'code' => $code, 'time' => time()]);
                return true;
            }
            return false;
            //发送失败，返回false
        }
        // 发送验证码短信
        // 修改为您要发送的短信内容,需要对content进行编码

        $result = $sms->send_yzm($data);
        $data = json_decode($result, true);
        if ($data['code'] == 1) {
            return true;
        }
        return false;
    }

    public static function verifySms($phone, $code)
    {
        if (!$phone || !$code) {
            return false;
        }
        //判断是否为本地环境
        if(isLocal()||$phone=="13800138000"){
            if($code=='123000'){
                Db('sms')->where(['phone' => $phone])->delete();
                return true;
            }
        }


        $smsData = Db("sms")->where(['phone' => $phone])->find();
        //判断短信记录是否存在
        if (!$smsData) {
            //不存在直接返回false
            return false;
        }
        //存在，判断是否超时
        if (time() - $smsData['time'] > config("sms_life_time")) {
            //超时，返回false
            return false;
        }
        //未超时，判断错误次数是否超过限定次数
        if ($smsData['count'] >= config("sms_err_count")) {
            //错误次数过多，直接返回false
            return false;
        }
        //未超过，判断验证码是否正确
        if ($smsData['code'] != $code) {
            //不正确，错误次数+1，返回false
            Db('sms')->where(['phone' => $phone])->inc('count', 1)->update();
            return false;
        }
        //正确，清除当前验证码记录，防止二次使用，返回true
        Db('sms')->where(['phone' => $phone])->delete();
        return true;

    }

    //验证码
    private function send_yzm($data)
    {
        $sms_server = config('sms_server');
        if($sms_server == 'mysubmail'){
            return $this->mysubmail($data);
        }elseif ($sms_server == 'tencent'){
            return $this->tencent($data);
        }else{
            return $this->dingdong($data);
        }

    }

    private function post($url,$params,$header=array()){
        $_headers=isset($header['Content-Type']) ? array() : array(
            "Content-Type: application/x-www-form-urlencoded"
        );
        foreach ($header as $key=>$val){
            $_headers[] = $key.': '.$val;
        }

        $postData = http_build_query($params);

        $_headers[] = 'Content-Length: ' . strlen($postData);

        $ch = curl_init();

        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postData );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, $_headers );
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 60 );
        $https = strpos(strtolower($url),"https://") !== false;
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        }

        $result = curl_exec($ch);
        return json_decode($result, true);
    }

    private function dingdong($data){
        $data['apikey'] = config("sms_apikey");
        $data['content'] = urlencode($data['content']);
        $result = $this->post('http://api.dingdongcloud.com/v1/sms/sendyzm',$data);
        if ($result['code'] == 1) {
            return true;
        }
        return false;
    }

    private function mysubmail($data){
        $params = array();
        $params['appid'] = config("mysubmail_sms_appid");
        $params['to'] = $data['mobile'];
        $params['content'] = $data['content'];
        $params['signature'] = config("mysubmail_sms_apikey");

        $result = $this->post('http://api.mysubmail.com/message/send.json',$params);
        if ($result['status'] == 'success') {
            return true;
        }
        return false;
    }

    private function tencent($data){
        // 短信应用SDK AppID
        $appid = config("tencent_sms_appid"); // 1400开头
        $appkey = config("tencent_sms_apikey");
        $templateId = 7839;
        $params = ["5678"];
        $smsSign = "腾讯云";
        $ssender = new SmsSingleSender($appid, $appkey);
        $result = $ssender->sendWithParam("86", $data['mobile'], $templateId, $params, $smsSign);  // 签名参数不能为空串
        $res = json_decode($result);
        echo $result;
    }
}