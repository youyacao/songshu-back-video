<?php
error_reporting(0);
$ubomoney=$_GET["price"];
$url ='http://'.$_SERVER['HTTP_HOST'];
?>


<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Content-Language" content="zh-cn">
    <meta name="apple-mobile-web-app-capable" content="no"/>
    <meta name="apple-touch-fullscreen" content="yes"/>
    <meta name="format-detection" content="telephone=no,email=no"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="white">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title>支付详情</title>
    <link href="css/wechat_pay.css" rel="stylesheet" media="screen">
    <style>
        .text-success {
            color: #468847;
            font-size: 2.33333333em;
        }

        .text-fail {
            color: #ff0c13;
            font-size: 2.33333333em;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .error {

            display: block;
            padding: 9.5px;
            margin: 0 0 10px;
            font-size: 13px;
            line-height: 1.42857143;
            color: #333;
            word-break: break-all;
            word-wrap: break-word;
            background-color: #f5f5f5;
            border: 1px solid #ccc;
            border-radius: 4px;

        }
    </style>
</head>

<body>
<div class="body">

    <div style="margin-top: 10vh">

        <h1 class="mod-title">
            <span class="ico_log " style="font-size: 2.4rem">支付成功</span>
        </h1>

        <div class="tip-text">
        </div>


    </div>
    <div class="foot">
        <div class="inner">
            <p>如未到账请联系我们</p>
        </div>
    </div>
</div>

<script>
     setTimeout(function () {
         window.location.href = '<?php echo $url?>';
     }, 5000);//5秒后跳转
</script>
</body>
</html>