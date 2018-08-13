<?php
namespace app\helpers;
use \app\Library\ImageApi;
use yii\base\Exception;
use app\Library\curl\Curl;
use phpseclib\Crypt\DES;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/22
 * Time: 16:19
 */
class myhelper
{
    static $country;
    
    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
     public static function  get_client_ip($type = 0,$adv=false) {
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


    /**
     * 判断是否为标准邮箱
     *2016年5月5日 上午11:46:19
     * @param string $email
     * @return Ambigous <boolean, number>
     */
    static function grepcheck($value, $action = 'email'){
        $istrue = false;

        switch ($action){
            case 'postcode':
                $pattern = "/^[0-9]\\d{5}$/";
            break;
            case 'intelphone':
                $pattern = "/^((([0\\+]\\d{2,3}-)?(0\\d{2,3})-)?(\\d{7,8})(-(\\d{3,}))?)$/";
            break;
            case 'cellphone':
                $pattern = "/^(\\+?\\d{1,3})?1(3|4|5|7|8)\\d{9}$/";
            break;
            default:       
                // $pattern = "/^[a-z0-9]+[-_\\.]?[a-z0-9]?+@([a-z0-9]*[-_]?[a-z0-9]+)+[\\.][a-z]{1,3}([\\.][a-z]{2})?$/i";
                $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
        }            
        $istrue = preg_match($pattern, $value);

        return $istrue;
    }

    /**
     * 数据签名认证
     * @param  array  $data 被认证的数据
     * @return string       签名
     */
    static function data_auth_sign($data) {
//数据类型检测
        if (!is_array($data)) {
            $data = (array) $data;
        }
        ksort($data); //排序
        $code = http_build_query($data); //url编码并生成query字符串
        $sign = sha1($code); //生成签名
        return $sign;
    }


    static function getLocationInfoByIp(){
        $client = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = @$_SERVER['REMOTE_ADDR'];
        $result = array('country'=>'', 'city'=>'');
        if(filter_var($client, FILTER_VALIDATE_IP)){
            $ip = $client;
        }elseif(filter_var($forward, FILTER_VALIDATE_IP)){
            $ip = $forward;
        }else{
            $ip = $remote;
        }
        //$ip = '45.32.26.226';
        $ip_data = @json_decode
        (file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip));
        if($ip_data && $ip_data->geoplugin_countryName != null){
            $result['country'] = $ip_data->geoplugin_countryCode;
            $result['city'] = $ip_data->geoplugin_city;
        }
        if(!$result['country']) $result['country'] = 'CN';
        $country_name = self::country_code_to_country($result['country']);
        return $country_name;
    }


