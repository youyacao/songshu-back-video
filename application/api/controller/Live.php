<?php
/**
 * 直播
 * @date    2020-01-03
 * @author  kiro
 * @email   294843009@qq.com
 * @version 1.0
 */
namespace app\api\controller;

use app\api\common\LiveService;
use think\Controller;

class Live extends Controller
{
    private $service = null;

    private $hd = false;

    public function __construct()
    {
        $this->hd = request()->get('hd', false);
        $this->service = new LiveService($this->hd);
    }

    // 直播列表
    public function getList()
    {
        $page = (int)input('page', 1);
        $limit = (int)input('limit', 10);
        $streamName = input('streamName', '');
        $online = $this->service->getLiveStreamOnlineList($page, $limit, $streamName);
        $total = $online['TotalNum'];
        $totalPage = $online['TotalPage'];
        $data = $online['OnlineInfo'];
        $user = session("user") ? session("user") : session("guest_user");
        $user_id = $user['id'] ? $user['id']:0;
        foreach ($data as &$value) {
            $streamName = $value['StreamName'];
            $live_urls = $this->service->getLiveUrl($streamName);
            $value['live_urls'] = $live_urls;
            $live = Db('live l')
                ->join('user u', 'l.user_id=u.id', "left")
                ->join("follow f", "l.user_id=f.follow_id and f.uid = '" . $user_id . "'", "left")
                ->where(['l.status' => 1])
                ->where(['u.username' => $streamName])
                ->order(['l.id' => 'DESC'])
                ->field([
                    'l.id',
                    'l.title',
                    'l.thumb',
                    'l.push_end_time',
                    'l.created_at',
                    'l.view_num',
                    'l.like_num',
                    'l.share_num',
                    'l.status',
                    "u.name",
                    "ifnull(u.head_img,'static/image/head.png') head_img",
                    "ifnull(f.id,'0') is_follow"
                ])
                ->select();
            $value['live'] = $live;
        }
        return success('成功', [
            'total'         => $total,
            'total_page'    => $totalPage,
            'current_page'  => $page,
            'list'          => $data
        ]);
    }

    // 直播详情
    public function getView()
    {
        $streamName = input('streamName');
        if (empty($streamName)) {
            return error('直播间号不能为空');
        }
        $user = session("user") ? session("user") : session("guest_user");
        $user_id = $user['id'] ? $user['id']:0;
        $live = Db('live l')
            ->join('user u', 'l.user_id=u.id', "left")
            ->join("follow f", "l.user_id=f.follow_id and f.uid = '" . $user_id . "'", "left")
            ->where(['l.status' => 1])
            ->where(['users.username' => $streamName])
            ->order(['live.id' => 'DESC'])
            ->field(
                'l.id',
                'l.user_id',
                'l.title',
                'l.thumb',
                'l.push_end_time',
                'l.created_at',
                'l.view_num',
                'l.like_num',
                'l.share_num',
                'l.status',
                "u.name",
                "ifnull(u.head_img,'static/image/head.png') head_img",
                "ifnull(f.id,'0') is_follow"
            )->select();
        if (empty($live)) {
            return error('直播间不存在或关闭');
        }
        $live_state = $this->service->getLiveStreamState($streamName);
        if (!$live_state) {
            return error('直播间已关闭');
        }
        $live_urls = $this->service->getLiveUrl($streamName);

        $live['live_urls'] = $live_urls;
        return success('成功', $live);
    }

    // 开始直播
    public function getStart()
    {
        $hd = input('hd', 0);
        $user = session("user") ? session("user") : session("guest_user");
        if (empty($user)) {
            return error('请先登录');
        }
        $live = Db('live l')->where(['user_id' => $user['id']])->where(['status' => 1])->order(['id' => 'DESC'])->find();
        $push_end_time = date('Y-m-d H:i:s', strtotime('+1 day'));
        if (empty($live)) {
            $data = [
                'user_id' => $user['id'],
                'title' => input('title', ''),
                'thumb' => input('thumb', ''),
                'rtmp_push_url' => $this->service->getPushUrl($user['name'], $push_end_time, '', $hd),
                'push_end_time' => $push_end_time,
                'status' => 1,
            ];
            $id = Db("live")->insertGetId($data);
            if (!$id) {
                return error('失败');
            }
            return success('成功', $id);
        } else {
            if ($live['push_end_time'] < date('Y-m-d H:i:s', strtotime("-1 hour"))) {
                $data = [
                    'title' => input('title', ''),
                    'thumb' => input('thumb', ''),
                    'rtmp_push_url' => $this->service->getPushUrl($user['name'], $push_end_time, ''),
                    'push_end_time' => $push_end_time
                ];
                $result = Db("live")->where(['id' => $live['id']])->update($data);
                if (!$result) {
                    return error('失败');
                }
            } else {
                $data = [
                    'title' => input('title', ''),
                    'thumb' => input('thumb', ''),
                    'rtmp_push_url' => $this->service->getPushUrl($user['name'], $push_end_time, ''),
                    'push_end_time' => $push_end_time
                ];
                $result = Db("live")->where(['id' => $live['id']])->update($data);
                if (!$result) {
                    return error('失败');
                }
            }
            return success('成功', $live);
        }
    }

    // 关闭直播
    public function getClose()
    {
        $user = session("user") ? session("user") : session("guest_user");
        if (empty($user)) {
            return error('请先登录');
        }
        $result = Db('live l')->where(['user_id' => $user['id']])->where(['status' => 1])->update(['status' => 0]);
        if (!$result) {
            return error('失败');
        }
        $this->service->dropLiveStream($user['name']);
        return success('成功');
    }

    // 历史直播列表
    public function getHistory()
    {
        $page = input("page", 1);
        $limit = (int)input('limit', 10);
        $streamName = input('streamName', '');
        $model = Db('live l');
        $model->join('user u', 'l.user_id=u.id', "left");
        $user = session("user") ? session("user") : session("guest_user");
        $user_id = $user['id'] ? $user['id']:0;
        $model->join("follow f", "l.user_id=f.follow_id and f.uid = '" . $user_id . "'", "left");
        $model->where(['l.status' => 1]);
        if ($streamName) {
            $model->where(['u.username' => $streamName]);
        }
        $list = $model->order(['l.id' => 'DESC'])
            ->field([
                'l.id',
                'l.title',
                'l.thumb',
                'l.push_end_time',
                'l.created_at',
                'l.view_num',
                'l.like_num',
                'l.share_num',
                'l.status',
                "u.name",
                "ifnull(u.head_img,'static/image/head.png') head_img",
                "ifnull(f.id,'0') is_follow"
            ])
            ->page($page, $limit)
            ->select();
        return success('成功', $list);
    }
}
