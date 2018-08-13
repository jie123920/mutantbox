<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace app\helpers;
/**
 *  IP 地理位置查询类 修改自 CoolCode.CN
 *  由于使用UTF8编码 如果使用纯真IP地址库的话 需要对返回结果进行编码转换
 * @author    liu21st <liu21st@gmail.com>
 */
class IpLocation {
    /**
     * QQWry.Dat文件指针
     *
     * @var resource
     */
    private $fp;

    /**
     * 第一条IP记录的偏移地址
     *
     * @var int
     */
    private $firstip;

    /**
     * 最后一条IP记录的偏移地址
     *
     * @var int
     */
    private $lastip;

    /**
     * IP记录的总条数（不包含版本信息记录）
     *
     * @var int
     */
    private $totalip;


    private $region;

    /**
     * 构造函数，打开 QQWry.Dat 文件并初始化类中的信息
     *
     * @param string $filename
     * @return IpLocation
     */
    public function __construct($filename = "UTFWry.dat") {
        $this->fp = 0;
        if (($this->fp      = fopen(dirname(__FILE__).'/'.$filename, 'rb')) !== false) {
            $this->firstip  = $this->getlong();
            $this->lastip   = $this->getlong();
            $this->totalip  = ($this->lastip - $this->firstip) / 7;
        }
        $this->region = dirname(__FILE__).'/';

    }

    /**
     * 返回读取的长整型数
     *
     * @access private
     * @return int
     */
    private function getlong() {
        //将读取的little-endian编码的4个字节转化为长整型数
        $result = unpack('Vlong', fread($this->fp, 4));
        return $result['long'];
    }

    /**
     * 返回读取的3个字节的长整型数
     *
     * @access private
     * @return int
     */
    private function getlong3() {
        //将读取的little-endian编码的3个字节转化为长整型数
        $result = unpack('Vlong', fread($this->fp, 3).chr(0));
        return $result['long'];
    }

    /**
     * 返回压缩后可进行比较的IP地址
     *
     * @access private
     * @param string $ip
     * @return string
     */
    private function packip($ip) {
        // 将IP地址转化为长整型数，如果在PHP5中，IP地址错误，则返回False，
        // 这时intval将Flase转化为整数-1，之后压缩成big-endian编码的字符串
        return pack('N', intval(ip2long($ip)));
    }

    /**
     * 返回读取的字符串
     *
     * @access private
     * @param string $data
     * @return string
     */
    private function getstring($data = "") {
        $char = fread($this->fp, 1);
        while (ord($char) > 0) {        // 字符串按照C格式保存，以\0结束
            $data  .= $char;             // 将读取的字符连接到给定字符串之后
            $char   = fread($this->fp, 1);
        }
        return $data;
    }

    /**
     * 返回地区信息
     *
     * @access private
     * @return string
     */
    private function getarea() {
        $byte = fread($this->fp, 1);    // 标志字节
        switch (ord($byte)) {
            case 0:                     // 没有区域信息
                $area = "";
                break;
            case 1:
            case 2:                     // 标志字节为1或2，表示区域信息被重定向
                fseek($this->fp, $this->getlong3());
                $area = $this->getstring();
                break;
            default:                    // 否则，表示区域信息没有被重定向
                $area = $this->getstring($byte);
                break;
        }
        return $area;
    }