    static function country_code_to_country( $code ){
        $country = '';
        if( $code == 'AF' ) $country = 'Afghanistan';
        if( $code == 'AX' ) $country = 'Aland Islands';
        if( $code == 'AL' ) $country = 'Albania';
        if( $code == 'DZ' ) $country = 'Algeria';
        if( $code == 'AS' ) $country = 'American Samoa';
        if( $code == 'AD' ) $country = 'Andorra';
        if( $code == 'AO' ) $country = 'Angola';
        if( $code == 'AI' ) $country = 'Anguilla';
        if( $code == 'AQ' ) $country = 'Antarctica';
        if( $code == 'AG' ) $country = 'Antigua and Barbuda';
        if( $code == 'AR' ) $country = 'Argentina';
        if( $code == 'AM' ) $country = 'Armenia';
        if( $code == 'AW' ) $country = 'Aruba';
        if( $code == 'AU' ) $country = 'Australia';
        if( $code == 'AT' ) $country = 'Austria';
        if( $code == 'AZ' ) $country = 'Azerbaijan';
        if( $code == 'BS' ) $country = 'Bahamas the';
        if( $code == 'BH' ) $country = 'Bahrain';
        if( $code == 'BD' ) $country = 'Bangladesh';
        if( $code == 'BB' ) $country = 'Barbados';
        if( $code == 'BY' ) $country = 'Belarus';
        if( $code == 'BE' ) $country = 'Belgium';
        if( $code == 'BZ' ) $country = 'Belize';
        if( $code == 'BJ' ) $country = 'Benin';
        if( $code == 'BM' ) $country = 'Bermuda';
        if( $code == 'BT' ) $country = 'Bhutan';
        if( $code == 'BO' ) $country = 'Bolivia';
        if( $code == 'BA' ) $country = 'Bosnia and Herzegovina';
        if( $code == 'BW' ) $country = 'Botswana';
        if( $code == 'BV' ) $country = 'Bouvet Island (Bouvetoya)';
        if( $code == 'BR' ) $country = 'Brazil';
        if( $code == 'IO' ) $country = 'British Indian Ocean Territory (Chagos Archipelago)';
        if( $code == 'VG' ) $country = 'British Virgin Islands';
        if( $code == 'BN' ) $country = 'Brunei Darussalam';
        if( $code == 'BG' ) $country = 'Bulgaria';
        if( $code == 'BF' ) $country = 'Burkina Faso';
        if( $code == 'BI' ) $country = 'Burundi';
        if( $code == 'KH' ) $country = 'Cambodia';
        if( $code == 'CM' ) $country = 'Cameroon';
        if( $code == 'CA' ) $country = 'Canada';
        if( $code == 'CV' ) $country = 'Cape Verde';
        if( $code == 'KY' ) $country = 'Cayman Islands';
        if( $code == 'CF' ) $country = 'Central African Republic';
        if( $code == 'TD' ) $country = 'Chad';
        if( $code == 'CL' ) $country = 'Chile';
        if( $code == 'CN' ) $country = 'China';
        if( $code == 'CX' ) $country = 'Christmas Island';
        if( $code == 'CC' ) $country = 'Cocos (Keeling) Islands';
        if( $code == 'CO' ) $country = 'Colombia';
        if( $code == 'KM' ) $country = 'Comoros the';
        if( $code == 'CD' ) $country = 'Congo';
        if( $code == 'CG' ) $country = 'Congo the';
        if( $code == 'CK' ) $country = 'Cook Islands';
        if( $code == 'CR' ) $country = 'Costa Rica';
        if( $code == 'CI' ) $country = 'Cote d\'Ivoire';
        if( $code == 'HR' ) $country = 'Croatia';
        if( $code == 'CU' ) $country = 'Cuba';
        if( $code == 'CY' ) $country = 'Cyprus';
        if( $code == 'CZ' ) $country = 'Czech Republic';
        if( $code == 'DK' ) $country = 'Denmark';
        if( $code == 'DJ' ) $country = 'Djibouti';
        if( $code == 'DM' ) $country = 'Dominica';
        if( $code == 'DO' ) $country = 'Dominican Republic';
        if( $code == 'EC' ) $country = 'Ecuador';
        if( $code == 'EG' ) $country = 'Egypt';
        if( $code == 'SV' ) $country = 'El Salvador';
        if( $code == 'GQ' ) $country = 'Equatorial Guinea';
        if( $code == 'ER' ) $country = 'Eritrea';
        if( $code == 'EE' ) $country = 'Estonia';
        if( $code == 'ET' ) $country = 'Ethiopia';
        if( $code == 'FO' ) $country = 'Faroe Islands';
        if( $code == 'FK' ) $country = 'Falkland Islands (Malvinas)';
        if( $code == 'FJ' ) $country = 'Fiji the Fiji Islands';
        if( $code == 'FI' ) $country = 'Finland';
        if( $code == 'FR' ) $country = 'France, French Republic';
        if( $code == 'GF' ) $country = 'French Guiana';
        if( $code == 'PF' ) $country = 'French Polynesia';
        if( $code == 'TF' ) $country = 'French Southern Territories';
        if( $code == 'GA' ) $country = 'Gabon';
        if( $code == 'GM' ) $country = 'Gambia the';
        if( $code == 'GE' ) $country = 'Georgia';
        if( $code == 'DE' ) $country = 'Germany';
        if( $code == 'GH' ) $country = 'Ghana';
        if( $code == 'GI' ) $country = 'Gibraltar';
        if( $code == 'GR' ) $country = 'Greece';
        if( $code == 'GL' ) $country = 'Greenland';
        if( $code == 'GD' ) $country = 'Grenada';
        if( $code == 'GP' ) $country = 'Guadeloupe';
        if( $code == 'GU' ) $country = 'Guam';
        if( $code == 'GT' ) $country = 'Guatemala';
        if( $code == 'GG' ) $country = 'Guernsey';
        if( $code == 'GN' ) $country = 'Guinea';
        if( $code == 'GW' ) $country = 'Guinea-Bissau';
        if( $code == 'GY' ) $country = 'Guyana';
        if( $code == 'HT' ) $country = 'Haiti';
        if( $code == 'HM' ) $country = 'Heard Island and McDonald Islands';
        if( $code == 'VA' ) $country = 'Holy See (Vatican City State)';
        if( $code == 'HN' ) $country = 'Honduras';
        if( $code == 'HK' ) $country = 'Hong Kong';
        if( $code == 'HU' ) $country = 'Hungary';
        if( $code == 'IS' ) $country = 'Iceland';
        if( $code == 'IN' ) $country = 'India';
        if( $code == 'ID' ) $country = 'Indonesia';
        if( $code == 'IR' ) $country = 'Iran';
        if( $code == 'IQ' ) $country = 'Iraq';
        if( $code == 'IE' ) $country = 'Ireland';
        if( $code == 'IM' ) $country = 'Isle of Man';
        if( $code == 'IL' ) $country = 'Israel';
        if( $code == 'IT' ) $country = 'Italy';
        if( $code == 'JM' ) $country = 'Jamaica';
        if( $code == 'JP' ) $country = 'Japan';
        if( $code == 'JE' ) $country = 'Jersey';
        if( $code == 'JO' ) $country = 'Jordan';
        if( $code == 'KZ' ) $country = 'Kazakhstan';
        if( $code == 'KE' ) $country = 'Kenya';
        if( $code == 'KI' ) $country = 'Kiribati';
        if( $code == 'KP' ) $country = 'Korea';
        if( $code == 'KR' ) $country = 'Korea';
        if( $code == 'KW' ) $country = 'Kuwait';
        if( $code == 'KG' ) $country = 'Kyrgyz Republic';
        if( $code == 'LA' ) $country = 'Lao';
        if( $code == 'LV' ) $country = 'Latvia';
        if( $code == 'LB' ) $country = 'Lebanon';
        if( $code == 'LS' ) $country = 'Lesotho';
        if( $code == 'LR' ) $country = 'Liberia';
        if( $code == 'LY' ) $country = 'Libyan Arab Jamahiriya';
        if( $code == 'LI' ) $country = 'Liechtenstein';
        if( $code == 'LT' ) $country = 'Lithuania';
        if( $code == 'LU' ) $country = 'Luxembourg';
        if( $code == 'MO' ) $country = 'Macao';
        if( $code == 'MK' ) $country = 'Macedonia';
        if( $code == 'MG' ) $country = 'Madagascar';
        if( $code == 'MW' ) $country = 'Malawi';
        if( $code == 'MY' ) $country = 'Malaysia';
        if( $code == 'MV' ) $country = 'Maldives';
        if( $code == 'ML' ) $country = 'Mali';
        if( $code == 'MT' ) $country = 'Malta';
        if( $code == 'MH' ) $country = 'Marshall Islands';
        if( $code == 'MQ' ) $country = 'Martinique';
        if( $code == 'MR' ) $country = 'Mauritania';
        if( $code == 'MU' ) $country = 'Mauritius';
        if( $code == 'YT' ) $country = 'Mayotte';
        if( $code == 'MX' ) $country = 'Mexico';
        if( $code == 'FM' ) $country = 'Micronesia';
        if( $code == 'MD' ) $country = 'Moldova';
        if( $code == 'MC' ) $country = 'Monaco';
        if( $code == 'MN' ) $country = 'Mongolia';
        if( $code == 'ME' ) $country = 'Montenegro';
        if( $code == 'MS' ) $country = 'Montserrat';
        if( $code == 'MA' ) $country = 'Morocco';
        if( $code == 'MZ' ) $country = 'Mozambique';
        if( $code == 'MM' ) $country = 'Myanmar';
        if( $code == 'NA' ) $country = 'Namibia';
        if( $code == 'NR' ) $country = 'Nauru';
        if( $code == 'NP' ) $country = 'Nepal';
        if( $code == 'AN' ) $country = 'Netherlands Antilles';
        if( $code == 'NL' ) $country = 'Netherlands the';
        if( $code == 'NC' ) $country = 'New Caledonia';
        if( $code == 'NZ' ) $country = 'New Zealand';
        if( $code == 'NI' ) $country = 'Nicaragua';
        if( $code == 'NE' ) $country = 'Niger';
        if( $code == 'NG' ) $country = 'Nigeria';
        if( $code == 'NU' ) $country = 'Niue';
        if( $code == 'NF' ) $country = 'Norfolk Island';
        if( $code == 'MP' ) $country = 'Northern Mariana Islands';
        if( $code == 'NO' ) $country = 'Norway';
        if( $code == 'OM' ) $country = 'Oman';
        if( $code == 'PK' ) $country = 'Pakistan';
        if( $code == 'PW' ) $country = 'Palau';
        if( $code == 'PS' ) $country = 'Palestinian Territory';
        if( $code == 'PA' ) $country = 'Panama';
        if( $code == 'PG' ) $country = 'Papua New Guinea';
        if( $code == 'PY' ) $country = 'Paraguay';
        if( $code == 'PE' ) $country = 'Peru';
        if( $code == 'PH' ) $country = 'Philippines';
        if( $code == 'PN' ) $country = 'Pitcairn Islands';
        if( $code == 'PL' ) $country = 'Poland';
        if( $code == 'PT' ) $country = 'Portugal, Portuguese Republic';
        if( $code == 'PR' ) $country = 'Puerto Rico';
        if( $code == 'QA' ) $country = 'Qatar';
        if( $code == 'RE' ) $country = 'Reunion';
        if( $code == 'RO' ) $country = 'Romania';
        if( $code == 'RU' ) $country = 'Russian Federation';
        if( $code == 'RW' ) $country = 'Rwanda';
        if( $code == 'BL' ) $country = 'Saint Barthelemy';
        if( $code == 'SH' ) $country = 'Saint Helena';
        if( $code == 'KN' ) $country = 'Saint Kitts and Nevis';
        if( $code == 'LC' ) $country = 'Saint Lucia';
        if( $code == 'MF' ) $country = 'Saint Martin';
        if( $code == 'PM' ) $country = 'Saint Pierre and Miquelon';
        if( $code == 'VC' ) $country = 'Saint Vincent and the Grenadines';
        if( $code == 'WS' ) $country = 'Samoa';
        if( $code == 'SM' ) $country = 'San Marino';
        if( $code == 'ST' ) $country = 'Sao Tome and Principe';
        if( $code == 'SA' ) $country = 'Saudi Arabia';
        if( $code == 'SN' ) $country = 'Senegal';
        if( $code == 'RS' ) $country = 'Serbia';
        if( $code == 'SC' ) $country = 'Seychelles';
        if( $code == 'SL' ) $country = 'Sierra Leone';
        if( $code == 'SG' ) $country = 'Singapore';
        if( $code == 'SK' ) $country = 'Slovakia (Slovak Republic)';
        if( $code == 'SI' ) $country = 'Slovenia';
        if( $code == 'SB' ) $country = 'Solomon Islands';
        if( $code == 'SO' ) $country = 'Somalia, Somali Republic';
        if( $code == 'ZA' ) $country = 'South Africa';
        if( $code == 'GS' ) $country = 'South Georgia and the South Sandwich Islands';
        if( $code == 'ES' ) $country = 'Spain';
        if( $code == 'LK' ) $country = 'Sri Lanka';
        if( $code == 'SD' ) $country = 'Sudan';
        if( $code == 'SR' ) $country = 'Suriname';
        if( $code == 'SJ' ) $country = 'Svalbard & Jan Mayen Islands';
        if( $code == 'SZ' ) $country = 'Swaziland';
        if( $code == 'SE' ) $country = 'Sweden';
        if( $code == 'CH' ) $country = 'Switzerland, Swiss Confederation';
        if( $code == 'SY' ) $country = 'Syrian Arab Republic';
        if( $code == 'TW' ) $country = 'Taiwan';
        if( $code == 'TJ' ) $country = 'Tajikistan';
        if( $code == 'TZ' ) $country = 'Tanzania';
        if( $code == 'TH' ) $country = 'Thailand';
        if( $code == 'TL' ) $country = 'Timor-Leste';
        if( $code == 'TG' ) $country = 'Togo';
        if( $code == 'TK' ) $country = 'Tokelau';
        if( $code == 'TO' ) $country = 'Tonga';
        if( $code == 'TT' ) $country = 'Trinidad and Tobago';
        if( $code == 'TN' ) $country = 'Tunisia';
        if( $code == 'TR' ) $country = 'Turkey';
        if( $code == 'TM' ) $country = 'Turkmenistan';
        if( $code == 'TC' ) $country = 'Turks and Caicos Islands';
        if( $code == 'TV' ) $country = 'Tuvalu';
        if( $code == 'UG' ) $country = 'Uganda';
        if( $code == 'UA' ) $country = 'Ukraine';
        if( $code == 'AE' ) $country = 'United Arab Emirates';
        if( $code == 'GB' ) $country = 'United Kingdom';
        if( $code == 'US' ) $country = 'United States of America';
        if( $code == 'UM' ) $country = 'United States Minor Outlying Islands';
        if( $code == 'VI' ) $country = 'United States Virgin Islands';
        if( $code == 'UY' ) $country = 'Uruguay, Eastern Republic of';
        if( $code == 'UZ' ) $country = 'Uzbekistan';
        if( $code == 'VU' ) $country = 'Vanuatu';
        if( $code == 'VE' ) $country = 'Venezuela';
        if( $code == 'VN' ) $country = 'Vietnam';
        if( $code == 'WF' ) $country = 'Wallis and Futuna';
        if( $code == 'EH' ) $country = 'Western Sahara';
        if( $code == 'YE' ) $country = 'Yemen';
        if( $code == 'ZM' ) $country = 'Zambia';
        if( $code == 'ZW' ) $country = 'Zimbabwe';
        if( $country == '') $country = $code;
        return $country;
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
    static function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
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
     * 根据城市获取相应时区代号
     *2016年5月12日 下午3:25:12
     * @param array $param
     * @param number $type
     * @return Ambigous <string, mixed>
     */
    static function getZoneName(array $param, $type = 1){
        $zoneName = '';
        $zonzArr = array(
            'EST' => 'America/New_York',//UTC -5:00
            'PST' => 'America/Los_Angeles',//UTC -8:00
            'GMT' => 'Europe/Dublin',//UTC  0:00
            'HKT' => 'Asia/Chongqing',//UTC +8:00
        );

        if (isset($param['sity']) && $type == 1){
            $sity = $param['sity'];
            $zoneName = array_search($sity, $zonzArr);
        }

        return $zoneName;
    }
    /**
     * 发送HTTP请求方法
     * @param  string $url    请求URL
     * @param  array  $params 请求参数
     * @param  string $method 请求方法GET/POST
     * @return array  $data   响应数据
     */
    static function http($url, $params, $method = 'GET', $header = array(), $multi = false, $duration=30) {
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
                exit('不支持的请求方式！');
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

    static function d_rmdir($dirname) {   //删除非空目录
        if(!is_dir($dirname)) {
            return false;
        }
        $handle = @opendir($dirname);
        while(($file = @readdir($handle)) !== false){
            if($file != '.' && $file != '..'){
                $dir = $dirname . '/' . $file;
                is_dir($dir) ? self::d_rmdir($dir) : unlink($dir);
            }
        }
        closedir($handle);
        return rmdir($dirname) ;
    }


    public static function get_resetpwd_code($email = 'Ass@123.com'){
        if ($email){
            $key = \YII::$app->params['TOKEN']['ucentkey'];
            $ucenter = \YII::$app->params['MY_URL']['UCENTER'];
            $url = $ucenter.'/api/generate-code';

            $post = $params = array(
                'timestamp'=>time(),
                'account'=>$email,
                'type' => "long_text",
                'sid' => '00001',
            );
            sort($params, SORT_STRING);
            $sign = md5($key . implode("", $params));
            $post['signature'] = $sign;

            $returnData = myhelper::http($url, $post, 'POST');

            if ($returnData){
                $returnArr = json_decode($returnData, true);
                $state = $returnArr['state'];
                if ($state == 0)
                    return $returnArr['data']['code'];
            }
        }
        return false;
    }


    public static function verify_resetpwd_code($email = '',$code = ''){
        if ($email && $code){
            $key = \YII::$app->params['TOKEN']['ucentkey'];
            $ucenter = \YII::$app->params['MY_URL']['UCENTER'];
            $url = $ucenter.'/api/verify-code';

            $post = $params = array(
                'timestamp'=>time(),
                'account'=>$email,
                'sid' => '00001',
                'code'=>$code
            );
            sort($params, SORT_STRING);
            $sign = md5($key . implode("", $params));
            $post['signature'] = $sign;

            $returnData = myhelper::http($url, $post, 'POST');
            if ($returnData){
                $returnArr = json_decode($returnData, true);
                $state = $returnArr['state'];
                if ($state == 0)
                    return true;
            }
        }
        return false;
    }

    public static function sendEmail($subject='',$to='',$html=''){
        $params = [
            "app_id" => "782937352627475",
            "type" => "email",
            "data" => [
                "from"=>'Mutantbox<postmaster@mutantboxmail.com>',
                "subject"=>$subject,
                "to"=>$to,
                "html"=>$html
            ],
            "timestamp" => time(),
        ];

        $arr = [];
        foreach ($params as $key => $value) {
            if (!is_array($value)) {
                $arr[] = $key . $value;
            }
        }

        sort($arr, SORT_STRING);

        $EMAIL = \YII::$app->params['MY_URL']['EMAIL'];
        $secretKey = \YII::$app->params['TOKEN']['email_token'];
        // 签名字符串
        $params['signature'] = md5($secretKey . implode("", $arr));

        $url = $EMAIL.'/message/generate';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($params)))
        );
        $returnData = curl_exec($ch);
        if ($returnData){
            $returnData = json_decode($returnData,true);
            if ($returnData['code']==0) return true;
        }
        return false;
    }

    public static function del_dir($path)
    {
        $dh = opendir($path);
        if(!$dh) return false;
        while (($d = readdir($dh)) !== false) {
            if ($d == '.' || $d == '..') {//如果为.或..
                continue;
            }
            $tmp = $path . '/' . $d;
            if (!is_dir($tmp)) {//如果为文件
                @unlink($tmp);
            } else {//如果为目录
                deldir($tmp);
            }
        }
        closedir($dh);
        rmdir($path);
    }




    public static function resize($resource, $width, $height, $protocol = 'https')
    {
        try{
            static $api = false;
            if ($api === false) {
                $host = \YII::$app->params['image_server_host'];
                $appId = \YII::$app->params['image_server_app_id'];
                $appSecret = \YII::$app->params['image_server_secret_key'];
                $appVersion = \YII::$app->params['image_server_version'];
                $api = new ImageApi($host, $appId, $appSecret, $appVersion);
            }
            $resource = preg_replace("/^http[s]?:/", '', $resource);
            $api->setResource($resource);
            return $api->resize($protocol, $width, $height);
        }catch (Exception $e){
            return '';
        }
    }
    
    /**
     * restfull验证签名
     * 2017年3月2日 下午6:10:11
     * @author liyee
     * @param unknown $sign
     * @param unknown $random
     */
    public static function checkToken($token, $userid) {
    $result = false;
    if ($token && $userid){
        
    }
    
    return $result;
    }
    
    /**
     * restfull验证签名
     * 2017年3月2日 下午6:10:11
     * @author liyee
     * @param unknown $sign
     * @param unknown $random
     */
    public static function checksign($sign, $random) {
        $result = false;
        $now = time();
        if ($sign && $random && ($now-$random < 60)){
            $key = \Yii::$app->params['TOKEN']['PASSKEY'];
            if ($sign == md5($key.$random)){
                $result = true;
            }
        }
    
        return $result;
    }
    
    /**
     * 接口错误编码
     * 2016年12月23日 下午5:45:13
     * @author liyee
     * @param string $code
     * @param string $data
     */
    static function code($code = '100', $data = '') {
        return [
            'code' => $code,
            'data' => $data,
            'massege' => \Yii::t('app', 'code.100'),
        ];
    }
    
    /**
     * 获取支付方式信息接口
     * 2017年3月3日 下午3:55:41
     * @author liyee
     * @param unknown $type
     */
    static function payinfo($type, $id = ''){
        $cache_name = 'myhelperpayinfo';
        if (\Yii::$app->cache){
            $cache_value = \Yii::$app->cache->get($cache_name);
            if (isset($cache_value[$type])){
                if ($id){
                    if (isset($cache_value[$type][$id])){
                        return $cache_value[$type][$id];
                    }                    
                }else {
                    return $cache_value[$type];
                }
            }
        }
        
        $curl = new Curl();
        $curl->setJsonDecoder(function($value) {
            return json_decode($value, true);
        });
        $shoppay = \Yii::$app->params['MY_URL']['ShopPay'];
        $clientip = isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'];
        $url = $shoppay."/v1/game/getdata?clientip=$clientip&l=1&source=shop";
        $paymentData = $curl->get($url);
        if (!isset($paymentData['status']) || $paymentData['status'] != 1) {
            return self::err(1, 'network fail');
        }else {
            if (\Yii::$app->cache){
                \Yii::$app->cache->set($cache_name, $paymentData['data'], 60);
            }
            if (isset($paymentData['data'][$type])){
                if ($id){
                    if (isset($paymentData['data'][$type][$id])){
                        return $paymentData['data'][$type][$id];
                    }else {
                        return '';
                    }
                }else {
                    return $paymentData['data'][$type];
                }
            }else {
                return '';
            }
        }
    }
    
    /**
     * 统一错误提示
     * 2017年3月3日 下午3:54:35
     * @author liyee
     * @param unknown $code
     * @param unknown $message
     */    
     static function err($code, $message) {
        return ['code' => $code, 'message' => $message, 'data' => []];
    }
    
    /**
     * aes加密
     * 2016年9月21日 下午2:48:12
     * @author liyee
     * @param string $plaintext
     * @return string
     */
    public static function DesEncrypt($plaintext = '', $type='queue') {
        if ($plaintext){
            $des = new DES();
            $key = \Yii::$app->params['TOKEN'][$type];
            $des->setKey($key);
            $des->iv = \Yii::$app->params['TOKEN'][$type];
            return base64_encode($des->encrypt($plaintext));
        }
    }
    
    /**
     * ase解密
     * 2016年9月21日 下午2:48:37
     * @author liyee
     * @param string $plaintext
     * @return string
     */
    public static function DesDecrypt($plaintext = '', $type='queue') {
        if ($plaintext){
            $des = new DES();
            $key = \Yii::$app->params['TOKEN'][$type];
            $des->setKey($key);
            $des->iv = \Yii::$app->params['TOKEN'][$type];
            if ($data = $des->decrypt(base64_decode($plaintext))){
                return $data;
            }else {
                return false;
            }
        }
    }


    
    /**
     * 国家列表
     * 2017年4月26日 下午7:10:33
     * @author liyee
     */
    public static function country(){
        $list = [];
        $region = Region::find()->select(['id', 'region_name', 'name_zh', 'country_code'])->asArray()->all();
    
        if ($region){
            foreach ($region as $item){
                $list[$item['id']] = [
                    'id' => $item['id'],
                    'region_name' => $item['region_name'],
                    'name_zh' => $item['name_zh'],
                    'country_code' => $item['country_code'],
                ];
            }
        }
        
        return $list;
    }
    
    /**
     * 根据ip获取默认国家
     * 2017年4月27日 下午4:35:31
     * @author liyee
     */
    public static function getDefaultCountry(){
        $clientIp = self::get_client_ip();
        $list = self::country();
        
        $Ip = new IpLocation('UTFWry_2.dat'); // 实例化类 参数表示IP地址库文件
        $region_result = $Ip->getlocation($clientIp); // 获取某个IP地址所在的位置
        $country = $region_result['country'];
        $lastone = substr($country, -3);
        if ($lastone == '市'){
            $country = '中国';
        }
        
        self::$country = $country;
        $default = array_filter($list, 'self::filter');
        
        return $default?array_values($default)[0]:$list['235'];
        
    }
    
    /**
     * 根据国家名称筛选
     * 2016年9月13日 上午11:10:45
     * @author liyee
     * @param unknown $countries
     */
    public static function filter($countries){
        return $countries['name_zh'] == self::$country;
    }

    /**
     * 日志记录函数
     * 2017年6月2日 上午11:15:10
     * @author liyee
     * @param unknown $channel
     * @param unknown $action
     * @param unknown $data
     */
    public static function inlog($channel, $action, $data){
        $data = is_string($data)?$data:json_encode($data);
    
        if (YII_DEBUG){
            try {
                file_put_contents(\Yii::$app->getRuntimePath().'/log'.date('Ymd').'.log', date('c').'::'.$channel.'::'.$action.'::'.$data."\n", FILE_APPEND);
            } catch (Exception $e) {
            }
        }
    }
}