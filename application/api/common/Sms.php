<?php


namespace app\api\common;


use think\Config;

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
        $apikey = config("sms_apikey");
        $data = array('content' => urlencode($yzmcontent), 'apikey' => $apikey, 'mobile' => $phone);

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
                $result = $sms->send_yzm($data);
                $data = json_decode($result, true);
                //判断是否发送成功
                if ($data['code'] == 1) {
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
            $result = $sms->send_yzm($data);

            $data = json_decode($result, true);
            //判断是否发送成功
            if ($data['code'] == 1) {
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
        $ch = curl_init();

        /* 设置验证方式 */
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8', 'Content-Type:application/x-www-form-urlencoded', 'charset=utf-8'));

        /* 设置返回结果为流 */
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        /* 设置超时时间*/
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        /* 设置通信方式 */
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_URL, 'http://api.dingdongcloud.com/v1/sms/sendyzm');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        return curl_exec($ch);
    }
}