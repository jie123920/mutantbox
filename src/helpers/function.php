<?php
namespace app\helpers;

use \app\helpers\Passport;

class coreFunction
{
    function set_title($title = "", $prefix = "-")
    {
        return C("WEB_SITE_TITLE") . $prefix . $title;
    }


    function base_encode($str)
    {
        $src = array("/", "+", "=");
        $dist = array("_a", "_b", "_c");
        $old = base64_encode($str);
        $new = str_replace($src, $dist, $old);
        return $new;
    }

    function base_decode($str)
    {
        $src = array("_a", "_b", "_c");
        $dist = array("/", "+", "=");
        $old = str_replace($src, $dist, $str);
        $new = base64_decode($old);
        return $new;
    }

    /**
     * 处理对应语言数据
     *
     * @access public
     * @param  void &$data
     * @return void
     */
    function process_language_data(&$data, $multi = true)
    {
        $lang = 'en-us';
        if ($multi) {
            foreach ($data as &$item) {
                foreach (array('title', 'message', 'description') as $field) {
                    if (!isset($item[$field])) continue;
                    $tmp_arr = json_decode($item[$field], true);
                    $tmp_arr = $tmp_arr === null ? array('en-us' => $item[$field]) : $tmp_arr;

                    $item[$field] = isset($tmp_arr[$lang]) ? $tmp_arr[$lang] : $tmp_arr['en-us'];
                }
            }
            return true;
        }

        foreach (array('title', 'message', 'description') as $field) {
            if (!isset($data[$field])) continue;
            $tmp_arr = json_decode($data[$field], true);
            $tmp_arr = $tmp_arr === null ? array('en-us' => $data[$field]) : $tmp_arr;

            $data[$field] = isset($tmp_arr[$lang]) ? $tmp_arr[$lang] : $tmp_arr['en-us'];
        }
    }

    /**
     * 检测验证码是否正确
     * @param string $code
     * @param int $id
     * @return boolean true验证码正确,false验证码错误
     */
    function check_verify($code, $id = '')
    {
        $verfiy = new \Think\Verify();
        return $verfiy->check($code, $id);
    }

    /**
     * 数据签名认证
     * @param  array $data 被认证的数据
     * @return string       签名
     */
    function data_auth_sign($data)
    {
//数据类型检测
        if (!is_array($data)) {
            $data = (array)$data;
        }
        ksort($data); //排序
        $code = http_build_query($data); //url编码并生成query字符串
        $sign = sha1($code); //生成签名
        return $sign;
    }


    /**
     * 根据获取头像
     * @return string       用户名
     */
    function get_avatar()
    {
        $user_auth = cookie('user_auth');
        if (isset($user_auth['thumb_avatar'])) {
            return $user_auth['thumb_avatar'];
        }

        //print_r($user_auth);
        $userModel = M('User');
        $userInfo = $userModel->where(array('email' => $user_auth['email']))->getField('avatar_url');
        $data['headerImg'] = $userInfo == null ? 'avatar01_20160305.jpg' : $userInfo;
        if (strpos($data['headerImg'], 'avatar') === false) {
            $data['url'] = "/Uploads/UserAvatar/{$data['headerImg']}";
        } else {
            $data['url'] = '/Public/' . C('DEFAULT_THEME') . "/Common/images/UserAvatar/{$data['headerImg']}";
        }
        return $data['url'];
        //return $user_auth['thumb_avatar'] ? $user_auth['thumb_avatar'] : "avatar_default_20160305.jpg";
    }

    /**
     * 根据用获取用户名
     * @return string       用户名
     */
    function get_username()
    {
        $user_auth = cookie('user_auth');
        return $user_auth['username'];
    }

    /**
     * 根据用获取用户email
     * @return string       用户名
     */
    function get_useremail()
    {
        $user_auth = cookie('user_auth');
        return $user_auth['email'];
    }

