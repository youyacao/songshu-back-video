<?php


namespace app\api\controller;


use think\Controller;

class Comment extends Controller
{
    /**
     * 根据视频ID获取评论列表
     * @return mixed
     */
    public function getList(){
        $vid = input("vid");
        $comments = Db("comment c")
            ->where(['vid'=>$vid,"pid"=>0])
            ->join("user u","c.uid=u.id")
            ->select();
        $comments=$this->subComment($comments,$vid);
        return $comments;
    }

    /**
     * 获取下级评论列表
     * @param $comments
     * @param $vid
     * @return mixed
     */
    private function subComment($comments,$vid){
        foreach ($comments  as $key=>$item)
        {
            $subcomments = Db("comment c")
                ->where(['vid'=>$vid,"pid"=>$item['id']])
                ->join("user u","c.uid=u.id")
                ->select();
            $comments[$key]['sub_comment']=$subcomments;
            $this->subComment($subcomments,$vid);
        }
        return $comments;
    }
    public function postData(){
        $vid = input("vid/i");//视频ID
        $uid = input("uid/i");//用户ID
        $content = input("content");//评论内容



    }
}