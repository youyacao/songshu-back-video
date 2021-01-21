<?php
/**
All rights Reserved, Designed By www.youyacao.com
@Description：邮箱服务方法文件
@author:成都市一颗优雅草科技有限公司
@version 松鼠短视频系统-后端部分
注意：后端代码在获得授权之前通过其他非官方渠道获得代码均为侵权，禁止用于商业用途，否则将承担因此带来等版权纠纷。
需要商业用途或者定制开发等可访问songshu.youyacao.com   联系QQ:422108995 23625059584

 */
namespace app\api\common;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * 邮件工具类
 * Class Mail
 * @package app\api\common
 */
class Mail
{
    public $user,$pass,$name,$smtp,$isDebug;
    public $mail;
    /**
     * Mail constructor.
     * @param string $user
     * @param string $pass
     * @param string $smtp
     * @param int $isDebug
     */
    public function __construct($user,$pass,$name,$smtp,$isDebug=0)
    {

        $this->user = $user;
        $this->pass=$pass;
        $this->name =$name;
        $this->smtp = $smtp;
        $this->isDebug=$isDebug;
        $this->mail = new PHPMailer();
    }

    /**
     * Notes: <br>
     * User:bigniu <br>
     * Date:2020-03-12 <br>
     * Time:13:37:35 <br>
     * Company:成都市一颗优雅草科技有限公司 <br>
     * @param string $mail
     * @param string $code
     */
    public function sendRegisterMail($mail,$code,$type="register"){
        if (!$mail || !$code) {
            return false;
        }
        //获取数据库是否有数据
        $mailData = Db("mail_code")->where(['mail' => $mail,'type'=>$type])->find();
        $title='注册验证【一颗优雅草科技】';
        $yzmcontent = str_replace("{验证码}", $code, config("mail_template"));
        if ($mailData) {

            //有数据：
            //判断时间是否超时
            if (time() - $mailData['time'] < config("mail_sleep_time")) {
                //未超时直接返回false
                return false;
            } else {
                //已超时，发送邮件
                //判断是否发送成功
                if($this->sendMail($mail,$title,$yzmcontent)){
                    Db("mail_code")->where(['mail' => $mail,'type'=>$type])->update(['code' => $code, 'time' => time(), 'count' => 0]);
                    //发送成功，返回true
                    return true;
                }else{
                    //发送失败，返回false
                    return false;
                }
            }
        } else {
            //无数据
            //发送消息
            //判断是否发送成功
            if($this->sendMail($mail,$title,$yzmcontent)){
                Db("mail_code")->insert(['mail' => $mail,'type'=>$type,'code' => $code, 'time' => time(), 'count' => 0]);
                //发送成功，返回true
                return true;
            }else{
                //发送失败，返回false
                return false;
            }
        }

    }
    public function verifyCode($mail,$code,$type="register"){
        if (!$mail || !$code) {
            return false;
        }

        $mailData = Db("mail_code")->where(['mail' => $mail,'type'=>$type])->find();

        //判断短信记录是否存在
        if (!$mailData) {
            //不存在直接返回false
            return false;
        }
        //存在，判断是否超时
        if (time() - $mailData['time'] > config("mail_life_time")) {
            //超时，返回false
            return false;
        }
        //未超时，判断错误次数是否超过限定次数
        if ($mailData['count'] >= config("mail_err_count")) {
            //错误次数过多，直接返回false
            return false;
        }
        //未超过，判断验证码是否正确
        if ($mailData['code'] != $code) {
            //不正确，错误次数+1，返回false
            Db('mail_code')->where(['mail' => $mail,'type'=>$type])->inc('count', 1)->update();
            return false;
        }
        //正确，清除当前验证码记录，防止二次使用，返回true
        Db('mail_code')->where(['mail' => $mail,'type'=>$type])->delete();
        return true;
    }
    /**
     * Notes: <br>
     * User:bigniu <br>
     * Date:2020-03-12 <br>
     * Time:13:38:13 <br>
     * Company:成都市一颗优雅草科技有限公司 <br>
     * @param string $mail
     * @param string $title
     * @param string $content
     * @param bool $isHtml
     * @return bool <br>
     */
    public function sendMail($mailRec,$title,$content,$isHtml=false){
        $mail = $this->mail;
        try {
            //邮件调试模式
            $mail->SMTPDebug = 0;
            //设置邮件使用SMTP
            $mail->isSMTP();
            // 设置邮件程序以使用SMTP
            $mail->Host = $this->smtp;
            // 设置邮件内容的编码
            $mail->CharSet = 'UTF-8';
            // 启用SMTP验证
            $mail->SMTPAuth = true;
            // SMTP username
            $mail->Username = $this->user;
            // SMTP password
            $mail->Password = $this->pass;
            // 启用TLS加密，`ssl`也被接受
//            $mail->SMTPSecure = 'tls';
            // 连接的TCP端口
//            $mail->Port = 587;
            //设置发件人
            $mail->setFrom($this->user, $this->name);
            //  添加收件人1
            $mail->addAddress($mailRec, '');     // Add a recipient
//            $mail->addAddress('ellen@example.com');               // Name is optional
//            收件人回复的邮箱
            $mail->addReplyTo($this->user, $this->name);
//            抄送
//            $mail->addCC('cc@example.com');
//            $mail->addBCC('bcc@example.com');
            //附件
//            $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//            $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
            //Content
            // 将电子邮件格式设置为HTML
            $mail->isHTML($isHtml);
            $mail->Subject = $title;
            $mail->Body = $content;
//            $mail->AltBody = '这是非HTML邮件客户端的纯文本';
            return $mail->send();
        } catch (Exception $e) {
            /*echo 'Mailer Error: ' . $mail->ErrorInfo;*/
            return false;
        }
    }
}