    /**
     * 字符串截取，支持中文和其他编码
     * @static
     * @access public
     * @param string $str 需要转换的字符串
     * @param string $start 开始位置
     * @param string $length 截取长度
     * @param string $charset 编码格式
     * @param string $suffix 截断显示字符
     * @return string
     */
    function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
    {
        if (function_exists("mb_substr"))
            $slice = mb_substr($str, $start, $length, $charset);
        elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
            if (false === $slice) {
                $slice = '';
            }
        } else {
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("", array_slice($match[0], $start, $length));
        }
        return $suffix ? $slice . '...' : $slice;
    }

    /**
     * 系统加密方法
     * @param string $data 要加密的字符串
     * @param string $key 加密密钥
     * @param int $expire 过期时间 单位 秒
     * @return string
     */
    function think_encrypt($data, $key = '', $expire = 0)
    {
        $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
        $data = base64_encode($data);
        $x = 0;
        $len = strlen($data);
        $l = strlen($key);
        $char = '';

        for ($i = 0; $i < $len; $i++) {
            if ($x == $l)
                $x = 0;
            $char .= substr($key, $x, 1);
            $x++;
        }

        $str = sprintf('%010d', $expire ? $expire + time() : 0);

        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
        }
        return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
    }

    /**
     * 格式化字节大小
     * @param  number $size 字节数
     * @param  string $delimiter 数字和单位分隔符
     * @return string            格式化后的带单位的大小
     */
    function format_bytes($size, $delimiter = '')
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 5; $i++)
            $size /= 1024;
        return round($size, 2) . $delimiter . $units[$i];
    }

    /**
     * 把返回的数据集转换成Tree
     * @param array $list 要转换的数据集
     * @param string $pid parent标记字段
     * @param string $level level标记字段
     * @return array
     */
    function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
    {
// 创建Tree
        $tree = array();
        if (is_array($list)) {
// 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] = &$list[$key];
            }
            foreach ($list as $key => $data) {
// 判断是否存在parent
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[] = &$list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[$parentId];
                        $parent[$child][] = &$list[$key];
                    }
                }
            }
        }
        return $tree;
    }

    /**
     * 将list_to_tree的树还原成列表
     * @param  array $tree 原来的树
     * @param  string $child 孩子节点的键
     * @param  string $order 排序显示的键，一般是主键 升序排列
     * @param  array $list 过渡用的中间数组，
     * @return array        返回排过序的列表数组
     */
    function tree_to_list($tree, $child = '_child', $order = 'id', &$list = array())
    {
        if (is_array($tree)) {
            foreach ($tree as $key => $value) {
                $reffer = $value;
                if (isset($reffer[$child])) {
                    unset($reffer[$child]);
                    tree_to_list($value[$child], $child, $order, $list);
                }
                $list[] = $reffer;
            }
            $list = list_sort_by($list, $order, $sortby = 'asc');
        }
        return $list;
    }

    /**
     * 调用系统的API接口方法（静态方法）
     * api('User/getName','id=5'); 调用公共模块的User接口的getName方法
     * api('Admin/User/getName','id=5');  调用Admin模块的User接口
     * @param  string $name 格式 [模块名]/接口名/方法名
     * @param  array|string $vars 参数
     */
    function api($name, $vars = array())
    {
        $array = explode('/', $name);
        $method = array_pop($array);
        $classname = array_pop($array);
        $module = $array ? array_pop($array) : 'Common';
        $callback = $module . '\\Api\\' . $classname . 'Api::' . $method;
        if (is_string($vars)) {
            parse_str($vars, $vars);
        }
        return call_user_func_array($callback, $vars);
    }

    /**
     * 时间戳格式化
     * @param int $time
     * @return string 完整的时间显示
     */
    function time_format($time = NULL, $format = 'Y-m-d')
    {
        $time = $time === NULL ? NOW_TIME : intval($time);
        return date($format, $time);
    }

    /**
     * 系统邮件发送函数
     * @param string $to 接收邮件者邮箱
     * @param string $name 接收邮件者名称
     * @param string $subject 邮件主题
     * @param string $body 邮件内容
     * @param string $attachment 附件列表
     * @return boolean
     */
    function send_mail($to, $name, $subject = '', $body = '', $attachment = null)
    {
        $config = C('THINK_EMAIL');
        Vendor('PHPMailer.class#phpmailer');
        Vendor('PHPMailer.class#smtp');
        $mail = new PHPMailer(); //PHPMailer对象
        $mail->setLanguage("zh_cn");
        $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
        $mail->IsSMTP();  // 设定使用SMTP服务
        $mail->SMTPDebug = 0;                     // 关闭SMTP调试功能
// 1 = errors and messages
// 2 = messages only
        $mail->SMTPAuth = true;                  // 启用 SMTP 验证功能
//$mail->SMTPSecure = 'ssl';                 // 使用安全协议
        $mail->Host = $config['SMTP_HOST'];  // SMTP 服务器
        $mail->Port = $config['SMTP_PORT'];  // SMTP服务器的端口号
        $mail->Username = $config['SMTP_USER'];  // SMTP服务器用户名
        $mail->Password = $config['SMTP_PASS'];  // SMTP服务器密码
        $mail->SetFrom($config['FROM_EMAIL'], $config['FROM_NAME']);
        $replyEmail = $config['REPLY_EMAIL'] ? $config['REPLY_EMAIL'] : $config['FROM_EMAIL'];
        $replyName = $config['REPLY_NAME'] ? $config['REPLY_NAME'] : $config['FROM_NAME'];
        $mail->AddReplyTo($replyEmail, $replyName);
        $mail->Subject = $subject;
        $mail->MsgHTML($body);
        $mail->AddAddress($to, $name);

        if (is_array($attachment)) { // 添加附件
            foreach ($attachment as $file) {
                is_file($file) && $mail->AddAttachment($file);
            }
        }
        return $mail->Send() ? true : $mail->ErrorInfo;
    }

    /**
     * 发送HTTP请求方法
     * @param  string $url 请求URL
     * @param  array $params 请求参数
     * @param  string $method 请求方法GET/POST
     * @return array  $data   响应数据
     */
    function http($url, $params, $method = 'GET', $header = array(), $multi = false, $duration = 30)
    {
        $opts = array(
            CURLOPT_TIMEOUT => $duration,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $header
        );

        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                //判断是否传输文件
                $params = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                throw new Exception('不支持的请求方式！');
        }

        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
