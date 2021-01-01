<?php


namespace app\api\controller;

use think\Controller;

class Config extends Controller
{

    public function index(){
        $key = request()->param('key', 'cy_');
        $list = Db('config')->where('name', 'like', "$key%")->column('value','name');
        return success("获取配置成功",$list);
    }
}