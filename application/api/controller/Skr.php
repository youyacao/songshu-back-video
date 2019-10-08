<?php


namespace app\api\controller;


use think\Controller;

class Skr extends Controller
{
    /**
     * Notes:点赞操作
     * User: BigNiu
     * Date: 2019/10/8
     * Time: 17:57
     */
    public function getLike(){
        $type = input("type/i");//0为未点赞，1为已点赞
    }
}