//     if ($error)
//         throw new Exception('请求发生错误：' . $error);
        return $data;
    }

    function http2($url, $params, $method = 'GET', $header = array(), $multi = false)
    {
        $opts = array(
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $header
        );

        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '&' . http_build_query($params);
                break;
            case 'POST':
                //判断是否传输文件
                $params = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                throw new Exception('不支持的请求方式！');
        }

        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
//     if ($error)
//         throw new Exception('请求发生错误：' . $error);
        return $data;
    }

    function get_region_info($ip)
    {
        $get_region_api = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json";
        $params = array(
            "ip" => $ip
        );

        $region_json = http($get_region_api, $params, "GET", $header);

        return json_decode($region_json, true);
    }

    function get_guid($start = 0, $length = 12)
    {
        $id = substr(strtoupper(md5(uniqid(mt_rand(), true))), $start, $length);
        return $id;
    }


    function getusernane($serverid)
    {
        //设置拼接token参数
        $dataInfo['action'] = 'getuserinfo';
        $dataInfo['time'] = NOW_TIME;
        $dataInfo['key'] = '123456@#$(*&';

        $param['open_id'] = is_login('user');
//     $param['open_id'] = Passport::getpassinfo(47240).'_'.'47240';
        $param['open_id'] = Passport::getpassinfo($param['open_id']) . '_' . $param['open_id'];
        $param['platform'] = '';
        $param['server_id'] = '';
        //拼接token
        $token = md5($dataInfo['action'] . $dataInfo['time'] . $dataInfo['key']);

        $url = C('REPORT_DATA_URL');
        //设置POST参数
        $postData = array('action' => $dataInfo['action'], 'time' => $dataInfo['time'], 'token' => $token, 'param' => json_encode($param));

        $data = http($url, $postData, 'POST');
        if ($data) {
            $data = json_decode($data, true);
            if (empty($data['msg']) && empty($data['result'])) {
                $data['msg'] = '服务器错误';
            }

            foreach ($data['data'] as $value) {
                if ($value['area'] == $serverid)
                    return $value['uname'];
            }
        }
        return '';
    }

    /**
     * 获取服务器回调地址
     */
    function getgameurl($serverid, $param = NULL)
    {
        $envid = C('ENV_TYPE');
        $key = C('TOKEN.PASSKEY');
        $gameurl = C('MY_URL.GAME');
        $url = $gameurl . 'index.php?s=Api/getServerList';

        $params = array('token' => md5($key), 'source' => 'all', 'server_id' => 'all', 'envid' => $envid);
        $serverlist = http($url, $params, 'post');
        $serverlist = json_decode($serverlist, TRUE);
        $serverlist = $serverlist['data'];

        foreach ($serverlist as $value) {
            if ($value['server_id'] == $serverid) {
                if ($param == null) {
                    return 'S' . $serverid . '-' . $value['server_name'];
                } elseif ($param == 'pay_url') {
                    return 'http://' . $value['server_ip'] . ':' . $value['http_port'] . '/pay.php';
                } elseif ($param == 'ip') {
                    return $value['server_ip'];
                } elseif ($param == 'getdata') {
                    return 'http://' . $value['server_ip'] . ':' . $value['http_port'] . '/get_data.php';
                }
            }
        }

        return '';
    }

    function debug_log($file_name = "", $content = "")
    {
        $d = date("Ymd");
        $file_name .= "_" . $d . ".log";
        $content .= "\n";
        file_put_contents(LOG_PATH . "/" . $file_name, $content, FILE_APPEND | LOCK_EX);
    }

    /**
     * 判断是否为标准邮箱
     *2016年5月5日 上午11:46:19
     * @param string $email
     * @return Ambigous <boolean, number>
     */
    function checkemail($email = '')
    {
        $istrue = false;

        if ($email) {
            $pattern = "/^[a-z0-9]+[-_\\.]?[a-z0-9]+@([a-z0-9]*[-_]?[a-z0-9]+)+[\\.][a-z]{2,3}([\\.][a-z]{2})?$/i";
            $istrue = preg_match($pattern, $email);
        }

        return $istrue;
    }

    /**
     * 根据城市获取相应时区代号
     *2016年5月12日 下午3:25:12
     * @param array $param
     * @param number $type
     * @return Ambigous <string, mixed>
     */
    function getZoneName(array $param, $type = 1)
    {
        $zoneName = '';
        $zonzArr = array(
            'EST' => 'America/New_York',//UTC -5:00
            'PST' => 'America/Los_Angeles',//UTC -8:00
            'GMT' => 'Europe/Dublin',//UTC  0:00
            'HKT' => 'Asia/Chongqing',//UTC +8:00
        );

        if (isset($param['sity']) && $type == 1) {
            $sity = $param['sity'];
            $zoneName = array_search($sity, $zonzArr);
        }

        return $zoneName;
    }

    /**
     * 根据地区编号获取地区对应名称
     *2016年5月16日 下午4:07:48
     * @param array $params
     * @param number $type
     * @return string
     */
    function getAreaName(array $params, $type = 1)
    {
        $areaName = '';
        $ararNames = array(
            //1开头的为美洲
            //10 => 'America',
            11 => 'US West',
            12 => 'US East',

            //2开头的为 欧洲
            20 => 'Europe',

            //3开头的为 大洋洲
            30 => 'Oceania',

            //4开头的为 亚洲
            40 => 'Asia',
        );

        if (isset($params['areaId']) && $type == 1) {
            $areaId = $params['areaId'];
            $areaName = $ararNames[$areaId];
        }

        return $areaName;
    }


    function country_code_to_country($code)
    {
        $country = '';
        if ($code == 'AF') $country = 'Afghanistan';
        if ($code == 'AX') $country = 'Aland Islands';
        if ($code == 'AL') $country = 'Albania';
        if ($code == 'DZ') $country = 'Algeria';
        if ($code == 'AS') $country = 'American Samoa';
        if ($code == 'AD') $country = 'Andorra';
        if ($code == 'AO') $country = 'Angola';
        if ($code == 'AI') $country = 'Anguilla';
        if ($code == 'AQ') $country = 'Antarctica';
        if ($code == 'AG') $country = 'Antigua and Barbuda';
        if ($code == 'AR') $country = 'Argentina';
        if ($code == 'AM') $country = 'Armenia';
        if ($code == 'AW') $country = 'Aruba';
        if ($code == 'AU') $country = 'Australia';
        if ($code == 'AT') $country = 'Austria';
        if ($code == 'AZ') $country = 'Azerbaijan';
        if ($code == 'BS') $country = 'Bahamas the';
        if ($code == 'BH') $country = 'Bahrain';
        if ($code == 'BD') $country = 'Bangladesh';
        if ($code == 'BB') $country = 'Barbados';
        if ($code == 'BY') $country = 'Belarus';
        if ($code == 'BE') $country = 'Belgium';
        if ($code == 'BZ') $country = 'Belize';
        if ($code == 'BJ') $country = 'Benin';
        if ($code == 'BM') $country = 'Bermuda';
        if ($code == 'BT') $country = 'Bhutan';
        if ($code == 'BO') $country = 'Bolivia';
        if ($code == 'BA') $country = 'Bosnia and Herzegovina';
        if ($code == 'BW') $country = 'Botswana';
        if ($code == 'BV') $country = 'Bouvet Island (Bouvetoya)';
        if ($code == 'BR') $country = 'Brazil';
        if ($code == 'IO') $country = 'British Indian Ocean Territory (Chagos Archipelago)';
        if ($code == 'VG') $country = 'British Virgin Islands';
        if ($code == 'BN') $country = 'Brunei Darussalam';
        if ($code == 'BG') $country = 'Bulgaria';
        if ($code == 'BF') $country = 'Burkina Faso';
        if ($code == 'BI') $country = 'Burundi';
        if ($code == 'KH') $country = 'Cambodia';
        if ($code == 'CM') $country = 'Cameroon';
        if ($code == 'CA') $country = 'Canada';
        if ($code == 'CV') $country = 'Cape Verde';
        if ($code == 'KY') $country = 'Cayman Islands';
        if ($code == 'CF') $country = 'Central African Republic';
        if ($code == 'TD') $country = 'Chad';
        if ($code == 'CL') $country = 'Chile';
        if ($code == 'CN') $country = 'China';
        if ($code == 'CX') $country = 'Christmas Island';
        if ($code == 'CC') $country = 'Cocos (Keeling) Islands';
        if ($code == 'CO') $country = 'Colombia';
        if ($code == 'KM') $country = 'Comoros the';
        if ($code == 'CD') $country = 'Congo';
        if ($code == 'CG') $country = 'Congo the';
        if ($code == 'CK') $country = 'Cook Islands';
        if ($code == 'CR') $country = 'Costa Rica';
        if ($code == 'CI') $country = 'Cote d\'Ivoire';
        if ($code == 'HR') $country = 'Croatia';
        if ($code == 'CU') $country = 'Cuba';
        if ($code == 'CY') $country = 'Cyprus';
        if ($code == 'CZ') $country = 'Czech Republic';
        if ($code == 'DK') $country = 'Denmark';
        if ($code == 'DJ') $country = 'Djibouti';
        if ($code == 'DM') $country = 'Dominica';
        if ($code == 'DO') $country = 'Dominican Republic';
        if ($code == 'EC') $country = 'Ecuador';
        if ($code == 'EG') $country = 'Egypt';
        if ($code == 'SV') $country = 'El Salvador';
        if ($code == 'GQ') $country = 'Equatorial Guinea';
        if ($code == 'ER') $country = 'Eritrea';
        if ($code == 'EE') $country = 'Estonia';
        if ($code == 'ET') $country = 'Ethiopia';
        if ($code == 'FO') $country = 'Faroe Islands';
        if ($code == 'FK') $country = 'Falkland Islands (Malvinas)';
        if ($code == 'FJ') $country = 'Fiji the Fiji Islands';
        if ($code == 'FI') $country = 'Finland';
        if ($code == 'FR') $country = 'France, French Republic';
        if ($code == 'GF') $country = 'French Guiana';
        if ($code == 'PF') $country = 'French Polynesia';
        if ($code == 'TF') $country = 'French Southern Territories';
        if ($code == 'GA') $country = 'Gabon';
        if ($code == 'GM') $country = 'Gambia the';
        if ($code == 'GE') $country = 'Georgia';
        if ($code == 'DE') $country = 'Germany';
        if ($code == 'GH') $country = 'Ghana';
        if ($code == 'GI') $country = 'Gibraltar';
        if ($code == 'GR') $country = 'Greece';
        if ($code == 'GL') $country = 'Greenland';
        if ($code == 'GD') $country = 'Grenada';
        if ($code == 'GP') $country = 'Guadeloupe';
        if ($code == 'GU') $country = 'Guam';
        if ($code == 'GT') $country = 'Guatemala';
        if ($code == 'GG') $country = 'Guernsey';
        if ($code == 'GN') $country = 'Guinea';
        if ($code == 'GW') $country = 'Guinea-Bissau';
        if ($code == 'GY') $country = 'Guyana';
        if ($code == 'HT') $country = 'Haiti';
        if ($code == 'HM') $country = 'Heard Island and McDonald Islands';
        if ($code == 'VA') $country = 'Holy See (Vatican City State)';
        if ($code == 'HN') $country = 'Honduras';
        if ($code == 'HK') $country = 'Hong Kong';
        if ($code == 'HU') $country = 'Hungary';
        if ($code == 'IS') $country = 'Iceland';
        if ($code == 'IN') $country = 'India';
        if ($code == 'ID') $country = 'Indonesia';
        if ($code == 'IR') $country = 'Iran';
        if ($code == 'IQ') $country = 'Iraq';
        if ($code == 'IE') $country = 'Ireland';
        if ($code == 'IM') $country = 'Isle of Man';
        if ($code == 'IL') $country = 'Israel';
        if ($code == 'IT') $country = 'Italy';
        if ($code == 'JM') $country = 'Jamaica';
        if ($code == 'JP') $country = 'Japan';
        if ($code == 'JE') $country = 'Jersey';
        if ($code == 'JO') $country = 'Jordan';
        if ($code == 'KZ') $country = 'Kazakhstan';
        if ($code == 'KE') $country = 'Kenya';
        if ($code == 'KI') $country = 'Kiribati';
        if ($code == 'KP') $country = 'Korea';
        if ($code == 'KR') $country = 'Korea';
        if ($code == 'KW') $country = 'Kuwait';
        if ($code == 'KG') $country = 'Kyrgyz Republic';
        if ($code == 'LA') $country = 'Lao';
        if ($code == 'LV') $country = 'Latvia';
        if ($code == 'LB') $country = 'Lebanon';
        if ($code == 'LS') $country = 'Lesotho';
        if ($code == 'LR') $country = 'Liberia';
        if ($code == 'LY') $country = 'Libyan Arab Jamahiriya';
        if ($code == 'LI') $country = 'Liechtenstein';
        if ($code == 'LT') $country = 'Lithuania';
        if ($code == 'LU') $country = 'Luxembourg';
        if ($code == 'MO') $country = 'Macao';
        if ($code == 'MK') $country = 'Macedonia';
        if ($code == 'MG') $country = 'Madagascar';
        if ($code == 'MW') $country = 'Malawi';
        if ($code == 'MY') $country = 'Malaysia';
        if ($code == 'MV') $country = 'Maldives';
        if ($code == 'ML') $country = 'Mali';
        if ($code == 'MT') $country = 'Malta';
        if ($code == 'MH') $country = 'Marshall Islands';
        if ($code == 'MQ') $country = 'Martinique';
        if ($code == 'MR') $country = 'Mauritania';
        if ($code == 'MU') $country = 'Mauritius';
        if ($code == 'YT') $country = 'Mayotte';
        if ($code == 'MX') $country = 'Mexico';
        if ($code == 'FM') $country = 'Micronesia';
        if ($code == 'MD') $country = 'Moldova';
        if ($code == 'MC') $country = 'Monaco';
        if ($code == 'MN') $country = 'Mongolia';
        if ($code == 'ME') $country = 'Montenegro';
        if ($code == 'MS') $country = 'Montserrat';
        if ($code == 'MA') $country = 'Morocco';
        if ($code == 'MZ') $country = 'Mozambique';
        if ($code == 'MM') $country = 'Myanmar';
        if ($code == 'NA') $country = 'Namibia';
        if ($code == 'NR') $country = 'Nauru';
        if ($code == 'NP') $country = 'Nepal';
        if ($code == 'AN') $country = 'Netherlands Antilles';
        if ($code == 'NL') $country = 'Netherlands the';
        if ($code == 'NC') $country = 'New Caledonia';
        if ($code == 'NZ') $country = 'New Zealand';
        if ($code == 'NI') $country = 'Nicaragua';
        if ($code == 'NE') $country = 'Niger';
        if ($code == 'NG') $country = 'Nigeria';
        if ($code == 'NU') $country = 'Niue';
        if ($code == 'NF') $country = 'Norfolk Island';
        if ($code == 'MP') $country = 'Northern Mariana Islands';
        if ($code == 'NO') $country = 'Norway';
        if ($code == 'OM') $country = 'Oman';
        if ($code == 'PK') $country = 'Pakistan';
        if ($code == 'PW') $country = 'Palau';
        if ($code == 'PS') $country = 'Palestinian Territory';
        if ($code == 'PA') $country = 'Panama';
        if ($code == 'PG') $country = 'Papua New Guinea';
        if ($code == 'PY') $country = 'Paraguay';
        if ($code == 'PE') $country = 'Peru';
        if ($code == 'PH') $country = 'Philippines';
        if ($code == 'PN') $country = 'Pitcairn Islands';
        if ($code == 'PL') $country = 'Poland';
        if ($code == 'PT') $country = 'Portugal, Portuguese Republic';
        if ($code == 'PR') $country = 'Puerto Rico';
        if ($code == 'QA') $country = 'Qatar';
        if ($code == 'RE') $country = 'Reunion';
        if ($code == 'RO') $country = 'Romania';
        if ($code == 'RU') $country = 'Russian Federation';
        if ($code == 'RW') $country = 'Rwanda';
        if ($code == 'BL') $country = 'Saint Barthelemy';
        if ($code == 'SH') $country = 'Saint Helena';
        if ($code == 'KN') $country = 'Saint Kitts and Nevis';
        if ($code == 'LC') $country = 'Saint Lucia';
        if ($code == 'MF') $country = 'Saint Martin';
        if ($code == 'PM') $country = 'Saint Pierre and Miquelon';
        if ($code == 'VC') $country = 'Saint Vincent and the Grenadines';
        if ($code == 'WS') $country = 'Samoa';
        if ($code == 'SM') $country = 'San Marino';
        if ($code == 'ST') $country = 'Sao Tome and Principe';
        if ($code == 'SA') $country = 'Saudi Arabia';
        if ($code == 'SN') $country = 'Senegal';
        if ($code == 'RS') $country = 'Serbia';
        if ($code == 'SC') $country = 'Seychelles';
        if ($code == 'SL') $country = 'Sierra Leone';
        if ($code == 'SG') $country = 'Singapore';
        if ($code == 'SK') $country = 'Slovakia (Slovak Republic)';
        if ($code == 'SI') $country = 'Slovenia';
        if ($code == 'SB') $country = 'Solomon Islands';
        if ($code == 'SO') $country = 'Somalia, Somali Republic';
        if ($code == 'ZA') $country = 'South Africa';
        if ($code == 'GS') $country = 'South Georgia and the South Sandwich Islands';
        if ($code == 'ES') $country = 'Spain';
        if ($code == 'LK') $country = 'Sri Lanka';
        if ($code == 'SD') $country = 'Sudan';
        if ($code == 'SR') $country = 'Suriname';
        if ($code == 'SJ') $country = 'Svalbard & Jan Mayen Islands';
        if ($code == 'SZ') $country = 'Swaziland';
        if ($code == 'SE') $country = 'Sweden';
        if ($code == 'CH') $country = 'Switzerland, Swiss Confederation';
        if ($code == 'SY') $country = 'Syrian Arab Republic';
        if ($code == 'TW') $country = 'Taiwan';
        if ($code == 'TJ') $country = 'Tajikistan';
        if ($code == 'TZ') $country = 'Tanzania';
        if ($code == 'TH') $country = 'Thailand';
        if ($code == 'TL') $country = 'Timor-Leste';
        if ($code == 'TG') $country = 'Togo';
        if ($code == 'TK') $country = 'Tokelau';
        if ($code == 'TO') $country = 'Tonga';
        if ($code == 'TT') $country = 'Trinidad and Tobago';
        if ($code == 'TN') $country = 'Tunisia';
        if ($code == 'TR') $country = 'Turkey';
        if ($code == 'TM') $country = 'Turkmenistan';
        if ($code == 'TC') $country = 'Turks and Caicos Islands';
        if ($code == 'TV') $country = 'Tuvalu';
        if ($code == 'UG') $country = 'Uganda';
        if ($code == 'UA') $country = 'Ukraine';
        if ($code == 'AE') $country = 'United Arab Emirates';
        if ($code == 'GB') $country = 'United Kingdom';
        if ($code == 'US') $country = 'United States of America';
        if ($code == 'UM') $country = 'United States Minor Outlying Islands';
        if ($code == 'VI') $country = 'United States Virgin Islands';
        if ($code == 'UY') $country = 'Uruguay, Eastern Republic of';
        if ($code == 'UZ') $country = 'Uzbekistan';
        if ($code == 'VU') $country = 'Vanuatu';
        if ($code == 'VE') $country = 'Venezuela';
        if ($code == 'VN') $country = 'Vietnam';
        if ($code == 'WF') $country = 'Wallis and Futuna';
        if ($code == 'EH') $country = 'Western Sahara';
        if ($code == 'YE') $country = 'Yemen';
        if ($code == 'ZM') $country = 'Zambia';
        if ($code == 'ZW') $country = 'Zimbabwe';
        if ($country == '') $country = $code;
        return $country;
    }


    function getLocationInfoByIp()
    {
        $client = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = @$_SERVER['REMOTE_ADDR'];
        $result = array('country' => '', 'city' => '');
        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }
        //$ip = '45.32.26.226';
        $ip_data = @json_decode
        (file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
        if ($ip_data && $ip_data->geoplugin_countryName != null) {
            $result['country'] = $ip_data->geoplugin_countryCode;
            $result['city'] = $ip_data->geoplugin_city;
        }

        $country_name = country_code_to_country($result['country']);
        return $country_name;
    }

    /**
     * 时区转化
     * 2016年6月28日 下午3:06:18
     * @author liyee
     * @param string $format
     * @param number $timestamp
     * @param number $type
     */
    function getTimestamp($format = 'Y-m-d H:i:s', $timestamp = 0, $type = 0)
    {
        $old_timezone = date_default_timezone_get();
        $timestamp = $timestamp == 0 ? time() : $timestamp;

        switch ($type) {
            case 1:
                date_default_timezone_set("America/New_York");
                break;
            case 2:
                date_default_timezone_set('US/Pacific');//太平洋时区
                break;
            case 3:
                date_default_timezone_set('Europe/London');
                break;
            case 4:
                date_default_timezone_set('PRC');
                break;
            default:
                date_default_timezone_set('US/Pacific');//太平洋时区
        }

        $datetime = date($format, $timestamp);
        date_default_timezone_set($old_timezone);
        return $datetime;
    }


    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    function get_client_ip($type = 0,$adv=false) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($adv){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos    =   array_search('unknown',$arr);
                if(false !== $pos) unset($arr[$pos]);
                $ip     =   trim($arr[0]);
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip     =   $_SERVER['HTTP_CLIENT_IP'];
            }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip     =   $_SERVER['REMOTE_ADDR'];
            }
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
}