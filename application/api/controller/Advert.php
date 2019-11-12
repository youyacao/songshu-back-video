<?php


namespace app\api\controller;


class Advert
{
    //获取广告信息
    public function getData(){
        $type = input("type",1);//广告类型，1:启动图和首页四屏广告，2:视频广告,3:弹窗霸屏广告,4:首页四屏广告
        $device_id  = input("device_id");//设备ID

        $where =[
            "state"=>1,
            "end_time"=>[">",TIME]
        ];
        switch ($type){
            case 1:
                //获取所有图片广告，根据浏览次数排序，显示浏览次数最低的
                $advert1 = Db("advert")->where(['type'=>1])->where($where)->order("view_count")
                    ->field(['id','name','type','img','open_type','ad_url','title'])->find();
                if(!$advert1){
                    return error("获取失败");
                }

                Db("advert")->where(['id'=>$advert1['id']])->inc("view_count")->update();
                $advert2 = Db("advert")->where(['type'=>4])->where($where)->order("view_count")->limit(4)
                    ->field(['id','name','type','img','open_type','ad_url','title'])->select();
                Db("advert")->where(['type'=>4])->where($where)->order("view_count")->limit(4)->inc("view_count")->update();
                $data =[
                    'home'=>$advert1,
                    "page"=>$advert2
                ];
                return success("获取成功",$data);
                break;
            case 2:
                break;
        }
    }
}