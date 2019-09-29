<?php


namespace app\api\controller;


class Video
{
    public function getData(){
        return success("",["name"=>"test","url"=>"http://www.baidu.com"]);
    }
    public function postData(){
        return success("",["name"=>"test1","url"=>"http://www.baidu.com"]);
    }
}