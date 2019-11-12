<?php


namespace app\api\common;
/**
 * 创建异步执行CMD任务
 * @package app\common\model;
 * @author 晓风<215628355@qq.com>
 */
class AsyncCommand {

    /**
     * 异步执行think命令行
     * @param string $command think命令行名称
     * @param array  $argument 参数 如[1,2,3]
     * @return string
     */
    public static function think($command,$argument = []){
        $cmd = "php " . ROOT_PATH . 'think ' . $command;
        if($argument){
            $cmd  .= " " . implode(" ",$argument);
        }
        return self::run($cmd);
    }

    /**
     * 创建一个新的异步CLI进程,支持WINDOWS 和 LINUX系统
     * PHP 需可全局执行，并启用popen
     * @param string $cmd 要异步执行的命令行
     * @return string
     */
    public static function run($cmd){
        $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        if ($isWin) {
            $cmd = "start /b " . $cmd ; //windos系统中start /b 是异步执行
        } else {
            $cmd = $cmd . " > /dev/null &"; //linux系统中&符号 是异步执行
        }
        $id = popen($cmd, 'r');
        pclose($id);
        return $cmd;
    }
}