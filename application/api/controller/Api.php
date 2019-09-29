<?php


namespace app\api\controller;


use think\Controller;

class Api extends Controller
{
    public function getVideos(){
        return success("",["name"=>"test","url"=>"http://www.baidu.com"]);
    }
}