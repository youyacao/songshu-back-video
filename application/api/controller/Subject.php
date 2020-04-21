<?php


namespace app\api\controller;


use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\response\Json;

class Subject
{
    /**
     * Notes:获取列表
     * User: BigNiu
     * Date: 2019/10/31
     * Time: 14:22
     * @return Json
     * @throws Exception
     */
    public function getList(){
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $vid = intval(input('vid'));
        $video = Db("video")->where(['id' => $vid])->find();
        if ($video['is_subject'] == '0') {
            return error("没有开启答题");
        }

        $where = [];
        $where['status'] = 1;
        if($vid){
            $where['vid'] = $vid;
        }
        $list = Db("video_subject")
            ->where($where)
            ->order("seconds asc")
            ->select();
        foreach($list as &$val) {
            $val['options'] = json_decode($val['options'], true);
        }
        $total = Db("video_subject")
            ->where($where)
            ->count();
        return success("获取成功", $list, 0, $total);
    }

    public function getResult(){
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $pageSize = input("pageSize/i", 10) <= 10 ? 10 : input("pageSize/i", 10);
        $where = [];
        $where['vsr.user_id'] = 1;
        $list = Db("video_subject_result vsr")
            ->join("video v", "vsr.vid=v.id", "left")
            ->where($where)
            ->page($page, $pageSize)
            ->order("vsr.id desc")
            ->field(['vsr.*','v.title','v.true_answer'])
            ->select();
        foreach($list as &$val) {
            $val['results'] = json_decode($val['results'], true);
        }
        $total = Db("video_subject_result")
            ->join("video v", "vsr.vid=v.id", "left")
            ->where($where)
            ->count();
        return success("获取成功", $list, $page, $total);
    }

    /**
     * @results array(
     *     ('subject_id' => 1, result=> 0),
     *     ('subject_id' => 2, result=> 1),
     * )
     */
    public function postAnswer(){
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        
        $vid = intval(input('vid'));
        $results = input('results/a');
        $video = Db("video")->where(['id' => $vid])->find();
        if (empty($video)) {
            return error("视频不存在");
        }
        if ($video['is_subject'] == '0') {
            return error("没有开启答题");
        }
        $where = [];
        $where['status'] = 1;
        if($vid){
            $where['vid'] = $vid;
        }
        $list = Db("video_subject")
            ->where($where)
            ->order("seconds asc")
            ->select();
      
        $total = Db("video_subject")
            ->where($where)
            ->count();
        $all_gold = 0;
        $true_num = 0;
        $data = [];
        // 启动事务
        Db::startTrans();
        foreach($results as $key => $result) {
            $subject = $list[$key];
            $true_answer = $subject['true_answer'];
            if ($result !== '' && $result == $true_answer) {
                $true_num++;
                $gold = intval($subject['gold']);
                $all_gold += $gold;
            }   
        }
        $data['vid'] = $vid;
        $data['uid'] = $user['id'];
      
        $data['results'] = json_encode($results);
        $data['true_num'] = $true_num;
        $data['error_num'] = $total - $true_num;
        $data['all_gold'] = $all_gold;
        $data['update_time'] = time();
        $data['create_time'] = time();
  		  
        $res_id = Db("video_subject_result")->insertGetId($data);
        if (!$res_id) {
            Db::rollback();
            return error("答题提交失败");
        }

        $allMoney = $user['money'] + $all_gold;
        if (!Db('user')->where('id', $user['id'])->update(['money' => $allMoney])) {
            Db::rollback();
            return;
        }
        //添加账变记灵
        $data = array();
        $data['user_id'] = $user['id'];
        $data['num'] = $all_gold;
        $data['before_money'] = $user['money'];
        $data['after_money'] = $allMoney;
        $data['info'] = '金币提现';
        $data['data_id'] = $res_id;
        $data['data_type'] = 'subject';
        $data['created_at'] = date('Y-m-d H:i:s');
        $res = Db('account_change')->insert($data);
        if (!$res) {
            Db::rollback();
            return error("答题提交失败");
        }

        // 提交事务
        Db::commit();
        return success("答题提交成功");
    }
}