    /**
     * 根据所给 IP 地址或域名返回所在地区信息
     *
     * @access public
     * @param string $ip
     * @return array
     */
    public function getlocation($ip='') {
        if (!$this->fp) return null;            // 如果数据文件没有被正确打开，则直接返回空
		if(empty($ip)) $ip = get_client_ip();
        $location['ip'] = gethostbyname($ip);   // 将输入的域名转化为IP地址
        $ip = $this->packip($location['ip']);   // 将输入的IP地址转化为可比较的IP地址
                                                // 不合法的IP地址会被转化为255.255.255.255
        // 对分搜索
        $l = 0;                         // 搜索的下边界
        $u = $this->totalip;            // 搜索的上边界
        $findip = $this->lastip;        // 如果没有找到就返回最后一条IP记录（QQWry.Dat的版本信息）
        while ($l <= $u) {              // 当上边界小于下边界时，查找失败
            $i = floor(($l + $u) / 2);  // 计算近似中间记录
            fseek($this->fp, $this->firstip + $i * 7);
            $beginip = strrev(fread($this->fp, 4));     // 获取中间记录的开始IP地址
            // strrev函数在这里的作用是将little-endian的压缩IP地址转化为big-endian的格式
            // 以便用于比较，后面相同。
            if ($ip < $beginip) {       // 用户的IP小于中间记录的开始IP地址时
                $u = $i - 1;            // 将搜索的上边界修改为中间记录减一
            }
            else {
                fseek($this->fp, $this->getlong3());
                $endip = strrev(fread($this->fp, 4));   // 获取中间记录的结束IP地址
                if ($ip > $endip) {     // 用户的IP大于中间记录的结束IP地址时
                    $l = $i + 1;        // 将搜索的下边界修改为中间记录加一
                }
                else {                  // 用户的IP在中间记录的IP范围内时
                    $findip = $this->firstip + $i * 7;
                    break;              // 则表示找到结果，退出循环
                }
            }
        }

        //获取查找到的IP地理位置信息
        fseek($this->fp, $findip);
        $location['beginip'] = long2ip($this->getlong());   // 用户IP所在范围的开始地址
        $offset = $this->getlong3();
        fseek($this->fp, $offset);
        $location['endip'] = long2ip($this->getlong());     // 用户IP所在范围的结束地址
        $byte = fread($this->fp, 1);    // 标志字节
        switch (ord($byte)) {
            case 1:                     // 标志字节为1，表示国家和区域信息都被同时重定向
                $countryOffset = $this->getlong3();         // 重定向地址
                fseek($this->fp, $countryOffset);
                $byte = fread($this->fp, 1);    // 标志字节
                switch (ord($byte)) {
                    case 2:             // 标志字节为2，表示国家信息又被重定向
                        fseek($this->fp, $this->getlong3());
                        $location['country']    = $this->getstring();
                        fseek($this->fp, $countryOffset + 4);
                        $location['area']       = $this->getarea();
                        break;
                    default:            // 否则，表示国家信息没有被重定向
                        $location['country']    = $this->getstring($byte);
                        $location['area']       = $this->getarea();
                        break;
                }
                break;
            case 2:                     // 标志字节为2，表示国家信息被重定向
                fseek($this->fp, $this->getlong3());
                $location['country']    = $this->getstring();
                fseek($this->fp, $offset + 8);
                $location['area']       = $this->getarea();
                break;
            default:                    // 否则，表示国家信息没有被重定向
                $location['country']    = $this->getstring($byte);
                $location['area']       = $this->getarea();
                break;
        }
        if (trim($location['country']) == 'CZ88.NET') {  // CZ88.NET表示没有有效信息
            $location['country'] = '未知';
        }
        if (trim($location['area']) == 'CZ88.NET') {
            $location['area'] = '';
        }
        return $location;
    }

    /**
     * 析构函数，用于在页面执行结束后自动关闭打开的文件。
     *
     */
    public function __destruct() {
        if ($this->fp) {
            fclose($this->fp);
        }
        $this->fp = 0;
    }


