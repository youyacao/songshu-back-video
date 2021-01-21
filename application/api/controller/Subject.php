
<!-- 
All rights Reserved, Designed By www.youyacao.com 
@Description:视频关联问题主题方法文件
@author:成都市一颗优雅草科技有限公司     
@version 松鼠短视频系统-后端部分
注意：后端代码在获得授权之前通过其他非官方渠道获得代码均为侵权，禁止用于商业用途，否则将承担因此带来等版权纠纷。
需要商业用途或者定制开发等可访问songshu.youyacao.com   联系QQ:422108995 23625059584

 -->

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
        $map = [];
        $map['uid'] = $user['id'];
        $map['vid'] = $vid;
        $exits = Db("video_subject_result")->where($map)->count();
        if ($exits) {
            return error("已经回答");
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
        $data = [
            'list' => $list,
            'page' => 0,
            'total'=> $total
        ];
        return success("获取成功", $data);
    }

    public function getResult(){
        $user = session("user");
        if (!$user) {
            return error("未登录");
        }
        $page = input("page/i", 1) <= 1 ? 1 : input("page/i", 1);
        $pageSize = input("pageSize/i", 10) <= 10 ? 10 : input("pageSize/i", 10);
        $where = [];
        $where['vsr.uid'] = $user['id'];
        $list = Db("video_subject_result vsr")
            ->join("video v", "vsr.vid=v.id", "left")
            ->where($where)
            ->page($page, $pageSize)
            ->order("vsr.id desc")
            ->field(['vsr.*','v.title'])
            ->select();
        foreach($list as &$val) {
            $val['results'] = json_decode($val['results'], true);
            $val['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
        }
        $total = Db("video_subject_result vsr")
            ->join("video v", "vsr.vid=v.id", "left")
            ->where($where)
            ->count();
        $data = [
            'list' => $list,
            'page' => $page,
            'total'=> $total,
            'page_count' => ceil($total/$pageSize)
        ];
        return success("获取成功", $data);
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
            return error("尚未登录，请登录后进行答题操作");
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
		if ($all_gold) {
            $allMoney = $user['money'] + $all_gold;
            if (!Db('user')->where('id', $user['id'])->update(['money' => $allMoney])) {
                Db::rollback();
                 return error("更新金币错误");
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
        }
        // 提交事务
        Db::commit();
        return success("提交成功，共答对 {$true_num} 题，得到 {$all_gold} 金币");
    }
}