<?php
header('Access-Control-Allow-Origin: http://movemama.com'); //设置http://www.baidu.com允许跨域访问
header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With'); //设置允许的跨域header
date_default_timezone_set("Asia/chongqing");
error_reporting(E_ERROR);
header("Content-Type: text/html; charset=utf-8");

$CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents("config.json")), true);
$action = $_GET['action'];
switch ($action) {
    case 'config':
        $result =  json_encode($CONFIG);
        break;

    /* 上传图片 */
    case 'uploadimage':
    /* 上传涂鸦 */
    case 'uploadscrawl':
    /* 上传视频 */
    case 'uploadvideo':
    /* 上传文件 */
    case 'uploadfile':
        $result = include("action_upload.php");
        break;

    /* 列出图片 */
    case 'listimage':
        $result = include("action_list.php");
        break;
    /* 列出文件 */
    case 'listfile':
        $result = include("action_list.php");
        break;

    /* 抓取远程文件 */
    case 'catchimage':
        $result = include("action_crawler.php");
        break;

    default:
        $result = json_encode(array(
            'state'=> '请求地址出错'
        ));
        break;
}

/* 输出结果 */
if (isset($_GET["callback"])) {
    if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
        echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
    } else {
        echo json_encode(array(
            'state'=> 'callback参数不合法'
        ));
    }
} else {
    if($_GET['action'] == 'uploadimage'){

        //图片同步CDN
        $uploadCDNResult = true;
        $upload_path = dirname(dirname(dirname(dirname(dirname(__DIR__)))))."/Uploads/";
        $YII_ENV = isset($_SERVER['YII_ENV'])?$_SERVER['YII_ENV']:'prod';
        if($YII_ENV == 'prod'){
            $config = [
                'RSYNC_CDN_ADDRESS'=>[
                    '172.31.1.3::cdn.uploads.mutantbox.com',
                    '172.31.1.2::cdn.uploads.mutantbox.com'
                ],
                'UPLOAD_CDN_URL' =>  '//cdn.mutantbox.com/00/03/uploads'
            ];
        }else{
            $config = [
                'RSYNC_CDN_ADDRESS'=>['52.197.150.65::uploads.testcdn.movemama.com'],
                'UPLOAD_CDN_URL' =>  '//testcdn.movemama.com/00/03/uploads'
            ];
        }
        $result = json_decode($result,true);
        $need_rsync_path = $upload_path.str_replace('/Uploads/','',$result['url']);
        foreach ($config['RSYNC_CDN_ADDRESS'] as  $address) {
            $address = $address.'/'.dirname(str_replace('/Uploads/','',$result['url'])).'/';
            //测试用
            // file_put_contents('./_test_data.log',json_encode([$result,$need_rsync_path,$address]), FILE_APPEND);
            
            system("rsync -avr {$need_rsync_path} {$address} > /dev/null", $systemResult);
            if ($systemResult != 0) {
                $uploadCDNResult = false;
                break;
            }else{

            }
        }
        if($uploadCDNResult){
            @unlink($upload_path.str_replace('/Uploads/','',$result['url']));
            $result['url'] = $config['UPLOAD_CDN_URL'].'/'.str_replace('/Uploads/','',$result['url']);
            $result = json_encode($result);
        }else{
            $result = json_decode($result,true);
            $result['state'] = 'Upload Failed!';
            $result['url'] = '';
            $result = json_encode($result);
        }


        echo '<html>
                        <head>
                        <meta charset="UTF-8"><title></title>
                        <script>document.domain = \''.$_SERVER['HTTP_HOST'].'\'</script>
                        </head>
                        <body>'. $result .'</body>
                        </html>';exit;
    }
    echo $result;
}

function deldir($path){
    $dh = opendir($path);
    while(($d = readdir($dh)) !== false){
        if($d == '.' || $d == '..'){//如果为.或..
            continue;
        }
        $tmp = $path.'/'.$d;
        if(!is_dir($tmp)){//如果为文件
            @unlink($tmp);
        }else{//如果为目录
            deldir($tmp);
        }
    }
    closedir($dh);
    rmdir($path);
}