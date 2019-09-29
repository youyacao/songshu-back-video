<?php


namespace app\api\controller;


use think\Request;

class Video
{
    public function getData($request){
        return success("",["name"=>"test","url"=>"http://www.baidu.com"]);
    }
    public function postData(Request $request){
        $title = input("title");
        $user = session("user");
        $video = input("video");

        return success("",["name"=>"test1","url"=>"http://www.baidu.com"]);
    }
}