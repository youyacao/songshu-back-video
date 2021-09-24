<?php


namespace app\common\behavior;

use think\Exception;
use think\Response;

/**
 * 解决跨域问题
 * Class CronRun
 * @package app\common\behavior
 */
class CronRun
{
    public function run(&$dispatch){
        $host_name = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "*";
        $headers = [
            "Access-Control-Allow-Origin" => $host_name,
            "Access-Control-Allow-Credentials" => 'true',
            "Access-Control-Allow-Headers" => "X-Token,x-uid,x-token-check,x-requested-with,content-type,Host"
        ];
        if($dispatch instanceof Response) {
            $dispatch->header($headers);
        } else if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $dispatch['type'] = 'response';
            $response = new Response('', 200, $headers);
            $dispatch['response'] = $response;
        }
    }
}



	/**
	 * All rights Reserved, Designed By www.youyacao.com <br>
	 * @Description:解决跨域 <br>
	 * @version V4.2  <br>
	 * @author:成都市一颗优雅草科技有限公司  <br>
	 * 注意：使用我司开源源代码请遵循license文件的协议仅供个人非盈利使用，禁止用于其他的商业用途
	 * 需要商业用途或者定制开发等可访问songshu.youyacao.com  联系QQ:2853810243 422108995 23625059584
	 * 正版系统查询系统 zhengban.youyacao.com
	 */
	
	
	
	    
	
