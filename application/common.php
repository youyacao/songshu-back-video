<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

if (!function_exists('paySign')) {
    function paySign($params, $appSecret)
    {
        unset($params['uid']);
        unset($params['shop_id']);
        unset($params['ip']);
        unset($params['token']);
        ksort($params);
        $str = '';
        foreach($params as $key => $val){
            $str .= $key.'='.$val.'&';
        }
        $str .= 'appSecret='.$appSecret;
        return strtoupper(md5($str));
    }
}


if (!function_exists('getPayConfig')) {
    function getPayConfig()
    {
        return Db("config")->whereIn('name',['pay_memberid','pay_key','pay_submit_url', 'gold_rate', 'pay_pet_key', 'pay_pet_user_id'])->column('value', 'name');
    }
}

if (!function_exists('curl')) {
    /**
     * @action curl获取数据
     * @param string
     * @return array
     */
    function curl($url, $post = '',$headers = array())
    {
        $headerArr = array();
        foreach( $headers as $n => $v ) {
            $headerArr[] = $n .':' . $v;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($post)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
