<?php

use think\Request;

/**
 * 助手函数定义
 * Created by PhpStorm.
 * User: bigniu
 * Date: 2019-06-17
 * Time: 13:07:10
 */
function title()
{
    return ['confs' => ["title" => "自助建站系统"]];
}

/**
 * Notes: 获取IP地址<br>
 * User:bigniu <br>
 * Date:2019-06-17 <br>
 * Time:13:52:32 <br>
 */
function getIp()
{
    $request = Request::instance();
    return $request->ip();
}

/**
 * Notes: 成功输出<br>
 * User:bigniu <br>
 * Date:2019-06-17 <br>
 * Time:17:03:50 <br>
 * @param null $msg
 * @param null $data
 * @return \think\response\Json <br>
 */
function success($msg = null, $data = null)
{
    $return = array("data" => $data, "msg" => $msg, "code" => 0);

    if ($data == null) {
        $return['data'] = "";
    }
    if ($msg == null) {
        $return['msg'] = "success";
    }
    return json($return, 200);
}

/**
 * Notes: 加密用户登录密码<br>
 * User:bigniu <br>
 * Date:2019-06-27 <br>
 * Time:16:11:40 <br>
 */
function pass($pass = "")
{
    return md5(md5($pass) . "bigniu");
}

/**
 * Notes: 加密管理员登录密码<br>
 * User:bigniu <br>
 * Date:2019-06-27 <br>
 * Time:16:11:40 <br>
 */
function adminpass($pass = "")
{
    return md5(md5($pass) . "bigniuadmin");
}

/**
 * Notes: 错误输出<br>
 * User:bigniu <br>
 * Date:2019-06-17 <br>
 * Time:17:04:04 <br>
 * @param null $msg
 * @param int $code
 * @return \think\response\Json <br>
 */
function error($msg = null, $code = 1)
{
    $return = array("msg" => $msg, "code" => $code);

    if ($msg == null) {
        $return['msg'] = "error";
    }
    return json($return, 200);
}

/**
 * Notes: 配置文件读取/写入<br>
 * User:bigniu <br>
 * Date:2019-06-17 <br>
 * Time:17:04:14 <br>
 * @param $key
 * @param null $value
 * @return mixed|null <br>
 * @throws \think\Exception
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 * @throws \think\exception\PDOException
 */
function config($key, $value = null)
{
    if ($key) {
        $config = db("config")->where(array("name" => $key))->find();
    } else {
        $config = null;
    }
    if (!$config) {
        return null;
    }
    if ($value == null) {
        return htmlspecialchars($config['value']);

    } else {
        db("config")->where(array("name" => $key))->update(array("value" => $value));
        return $value;
    }
    return null;
}

function isActiveUrl($url)
{
    if (gettype($url) == "array") {
        foreach ($url as $key => $value) {
            $request = Request::instance();
            $request_url = $request->url();
            $value = str_replace("/", "\/", $value);
            $value = str_replace(".html", "\.html", $value);
            //echo"/(^".$url.")/s"."|".$request_url;
            preg_match("/(^" . $value . ")/s", $request_url, $matches);
            if (sizeof($matches) > 0) {
                return "active";
            }
        }
    } else {
        $request = Request::instance();
        $request_url = $request->url();
        $url = str_replace("/", "\/", $url);
        $url = str_replace(".html", "\.html", $url);
        //echo"/(^".$url.")/s"."|".$request_url;
        preg_match("/(^" . $url . ")/s", $request_url, $matches);
        if (sizeof($matches) > 0) {
            return "active";
        } else {
            return "";
        }
    }

    return "";
}

function getRandStr($len = 15)
{
    $authnum = "";
    $ychar = "1,2,3,4,5,6,7,8,9,a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z";
    $list = explode(",", $ychar);
    for ($i = 0; $i < $len; $i++) {
        $randnum = rand(0, 34); // 10+26;
        $authnum .= $list[$randnum];
    }
    return $authnum;
}

function getOs()
{
    if (strstr(PHP_OS, 'WIN')) {
        return "win";
    } else {
        return "linux";
    }
}

function getRuntime()
{
    if (strstr(PHP_SAPI, "apache")) {
        return "apache";
    } else if (strstr(PHP_SAPI, "fcgi")) {
        return "nginx";
    } else {
        return "php";
    }
}

/**
 * Notes: 获取本地IP地址<br>
 * User:bigniu <br>
 * Date:2019-06-28 <br>
 * Time:15:27:13 <br>
 * @return string <br>
 */
function getLocalIp()
{
    return gethostbyname(null);
}

function getPHPVersion()
{
    return str_replace(".", "", substr(PHP_VERSION, 0, 3));
}

/**
 * Notes: 删除指定文件夹以及文件夹下的所有文件<br>
 * User:bigniu <br>
 * Date:2019-06-29 <br>
 * Time:13:11:27 <br>
 * @param $dir
 * @return bool <br>
 */
function delDir($dir)
{
    //先删除目录下的文件：
    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if ($file != "." && $file != "..") {
            $fullpath = $dir . "/" . $file;
            if (!is_dir($fullpath)) {
                unlink($fullpath);
            } else {
                deldir($fullpath);
            }
        }
    }
    closedir($dh);
    //删除当前文件夹：
    if (rmdir($dir)) {
        return true;
    } else {
        return false;
    }
}