    public static function region(){
        return array(
            array('id' => '2','region_name' => 'Afghanistan','area_code' => '93','name_zh' => '阿富汗','pid' => '0'),
            array('id' => '3','region_name' => 'Åland Islands','area_code' => '35818','name_zh' => '奥兰','pid' => '0'),
            array('id' => '4','region_name' => 'Albania','area_code' => '355','name_zh' => '阿尔巴尼亚','pid' => '0'),
            array('id' => '5','region_name' => 'Algeria','area_code' => '213','name_zh' => '阿尔及利亚','pid' => '0'),
            array('id' => '6','region_name' => 'American Samoa','area_code' => '1684','name_zh' => '美属萨摩亚','pid' => '0'),
            array('id' => '7','region_name' => 'Andorra','area_code' => '376','name_zh' => '安道尔','pid' => '0'),
            array('id' => '8','region_name' => 'Angola','area_code' => '244','name_zh' => '安哥拉','pid' => '0'),
            array('id' => '9','region_name' => 'Anguilla','area_code' => '1264','name_zh' => '安圭拉','pid' => '0'),
            array('id' => '10','region_name' => 'Antigua and Barbuda','area_code' => '1268','name_zh' => '安提瓜和巴布达','pid' => '0'),
            array('id' => '11','region_name' => 'Argentina','area_code' => '54','name_zh' => '阿根廷','pid' => '0'),
            array('id' => '12','region_name' => 'Armenia','area_code' => '374','name_zh' => '亚美尼亚','pid' => '0'),
            array('id' => '13','region_name' => 'Aruba','area_code' => '297','name_zh' => '阿鲁巴','pid' => '0'),
            array('id' => '14','region_name' => 'Ascension Island','area_code' => '247','name_zh' => '阿森松岛','pid' => '0'),
            array('id' => '15','region_name' => 'Australia','area_code' => '61','name_zh' => '澳大利亚','pid' => '0'),
            array('id' => '16','region_name' => 'Austria','area_code' => '43','name_zh' => '奥地利','pid' => '0'),
            array('id' => '17','region_name' => 'Azerbaijan','area_code' => '994','name_zh' => '阿塞拜疆','pid' => '0'),
            array('id' => '18','region_name' => 'Bahamas','area_code' => '1242','name_zh' => '巴哈马','pid' => '0'),
            array('id' => '19','region_name' => 'Bahrain','area_code' => '973','name_zh' => '巴林','pid' => '0'),
            array('id' => '20','region_name' => 'Bangladesh','area_code' => '880','name_zh' => '孟加拉国','pid' => '0'),
            array('id' => '21','region_name' => 'Barbados','area_code' => '1246','name_zh' => '巴巴多斯','pid' => '0'),
            array('id' => '22','region_name' => 'Belarus','area_code' => '375','name_zh' => '白俄罗斯','pid' => '0'),
            array('id' => '23','region_name' => 'Belgium','area_code' => '32','name_zh' => '比利时','pid' => '0'),
            array('id' => '24','region_name' => 'Belize','area_code' => '501','name_zh' => '伯利兹','pid' => '0'),
            array('id' => '25','region_name' => 'Benin','area_code' => '229','name_zh' => '贝宁','pid' => '0'),
            array('id' => '26','region_name' => 'Bermuda','area_code' => '1441','name_zh' => '百慕大','pid' => '0'),
            array('id' => '27','region_name' => 'Bhutan','area_code' => '975','name_zh' => '不丹','pid' => '0'),
            array('id' => '28','region_name' => 'Bolivia','area_code' => '591','name_zh' => '玻利维亚','pid' => '0'),
            array('id' => '29','region_name' => 'Bonaire','area_code' => '5997','name_zh' => '加勒比荷兰','pid' => '0'),
            array('id' => '30','region_name' => 'Caribbean Netherlands','area_code' => '599','name_zh' => '加勒比荷兰','pid' => '0'),
            array('id' => '31','region_name' => 'Bosnia and Herzegovina','area_code' => '387','name_zh' => '波斯尼亚和黑塞哥维那','pid' => '0'),
            array('id' => '32','region_name' => 'Botswana','area_code' => '267','name_zh' => '博茨瓦纳','pid' => '0'),
            array('id' => '33','region_name' => 'Brazil','area_code' => '55','name_zh' => '巴西','pid' => '0'),
            array('id' => '34','region_name' => 'British Indian Ocean Territory','area_code' => '246','name_zh' => '英属印度洋领地','pid' => '0'),
            array('id' => '35','region_name' => 'Brunei Darussalam','area_code' => '673','name_zh' => '文莱','pid' => '0'),
            array('id' => '36','region_name' => 'Bulgaria','area_code' => '359','name_zh' => '保加利亚','pid' => '0'),
            array('id' => '37','region_name' => 'Burkina Faso','area_code' => '226','name_zh' => '布基纳法索','pid' => '0'),
            array('id' => '38','region_name' => 'Burundi','area_code' => '257','name_zh' => '布隆迪','pid' => '0'),
            array('id' => '39','region_name' => 'Cambodia','area_code' => '855','name_zh' => '柬埔寨','pid' => '0'),
            array('id' => '40','region_name' => 'Cameroon','area_code' => '237','name_zh' => '喀麦隆','pid' => '0'),
            array('id' => '41','region_name' => 'Canada','area_code' => '1','name_zh' => '加拿大','pid' => '0'),
            array('id' => '42','region_name' => 'Cape Verde','area_code' => '238','name_zh' => '佛得角','pid' => '0'),
            array('id' => '43','region_name' => 'Cayman Islands','area_code' => '1345','name_zh' => '开曼群岛','pid' => '0'),
            array('id' => '44','region_name' => 'Central African Republic','area_code' => '236','name_zh' => '中非共和国','pid' => '0'),
            array('id' => '45','region_name' => 'Chad','area_code' => '235','name_zh' => '乍得','pid' => '0'),
            array('id' => '46','region_name' => 'Chile','area_code' => '56','name_zh' => '智利','pid' => '0'),
            array('id' => '47','region_name' => 'China','area_code' => '86','name_zh' => '中国','pid' => '0'),
            array('id' => '48','region_name' => 'Christmas Island','area_code' => '61','name_zh' => '圣诞岛','pid' => '0'),
            array('id' => '49','region_name' => 'Cocos (Keeling) Islands','area_code' => '61','name_zh' => '科科斯（基林）群岛','pid' => '0'),
            array('id' => '50','region_name' => 'Colombia','area_code' => '57','name_zh' => '哥伦比亚','pid' => '0'),
            array('id' => '51','region_name' => 'Comoros','area_code' => '269','name_zh' => '科摩罗','pid' => '0'),
            array('id' => '52','region_name' => 'Congo','area_code' => '242','name_zh' => '刚果','pid' => '0'),
            array('id' => '53','region_name' => 'Congo (Republic)','area_code' => '242','name_zh' => '民主刚果','pid' => '0'),
            array('id' => '54','region_name' => 'Cook Islands','area_code' => '682','name_zh' => '库克群岛','pid' => '0'),
            array('id' => '55','region_name' => 'Costa Rica','area_code' => '506','name_zh' => '哥斯达黎加','pid' => '0'),
            array('id' => '56','region_name' => 'Côte d\'Ivoire
','area_code' => '225','name_zh' => '科特迪瓦','pid' => '0'),
            array('id' => '57','region_name' => 'Croatia','area_code' => '385','name_zh' => '克罗地亚','pid' => '0'),
            array('id' => '58','region_name' => 'Cuba','area_code' => '53','name_zh' => '古巴','pid' => '0'),
            array('id' => '59','region_name' => 'Curaçao','area_code' => '5999','name_zh' => '库拉索','pid' => '0'),
            array('id' => '60','region_name' => 'Cyprus','area_code' => '357','name_zh' => '塞浦路斯','pid' => '0'),
            array('id' => '61','region_name' => 'Czech Republic','area_code' => '420','name_zh' => '捷克','pid' => '0'),
            array('id' => '62','region_name' => 'Denmark','area_code' => '45','name_zh' => '丹麦','pid' => '0'),
            array('id' => '63','region_name' => 'Djibouti','area_code' => '253','name_zh' => '吉布提','pid' => '0'),
            array('id' => '64','region_name' => 'Dominica','area_code' => '1767','name_zh' => '多米尼克','pid' => '0'),
            array('id' => '65','region_name' => 'Dominican Republic','area_code' => '1809','name_zh' => '多米尼加','pid' => '0'),
            array('id' => '66','region_name' => 'Ecuador','area_code' => '593','name_zh' => '厄瓜多尔','pid' => '0'),
            array('id' => '67','region_name' => 'Egypt','area_code' => '20','name_zh' => '埃及','pid' => '0'),
            array('id' => '68','region_name' => 'El Salvador','area_code' => '503','name_zh' => '萨尔瓦多','pid' => '0'),
            array('id' => '69','region_name' => 'Equatorial Guinea','area_code' => '240','name_zh' => '赤道几内亚','pid' => '0'),
            array('id' => '70','region_name' => 'Eritrea','area_code' => '291','name_zh' => '厄立特里亚','pid' => '0'),
            array('id' => '71','region_name' => 'Estonia','area_code' => '372','name_zh' => '爱沙尼亚','pid' => '0'),
            array('id' => '72','region_name' => 'Ethiopia','area_code' => '251','name_zh' => '埃塞俄比亚','pid' => '0'),
            array('id' => '73','region_name' => 'Falkland Islands','area_code' => '500','name_zh' => '福克兰群岛','pid' => '0'),
            array('id' => '74','region_name' => 'Faroe Islands','area_code' => '298','name_zh' => '法罗群岛','pid' => '0'),
            array('id' => '75','region_name' => 'Fiji','area_code' => '679','name_zh' => '斐济','pid' => '0'),
            array('id' => '76','region_name' => 'Finland','area_code' => '358','name_zh' => '芬兰','pid' => '0'),
            array('id' => '77','region_name' => 'France','area_code' => '33','name_zh' => '法国','pid' => '0'),
            array('id' => '78','region_name' => 'French Guiana','area_code' => '594','name_zh' => '法属圭亚那','pid' => '0'),
            array('id' => '79','region_name' => 'French Polynesia','area_code' => '689','name_zh' => '法属波利尼西亚','pid' => '0'),
            array('id' => '80','region_name' => 'Gabon','area_code' => '241','name_zh' => '加蓬','pid' => '0'),
            array('id' => '81','region_name' => 'Gambia','area_code' => '220','name_zh' => '冈比亚','pid' => '0'),
            array('id' => '82','region_name' => 'Georgia','area_code' => '995','name_zh' => '格鲁吉亚','pid' => '0'),
            array('id' => '83','region_name' => 'Germany','area_code' => '49','name_zh' => '德国','pid' => '0'),
            array('id' => '84','region_name' => 'Ghana','area_code' => '233','name_zh' => '加纳','pid' => '0'),
            array('id' => '85','region_name' => 'Gibraltar','area_code' => '350','name_zh' => '直布罗陀','pid' => '0'),
            array('id' => '86','region_name' => 'Greece','area_code' => '30','name_zh' => '希腊','pid' => '0'),
            array('id' => '87','region_name' => 'Greenland','area_code' => '299','name_zh' => '格陵兰','pid' => '0'),
            array('id' => '88','region_name' => 'Grenada','area_code' => '1473','name_zh' => '格林纳达','pid' => '0'),
            array('id' => '89','region_name' => 'Guadeloupe','area_code' => '590','name_zh' => '瓜德罗普','pid' => '0'),
            array('id' => '90','region_name' => 'Guam','area_code' => '1671','name_zh' => '关岛','pid' => '0'),
            array('id' => '91','region_name' => 'Guatemala','area_code' => '502','name_zh' => '危地马拉','pid' => '0'),
            array('id' => '92','region_name' => 'Guernsey','area_code' => '44','name_zh' => '根西','pid' => '0'),
            array('id' => '93','region_name' => 'Guinea','area_code' => '224','name_zh' => '几内亚','pid' => '0'),
            array('id' => '94','region_name' => 'Guinea-Bissau','area_code' => '245','name_zh' => '几内亚比绍','pid' => '0'),
            array('id' => '95','region_name' => 'Guyana','area_code' => '592','name_zh' => '圭亚那','pid' => '0'),
            array('id' => '96','region_name' => 'Haiti','area_code' => '509','name_zh' => '海地','pid' => '0'),
            array('id' => '97','region_name' => 'Vatican City','area_code' => '379','name_zh' => '梵蒂冈','pid' => '0'),
            array('id' => '98','region_name' => 'Honduras','area_code' => '504','name_zh' => '洪都拉斯','pid' => '0'),
            array('id' => '99','region_name' => 'Hong Kong','area_code' => '852','name_zh' => '香港','pid' => '0'),
            array('id' => '100','region_name' => 'Hungary','area_code' => '36','name_zh' => '匈牙利','pid' => '0'),
            array('id' => '101','region_name' => 'Iceland','area_code' => '354','name_zh' => '冰岛','pid' => '0'),
            array('id' => '102','region_name' => 'India','area_code' => '91','name_zh' => '印度','pid' => '0'),
            array('id' => '103','region_name' => 'Indonesia','area_code' => '62','name_zh' => '印尼','pid' => '0'),
            array('id' => '104','region_name' => 'Iran','area_code' => '98','name_zh' => '伊朗','pid' => '0'),
            array('id' => '105','region_name' => 'Iraq','area_code' => '964','name_zh' => '伊拉克','pid' => '0'),
            array('id' => '106','region_name' => 'Ireland','area_code' => '353','name_zh' => '爱尔兰','pid' => '0'),
            array('id' => '107','region_name' => 'Isle of Man','area_code' => '44','name_zh' => '马恩岛','pid' => '0'),
            array('id' => '108','region_name' => 'Israel','area_code' => '972','name_zh' => '以色列','pid' => '0'),
            array('id' => '109','region_name' => 'Italy','area_code' => '39','name_zh' => '意大利','pid' => '0'),
            array('id' => '110','region_name' => 'Jamaica','area_code' => '1876','name_zh' => '牙买加','pid' => '0'),
            array('id' => '111','region_name' => 'Japan','area_code' => '81','name_zh' => '日本','pid' => '0'),
            array('id' => '112','region_name' => 'Jersey','area_code' => '44','name_zh' => '泽西','pid' => '0'),
            array('id' => '113','region_name' => 'Jordan','area_code' => '962','name_zh' => '约旦','pid' => '0'),
            array('id' => '114','region_name' => 'Kazakhstan','area_code' => '7','name_zh' => '哈萨克斯坦','pid' => '0'),
            array('id' => '115','region_name' => 'Kenya','area_code' => '254','name_zh' => '肯尼亚','pid' => '0'),
            array('id' => '116','region_name' => 'Kiribati','area_code' => '686','name_zh' => '基里巴斯','pid' => '0'),
            array('id' => '117','region_name' => 'Korea, North','area_code' => '850','name_zh' => '朝鲜','pid' => '0'),
            array('id' => '118','region_name' => 'Korea, South','area_code' => '82','name_zh' => '韩国','pid' => '0'),
            array('id' => '119','region_name' => 'Kuwait','area_code' => '965','name_zh' => '科威特','pid' => '0'),
            array('id' => '120','region_name' => 'Kyrgyzstan','area_code' => '996','name_zh' => '吉尔吉斯斯坦','pid' => '0'),
            array('id' => '121','region_name' => 'Laos','area_code' => '856','name_zh' => '老挝','pid' => '0'),
            array('id' => '122','region_name' => 'Latvia','area_code' => '371','name_zh' => '拉脱维亚','pid' => '0'),
            array('id' => '123','region_name' => 'Lebanon','area_code' => '961','name_zh' => '黎巴嫩','pid' => '0'),
            array('id' => '124','region_name' => 'Lesotho','area_code' => '266','name_zh' => '莱索托','pid' => '0'),
            array('id' => '125','region_name' => 'Liberia','area_code' => '231','name_zh' => '利比里亚','pid' => '0'),
            array('id' => '126','region_name' => 'Libya','area_code' => '218','name_zh' => '利比亚','pid' => '0'),
            array('id' => '127','region_name' => 'Liechtenstein','area_code' => '423','name_zh' => '列支敦士登','pid' => '0'),
            array('id' => '128','region_name' => 'Lithuania','area_code' => '370','name_zh' => '立陶宛','pid' => '0'),
            array('id' => '129','region_name' => 'Luxembourg','area_code' => '352','name_zh' => '卢森堡','pid' => '0'),
            array('id' => '130','region_name' => 'Macao','area_code' => '853','name_zh' => '澳门','pid' => '0'),
            array('id' => '131','region_name' => 'Macedonia','area_code' => '389','name_zh' => '马其顿','pid' => '0'),
            array('id' => '132','region_name' => 'Madagascar','area_code' => '261','name_zh' => '马达加斯加','pid' => '0'),
            array('id' => '133','region_name' => 'Malawi','area_code' => '265','name_zh' => '马拉维','pid' => '0'),
            array('id' => '134','region_name' => 'Malaysia','area_code' => '60','name_zh' => '马来西亚','pid' => '0'),
            array('id' => '135','region_name' => 'Maldives','area_code' => '960','name_zh' => '马尔代夫','pid' => '0'),
            array('id' => '136','region_name' => 'Mali','area_code' => '223','name_zh' => '马里','pid' => '0'),
            array('id' => '137','region_name' => 'Malta','area_code' => '356','name_zh' => '马耳他','pid' => '0'),
            array('id' => '138','region_name' => 'Marshall Islands','area_code' => '692','name_zh' => '马绍尔群岛','pid' => '0'),
            array('id' => '139','region_name' => 'Martinique','area_code' => '596','name_zh' => '马提尼克','pid' => '0'),
            array('id' => '140','region_name' => 'Mauritania','area_code' => '222','name_zh' => '毛里塔尼亚','pid' => '0'),
            array('id' => '141','region_name' => 'Mauritius','area_code' => '230','name_zh' => '毛里求斯','pid' => '0'),
            array('id' => '142','region_name' => 'Mayotte','area_code' => '262','name_zh' => '马约特','pid' => '0'),
            array('id' => '143','region_name' => 'Mexico','area_code' => '52','name_zh' => '墨西哥','pid' => '0'),
            array('id' => '144','region_name' => 'Micronesia','area_code' => '691','name_zh' => '密克罗尼西亚联邦','pid' => '0'),
            array('id' => '145','region_name' => 'Moldova','area_code' => '373','name_zh' => '摩尔多瓦','pid' => '0'),
            array('id' => '146','region_name' => 'Monaco','area_code' => '377','name_zh' => '摩纳哥','pid' => '0'),
            array('id' => '147','region_name' => 'Mongolia','area_code' => '976','name_zh' => '蒙古国','pid' => '0'),
            array('id' => '148','region_name' => 'Montenegro','area_code' => '382','name_zh' => '黑山','pid' => '0'),
            array('id' => '149','region_name' => 'Montserrat','area_code' => '1664','name_zh' => '蒙特塞拉特','pid' => '0'),
            array('id' => '150','region_name' => 'Morocco','area_code' => '212','name_zh' => '摩洛哥','pid' => '0'),
            array('id' => '151','region_name' => 'Mozambique','area_code' => '258','name_zh' => '莫桑比克','pid' => '0'),
            array('id' => '152','region_name' => 'Myanmar','area_code' => '95','name_zh' => '缅甸','pid' => '0'),
            array('id' => '153','region_name' => 'Namibia','area_code' => '264','name_zh' => '纳米比亚','pid' => '0'),
            array('id' => '154','region_name' => 'Nauru','area_code' => '674','name_zh' => '瑙鲁','pid' => '0'),
            array('id' => '155','region_name' => 'Nepal','area_code' => '977','name_zh' => '尼泊尔','pid' => '0'),
            array('id' => '156','region_name' => 'Netherlands','area_code' => '31','name_zh' => '荷兰','pid' => '0'),
            array('id' => '157','region_name' => 'New Caledonia','area_code' => '687','name_zh' => '新喀里多尼亚','pid' => '0'),
            array('id' => '158','region_name' => 'New Zealand','area_code' => '64','name_zh' => '新西兰','pid' => '0'),
            array('id' => '159','region_name' => 'Nicaragua','area_code' => '505','name_zh' => '尼加拉瓜','pid' => '0'),
            array('id' => '160','region_name' => 'Niger','area_code' => '227','name_zh' => '尼日尔','pid' => '0'),
            array('id' => '161','region_name' => 'Nigeria','area_code' => '234','name_zh' => '尼日利亚','pid' => '0'),
            array('id' => '162','region_name' => 'Niue','area_code' => '683','name_zh' => '纽埃','pid' => '0'),
            array('id' => '163','region_name' => 'Norfolk Island','area_code' => '672','name_zh' => '诺福克岛','pid' => '0'),
            array('id' => '164','region_name' => 'Northern Mariana Islands','area_code' => '1670','name_zh' => '北马里亚纳群岛','pid' => '0'),
            array('id' => '165','region_name' => 'Norway','area_code' => '47','name_zh' => '挪威','pid' => '0'),
            array('id' => '166','region_name' => 'Oman','area_code' => '968','name_zh' => '阿曼','pid' => '0'),
            array('id' => '167','region_name' => 'Pakistan','area_code' => '92','name_zh' => '巴基斯坦','pid' => '0'),
            array('id' => '168','region_name' => 'Palau','area_code' => '680','name_zh' => '帕劳','pid' => '0'),
            array('id' => '169','region_name' => 'Palestine, State of','area_code' => '970','name_zh' => '巴勒斯坦','pid' => '0'),
            array('id' => '170','region_name' => 'Panama','area_code' => '507','name_zh' => '巴拿马','pid' => '0'),
            array('id' => '171','region_name' => 'Papua New Guinea','area_code' => '675','name_zh' => '巴布亚新几内亚','pid' => '0'),
            array('id' => '172','region_name' => 'Paraguay','area_code' => '595','name_zh' => '巴拉圭','pid' => '0'),
            array('id' => '173','region_name' => 'Peru','area_code' => '51','name_zh' => '秘鲁','pid' => '0'),
            array('id' => '174','region_name' => 'Philippines','area_code' => '63','name_zh' => '菲律宾','pid' => '0'),
            array('id' => '175','region_name' => 'Pitcairn','area_code' => '64','name_zh' => '皮特凯恩群岛','pid' => '0'),
            array('id' => '176','region_name' => 'Poland','area_code' => '48','name_zh' => '波兰','pid' => '0'),
            array('id' => '177','region_name' => 'Portugal','area_code' => '351','name_zh' => '葡萄牙','pid' => '0'),
            array('id' => '178','region_name' => 'Puerto Rico','area_code' => '1787','name_zh' => '波多黎各','pid' => '0'),
            array('id' => '179','region_name' => 'Qatar','area_code' => '974','name_zh' => '卡塔尔','pid' => '0'),
            array('id' => '180','region_name' => 'Réunion','area_code' => '262','name_zh' => '留尼汪','pid' => '0'),
            array('id' => '181','region_name' => 'Romania','area_code' => '40','name_zh' => '罗马尼亚','pid' => '0'),
            array('id' => '182','region_name' => 'Russia','area_code' => '7','name_zh' => '俄罗斯','pid' => '0'),
            array('id' => '183','region_name' => 'Rwanda','area_code' => '250','name_zh' => '卢旺达','pid' => '0'),
            array('id' => '184','region_name' => 'Saint Barthélemy','area_code' => '590','name_zh' => '圣巴泰勒米','pid' => '0'),
            array('id' => '185','region_name' => 'Saint Helena','area_code' => '290','name_zh' => '圣赫勒拿','pid' => '0'),
            array('id' => '186','region_name' => 'Saint Kitts and Nevis','area_code' => '1869','name_zh' => '圣基茨和尼维斯','pid' => '0'),
            array('id' => '187','region_name' => 'Saint Lucia','area_code' => '1758','name_zh' => '圣卢西亚','pid' => '0'),
            array('id' => '188','region_name' => 'Saint Martin','area_code' => '590','name_zh' => '法属圣马丁','pid' => '0'),
            array('id' => '189','region_name' => 'Saint Pierre and Miquelon','area_code' => '508','name_zh' => '圣皮埃尔和密克隆','pid' => '0'),
            array('id' => '190','region_name' => 'Saint Vincent and the Grenadines','area_code' => '1784','name_zh' => '圣文森特和格林纳丁斯','pid' => '0'),
            array('id' => '191','region_name' => 'Samoa','area_code' => '685','name_zh' => '萨摩亚','pid' => '0'),
            array('id' => '192','region_name' => 'San Marino','area_code' => '378','name_zh' => '圣马力诺','pid' => '0'),
            array('id' => '193','region_name' => 'São Tomé and Príncipe','area_code' => '239','name_zh' => '圣多美和普林西比','pid' => '0'),
            array('id' => '194','region_name' => 'Saudi Arabia','area_code' => '966','name_zh' => '沙特阿拉伯','pid' => '0'),
            array('id' => '195','region_name' => 'Senegal','area_code' => '221','name_zh' => '塞内加尔','pid' => '0'),
            array('id' => '196','region_name' => 'Serbia','area_code' => '381','name_zh' => '塞尔维亚','pid' => '0'),
            array('id' => '197','region_name' => 'Seychelles','area_code' => '248','name_zh' => '塞舌尔','pid' => '0'),
            array('id' => '198','region_name' => 'Sierra Leone','area_code' => '232','name_zh' => '塞拉利昂','pid' => '0'),
            array('id' => '199','region_name' => 'Singapore','area_code' => '65','name_zh' => '新加坡','pid' => '0'),
            array('id' => '200','region_name' => 'Sint Maarten','area_code' => '1721','name_zh' => '荷属圣马丁','pid' => '0'),
            array('id' => '201','region_name' => 'Slovakia','area_code' => '421','name_zh' => '斯洛伐克','pid' => '0'),
            array('id' => '202','region_name' => 'Slovenia','area_code' => '386','name_zh' => '斯洛文尼亚','pid' => '0'),
            array('id' => '203','region_name' => 'Solomon Islands','area_code' => '677','name_zh' => '所罗门群岛','pid' => '0'),
            array('id' => '204','region_name' => 'Somalia','area_code' => '252','name_zh' => '索马里','pid' => '0'),
            array('id' => '205','region_name' => 'South Africa','area_code' => '27','name_zh' => '南非','pid' => '0'),
            array('id' => '206','region_name' => 'South Georgia and the South Sandwich Isl','area_code' => '500','name_zh' => '南乔治亚和南桑威奇群岛','pid' => '0'),
            array('id' => '207','region_name' => 'South Sudan','area_code' => '211','name_zh' => '南苏丹','pid' => '0'),
            array('id' => '208','region_name' => 'Spain','area_code' => '34','name_zh' => '西班牙','pid' => '0'),
            array('id' => '209','region_name' => 'Sri Lanka','area_code' => '94','name_zh' => '斯里兰卡','pid' => '0'),
            array('id' => '210','region_name' => 'Sudan','area_code' => '249','name_zh' => '苏丹','pid' => '0'),
            array('id' => '211','region_name' => 'Suriname','area_code' => '597','name_zh' => '苏里南','pid' => '0'),
            array('id' => '212','region_name' => 'Svalbard','area_code' => '4779','name_zh' => '斯瓦尔巴群岛和扬马延岛','pid' => '0'),
            array('id' => '213','region_name' => 'Swaziland','area_code' => '268','name_zh' => '斯威士兰','pid' => '0'),
            array('id' => '214','region_name' => 'Sweden','area_code' => '46','name_zh' => '瑞典','pid' => '0'),
            array('id' => '215','region_name' => 'Switzerland','area_code' => '41','name_zh' => '瑞士','pid' => '0'),
            array('id' => '216','region_name' => 'Syria','area_code' => '963','name_zh' => '叙利亚','pid' => '0'),
            array('id' => '217','region_name' => 'Taiwan','area_code' => '886','name_zh' => '台湾','pid' => '0'),
            array('id' => '218','region_name' => 'Tajikistan','area_code' => '992','name_zh' => '塔吉克斯坦','pid' => '0'),
            array('id' => '219','region_name' => 'Tanzania','area_code' => '255','name_zh' => '坦桑尼亚','pid' => '0'),
            array('id' => '220','region_name' => 'Thailand','area_code' => '66','name_zh' => '泰国','pid' => '0'),
            array('id' => '221','region_name' => 'Timor-Leste','area_code' => '670','name_zh' => '东帝汶','pid' => '0'),
            array('id' => '222','region_name' => 'Togo','area_code' => '228','name_zh' => '多哥','pid' => '0'),
            array('id' => '223','region_name' => 'Tokelau','area_code' => '690','name_zh' => '托克劳','pid' => '0'),
            array('id' => '224','region_name' => 'Tonga','area_code' => '676','name_zh' => '汤加','pid' => '0'),
            array('id' => '225','region_name' => 'Trinidad and Tobago','area_code' => '1868','name_zh' => '特立尼达','pid' => '0'),
            array('id' => '226','region_name' => 'Tunisia','area_code' => '216','name_zh' => '突尼斯','pid' => '0'),
            array('id' => '227','region_name' => 'Turkey','area_code' => '90','name_zh' => '土耳其','pid' => '0'),
            array('id' => '228','region_name' => 'Turkmenistan','area_code' => '993','name_zh' => '土库曼斯坦','pid' => '0'),
            array('id' => '229','region_name' => 'Turks and Caicos Islands','area_code' => '1649','name_zh' => '特克斯和凯科斯群岛','pid' => '0'),
            array('id' => '230','region_name' => 'Tuvalu','area_code' => '688','name_zh' => '图瓦卢','pid' => '0'),
            array('id' => '231','region_name' => 'Uganda','area_code' => '256','name_zh' => '乌干达','pid' => '0'),
            array('id' => '232','region_name' => 'Ukraine','area_code' => '380','name_zh' => '乌克兰','pid' => '0'),
            array('id' => '233','region_name' => 'United Arab Emirates','area_code' => '971','name_zh' => '阿联酋','pid' => '0'),
            array('id' => '234','region_name' => 'United Kingdom','area_code' => '44','name_zh' => '英国','pid' => '0'),
            array('id' => '235','region_name' => 'United States','area_code' => '1','name_zh' => '美国','pid' => '0'),
            array('id' => '236','region_name' => 'Uruguay','area_code' => '598','name_zh' => '乌拉圭','pid' => '0'),
            array('id' => '237','region_name' => 'Uzbekistan','area_code' => '998','name_zh' => '乌兹别克斯坦','pid' => '0'),
            array('id' => '238','region_name' => 'Vanuatu','area_code' => '678','name_zh' => '瓦努阿图','pid' => '0'),
            array('id' => '239','region_name' => 'Venezuela','area_code' => '58','name_zh' => '委内瑞拉','pid' => '0'),
            array('id' => '240','region_name' => 'Vietnam','area_code' => '84','name_zh' => '越南','pid' => '0'),
            array('id' => '241','region_name' => 'British Virgin Islands','area_code' => '1284','name_zh' => '英属维尔京群岛','pid' => '0'),
            array('id' => '242','region_name' => 'US Virgin Islands','area_code' => '1340','name_zh' => '美属维京群岛','pid' => '0'),
            array('id' => '243','region_name' => 'Wallis and Futuna','area_code' => '681','name_zh' => '瓦利斯和富图纳','pid' => '0'),
            array('id' => '244','region_name' => 'Yemen','area_code' => '967','name_zh' => '也门','pid' => '0'),
            array('id' => '245','region_name' => 'Zambia','area_code' => '260','name_zh' => '赞比亚','pid' => '0'),
            array('id' => '246','region_name' => 'Zimbabwe','area_code' => '263','name_zh' => '津巴布韦','pid' => '0')
        );

    }


}