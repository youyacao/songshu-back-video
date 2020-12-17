<?php
/**
 * 直播服务
 * @date    2020-01-01
 * @author  kiro
 * @email   294843009@qq.com
 * @version 1.0
 */
namespace app\api\common;

use Exception;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Live\V20180801\LiveClient;
use TencentCloud\Live\V20180801\Models\CreateLiveTranscodeRuleRequest;
use TencentCloud\Live\V20180801\Models\DeleteLiveTranscodeRuleRequest;
use TencentCloud\Live\V20180801\Models\DescribeLiveStreamOnlineListRequest;
use TencentCloud\Live\V20180801\Models\DescribeLiveStreamStateRequest;
use TencentCloud\Live\V20180801\Models\DropLiveStreamRequest;
use TencentCloud\Live\V20180801\Models\ResumeLiveStreamRequest;

class LiveService
{
    private $domain = 'txliveplay.youyacao.com';
    private $push_domain = 'txlivepush.youyacao.com';
    private $push_key = '99d2d356d0a86b65a65240228a518f27';
    // 秘钥--腾讯云API管理生成的
    private $secretId = 'AKIDgBSdh58dCctyfQqAIdkaGjxx5h49xqtl';
    private $secretKey = 'jEDn694fz6wc4PWWGck27yiDLn16Wg8d';

    private $AppName = '蜻蜓-uni';

    private $hd = false;

    public function __construct($hd){
        $this->hd = $hd;
    }

    /**
     *  获取直播中的流
     */
    public function getLiveStreamOnlineList($page = 1, $pageSize = 10, $streamName = '')
    {
        try {
            $cred = new Credential($this->secretId, $this->secretKey);
            $client = new LiveClient($cred, "");
            $req = new DescribeLiveStreamOnlineListRequest();
            $req->setAppName($this->AppName);
            $req->setDomainName($this->push_domain);
            $req->setPageNum($page);
            $req->setPageSize($pageSize);
            $streamName AND $req->setStreamName($streamName);
            $resp = $client->DescribeLiveStreamOnlineList($req);
            return json_decode($resp->toJsonString(), true);
        } catch(TencentCloudSDKException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     *  获取直播流状态
     */
    public function getLiveStreamState($streamName)
    {
        try {
            $cred = new Credential($this->secretId, $this->secretKey);
            $client = new LiveClient($cred, "");
            $req = new DescribeLiveStreamStateRequest();
            $req->setAppName($this->AppName);
            $req->setDomainName($this->push_domain);
            $req->setStreamName($streamName);
            $resp = $client->DescribeLiveStreamState($req);
            $result = json_decode($resp->toJsonString(), true);
            if ($result['StreamState'] == 'active') {
                return true;
            }
            return false;
        } catch(TencentCloudSDKException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     *  断开直播流
     */
    public function dropLiveStream($streamName)
    {
        try {
            $cred = new Credential($this->secretId, $this->secretKey);
            $client = new LiveClient($cred, "");
            $req = new DropLiveStreamRequest();
            $req->setAppName($this->AppName);
            $req->setDomainName($this->push_domain);
            $req->setStreamName($streamName);
            $resp = $client->DropLiveStream($req);
            return json_decode($resp->toJsonString(), true);
        } catch(TencentCloudSDKException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 生成播放地址
     */
    public function getLiveUrl($streamName)
    {
        return [
            'rtmp_url'  => 'rtmp://' . $this->domain . '/live/' . $streamName,
            'flv_url'   => 'http://' . $this->domain . '/live/' . $streamName . '.flv',
            'hls_url'   => 'http://' . $this->domain . '/live/' . $streamName . '.m3u8',
            'udp_url'   => 'webrtc://' . $this->domain . '/live/' . $streamName
        ];
    }

    /**
     * 获取推流地址
     * 如果不传key和过期时间，将返回不含防盗链的url
     * @param domain 您用来推流的域名
     *        streamName 您用来区别不同推流地址的唯一流名称
     *        key 安全密钥
     *        time 过期时间 sample 2016-11-12 12:00:00
     * @return String url
     */
    public function getPushUrl($streamName, $time = '', $key = ''){
        if (empty($key)) {
            $key = $this->push_key;
        }
        if (empty($time)) {
            $time = date('Y-m-d H:i:s', strtotime('+1 day'));
        }
        $txTime = strtoupper(base_convert(strtotime($time),10,16));
        $txSecret = md5($key.$streamName.$txTime);
        $ext_str = "?".http_build_query(array(
                "txSecret"=> $txSecret,
                "txTime"=> $txTime
            ));
        $this->createLiveTranscodeRule($streamName);
        return "rtmp://".$this->push_domain."/" . $this->AppName . "/".$streamName . (isset($ext_str) ? $ext_str : "");
    }

    /**
     *  创建转码规则
     */
    public function createLiveTranscodeRule($streamName)
    {
        try {
            $cred = new Credential($this->secretId, $this->secretKey);
            $client = new LiveClient($cred, "");
            $req = new CreateLiveTranscodeRuleRequest();
            $req->setDomainName($this->push_domain);
            //$req->setAppName($this->AppName);
            $req->setAppName('');
            $req->setStreamName('');
            if ($this->hd) {
                $req->setTemplateId(12816);
            } else {
                $req->setTemplateId(12818);
            }
            $resp = $client->CreateLiveTranscodeRule($req);
            return json_decode($resp->toJsonString(), true);
        } catch(TencentCloudSDKException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     *  恢复直播推流
     */
    public function resumeLiveStream($streamName)
    {
        try {
            $cred = new Credential($this->secretId, $this->secretKey);
            $client = new LiveClient($cred, "");
            $req = new ResumeLiveStreamRequest();
            $req->setAppName($this->AppName);
            $req->setDomainName($this->push_domain);
            $req->setStreamName($streamName);
            $resp = $client->ResumeLiveStream($req);
            return json_decode($resp->toJsonString(), true);
        } catch(TencentCloudSDKException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     *  删除转码规则
     */
    public function deleteLiveTranscodeRule($streamName)
    {
        try {
            $cred = new Credential($this->secretId, $this->secretKey);
            $client = new LiveClient($cred, "");
            $req = new DeleteLiveTranscodeRuleRequest();
            $req->setAppName($this->AppName);
            $req->setDomainName($this->push_domain);
            $req->setStreamName($streamName);
            $resp = $client->DeleteLiveTranscodeRule($req);
            return json_decode($resp->toJsonString(), true);
        } catch(TencentCloudSDKException $e) {
            throw new Exception($e->getMessage());
        }
    }
}
