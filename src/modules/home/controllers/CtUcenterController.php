<?php

namespace app\modules\home\controllers;

use app\modules\home\models\Region;
use nickcv\encrypter\components\Encrypter;
use Ucenter\Library\AES;
use Ucenter\Ucenter;
use app\helpers\myhelper;
use app\Library\Net\IpLocation;
use app\Library\Mlog;
use app\modules\home\models\Getpassword;

//地產大亨
class CtUcenterController extends CommonController
{
    public $ucenter = NULL;
    private $result = array('code' => 0, 'error' => '', 'data' => array());

    public function init()
    {
        parent::init();
        preg_match("/^(http[s]?:\/\/)?([^\/]+)/i", isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', $matches);
        if (isset($matches[0])) {
            header('Access-Control-Allow-Origin:' . $matches[0]);
        }
        header("Access-Control-Allow-Credentials: true");
        $this->ucenter = new Ucenter(['domain' => DOMAIN, 'env' => ENVUC]);
    }

    public function actionRegister($email = '', $password = '', $adv_key = '')
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $email = $email ? $email : \Yii::$app->request->post('email', '');
        $password = $password ? $password : \Yii::$app->request->post('password', '');
        $adv_key = $adv_key ? $adv_key : \Yii::$app->request->post('adv_key', '');
        $clientip = myhelper::get_client_ip();

        $returnData = $this->actionCheckuser($email, 'entity');

        if ($returnData['data'] == true || !myhelper::grepcheck($email)) {
            $this->result['code'] = 1000;
            return $this->result;
        }
        if (!$password) {
            $this->result['code'] = 1001;
            return $this->result;
        }

        $params = array();
        $params['account'] = $email;
        $params['password'] = $password;
        $params['ip'] = $clientip;
        $params['ads_key'] = $adv_key;

        //调用用户中心
        $returnData = $this->ucenter->Register()->register($params);

        //发送日志
        $sendmessage = new Mlog();
        $sendmessage->inlog('ucenter', 'register_data', $returnData['data']);
        if (empty($returnData['data'])) {
            $sendmessage->inlog('ucenter', 'register_server', $_SERVER);
        }

        $mainref = !isset($adv_key) ? '' : 'adv';

        if (isset($returnData['data']['uid'])) {
            $http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
            $http_referer = substr($http_referer, 0, 1000);

            $message = array(
                'timestamp' => time(),
                'tpuid' => '',
                'uid' => $returnData['data']['uid'],
                'mainref' => $mainref,
                'subref' => $adv_key,
                'entry' => 'gw',
                'count' => '1',
                'ip' => $clientip,
                'step' => '3',
                'appid' => '4',
                'ref' => $http_referer,
            );
            $sendmessage->Send($message, 'adv');

            $reg_message = [
                'projectid'=> '1',
                'email'=> $email,
                'uid' => $returnData['data']['uid'],
                'mainref' => $http_referer,
                'subref' => '',
                'clientip' => myhelper::get_client_ip(),
                'deviceid' => \Yii::$app->request->getUserAgent(),
                'timezone' => '',
                'timestamp' => time(),
                'testuser' => '0',
                'adv'=> $adv_key,
                'param1'=>'',
                'param2'=>'',
                'param3'=>''
            ];
            $sendmessage->Send($reg_message, 'preg');
        }

        $this->sessions['userinfo'] = $returnData['data'];

        //注册后生成所用的cookie信息
        $this->createcookie($returnData['data']);

        if ($returnData['code']) {
            $this->result['code'] = 1002;
            $this->result['data'] = $returnData;
            return $this->result;
        }

        $this->cookies_2->add(new \yii\web\Cookie([
            'name' => 'last_update_time',
            'value' => time(),
            'domain' => \YII::$app->params['COOKIE_DOMAIN'],
        ]));
        $this->sessions['user_data'] = null;

        //设置国家
        $this->setCountry($returnData['data']['token']);

        //RETURN
        $this->result['data'] = $returnData['data'];
        $this->result['ct_data'] = base64_encode('user_id:' . $returnData['data']['uid'] . '&username:' . (empty($returnData['data']['username']) ? $returnData['data']['account'] : $returnData['data']['username']) . '&token:' . $returnData['data']['token']);

        return $this->result;
    }

    public function actionLogin()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $email = \Yii::$app->request->get('email', '');
        $password = \Yii::$app->request->get('password', '');
        $remeber_time = \Yii::$app->request->get('remeber_time', '');
        if (!myhelper::grepcheck($email)) {
            $this->result['code'] = 1005;
            return $this->result;
        }
        if (!$password) {
            $this->result['code'] = 1006;
            return $this->result;
        }

        //调用用户中心
        $returnData = $this->ucenter->Login()->loginGW($email, $password, $remeber_time);
        if (!$returnData) {
            $this->result['code'] = 1007;
            return $this->result;
        }
        if ($returnData['code']) {
            $this->result['code'] = 1008;
            $this->result['data'] = $returnData;
            return $this->result;
        }
        $this->sessions['userinfo'] = $returnData['data'];
        $data = $returnData['data'];

        //发送登录日志
        $http_referer = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
        $http_referer = substr($http_referer, 0, 1000);
        $sendmessage = new Mlog();
        $login_message = [
            'projectid'=> '1',
            'email'=> $email,
            'uid' => $returnData['data']['uid'],
            'mainref' => $http_referer,
            'subref' => '',
            'clientip' => myhelper::get_client_ip(),
            'deviceid' => \Yii::$app->request->getUserAgent(),
            'timezone' => '',
            'timestamp' => time(),
            'param1'=>'',
            'param2'=>'',
            'param3'=>''
        ];
        $sendmessage->Send($login_message, 'plogin');

        //登录成功后生成所用的cookie信息
        $this->createcookie($data, $remeber_time);
        $this->cookies_2->add(new \yii\web\Cookie([
            'name' => 'last_update_time',
            'value' => time(),
            'domain' => \YII::$app->params['COOKIE_DOMAIN'],
        ]));
        $this->sessions['user_data'] = null;

        //RETURN
        $this->result['data'] = $data;

        $this->result['ct_data'] = base64_encode('user_id:' . $returnData['data']['uid'] . '&username:' . (empty($returnData['data']['username']) ? $returnData['data']['account'] : $returnData['data']['username']) . '&token:' . $returnData['data']['token']);

        return $this->result;
    }


    /* 登录-FB */
    public function actionLoginfb()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!$app_type = \Yii::$app->request->post('app_type', '')) {
            $this->result['code'] = 1010;
            $this->result['error'] = 'app_type is empty';
            return $this->result;
        }
        if (!$token = \Yii::$app->request->post('token', '')) {
            $this->result['code'] = 1011;
            $this->result['error'] = 'token is empty';
            return $this->result;
        }
        

        $adv_tag = \Yii::$app->request->post('adv_tag', '');

        //调用用户中心
        $returnData = $this->ucenter->Login()->loginFB($token, $app_type, $adv_tag);
        if (!$returnData) {
            $this->result['code'] = 1012;
            $this->result['error'] = 'error';
            return $this->result;
        }
        if ($returnData['code']) {
            $this->result['code'] = 1013;
            $this->result['data'] = $returnData;
            $this->result['error'] = $returnData['error'];
            return $this->result;
        }

        $this->sessions['userinfo'] = $returnData['data'];
        $data = isset($returnData['data']) ? $returnData['data'] : '';

        //打印fb返回信息
        $mlog = new Mlog();
        $mlog->inlog('ucenter', 'loginfb_data', $data);

        $lasttime = isset($data['lasttime']) ? $data['lasttime'] : '';
        $createtime = isset($data['createtime']) ? $data['createtime'] : '';
        $regdate = isset($data['regdate']) ? $data['regdate'] : '';
        $third_id = isset($data['third_id']) ? $data['third_id'] : '';

        if (isset($returnData['data']['uid']) && !empty($returnData['data']['uid']) && !empty($lasttime) && !empty($regdate) && ($lasttime == $regdate)) {
            //发送日志
            $sendmessage = new Mlog();
            $sendmessage->inlog('ucenter', 'lasttime-createtime', $lasttime . '-' . $regdate);

            $mainref = !isset($adv_tag) ? '' : 'adv';

            $http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
            $http_referer = substr($http_referer, 0, 1000);

            $message = array(
                'timestamp' => time(),
                'tpuid' => $third_id,
                'uid' => $returnData['data']['uid'],
                'mainref' => $mainref,
                'subref' => $adv_tag,
                'entry' => 'gw',
                'count' => '1',
                'ip' => myhelper::get_client_ip(),
                'step' => '3',
                'appid' => '4',
                'ref' => $http_referer,
            );
            $sendmessage->Send($message, 'adv');
        }

        //登录成功后生成所用的cookie信息
        //$this->createcookie($data);
        //设置国家
        $userData = (new \Ucenter\User(['env' => ENV]))->userinfo(null, 'country');
        if ($userData && empty($userData['data']['country'])) {
            $this->setCountry($returnData['data']['token']);
        }
        /*$this->cookies_2->add(new \yii\web\Cookie([
            'name' => 'last_update_time',
            'value' => time(),
            'domain'=>\YII::$app->params['COOKIE_DOMAIN'],
        ]));
        $this->sessions['user_data'] = null;*/

        //RETURN
        $this->result['data'] = $data;
        $this->result['ct_data'] = base64_encode('user_id:' . $returnData['data']['uid'] . '&username:' . (empty($returnData['data']['username']) ? $returnData['data']['account'] : $returnData['data']['username']) . '&token:' . $returnData['data']['token']);
        $this->result['user_data'] = $userData;
        return $this->result;
    }


    /* 登录-GOOGLE */
    public function actionLogingg()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!$code = \Yii::$app->request->post('code', '')) {
            $this->result['code'] = 1015;
            $this->result['error'] = 'code is empty';
            return $this->result;
        }

        $adv_tag = \YII::$app->request->post('adv_tag', '');

        //调用用户中心
        $returnData = $this->ucenter->Login()->loginGG($code, 'mutantbox', $adv_tag);
        if (!$returnData) {
            $this->result['code'] = 1016;
            $this->result['error'] = 'error';
            return $this->result;
        }
        if ($returnData['code']) {
            $this->result['code'] = 1017;
            $this->result['data'] = $returnData;
            $this->result['error'] = $returnData['error'];
            return $this->result;
        }

        $this->sessions['userinfo'] = $returnData['data'];
        $data = isset($returnData['data']) ? $returnData['data'] : '';

        //打印gg返回信息
        $mlog = new Mlog();
        $mlog->inlog('ucenter', 'logingg_data', $data);

        $lasttime = isset($data['lasttime']) ? $data['lasttime'] : '';
        $createtime = isset($data['createtime']) ? $data['createtime'] : '';
        $regdate = isset($data['regdate']) ? $data['regdate'] : '';
        $third_id = isset($data['third_id']) ? $data['third_id'] : '';

        if (isset($returnData['data']['uid']) && !empty($returnData['data']['uid']) && !empty($lasttime) && !empty($regdate) && ($lasttime == $regdate)) {
            //发送日志
            $sendmessage = new Mlog();
            $sendmessage->inlog('ucenter', 'lasttime-createtime', $lasttime . '-' . $regdate);

            $mainref = !isset($adv_tag) ? '' : 'adv';

            $http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
            $http_referer = substr($http_referer, 0, 1000);

            $message = array(
                'timestamp' => time(),
                'tpuid' => '',
                'uid' => $returnData['data']['uid'],
                'mainref' => $mainref,
                'subref' => $adv_tag,
                'entry' => 'gw',
                'count' => '1',
                'ip' => myhelper::get_client_ip(),
                'step' => '3',
                'appid' => '4',
                'ref' => $http_referer,
            );
            $sendmessage->Send($message, 'adv');
        }

        //登录成功后生成所用的cookie信息
        $this->createcookie($data);

        //设置国家
        $userData = (new \Ucenter\User(['env' => ENV]))->userinfo(null, 'country');
        if ($userData && empty($userData['data']['country'])) {
            $this->setCountry($returnData['data']['token']);
        }

        $this->cookies_2->add(new \yii\web\Cookie([
            'name' => 'last_update_time',
            'value' => time(),
            'domain' => \YII::$app->params['COOKIE_DOMAIN'],
        ]));
        $this->sessions['user_data'] = null;

        $this->result['data'] = $returnData['data'];
        $this->result['ct_data'] = base64_encode('user_id:' . $returnData['data']['uid'] . '&username:' . (empty($returnData['data']['username']) ? $returnData['data']['account'] : $returnData['data']['username']) . '&token:' . $returnData['data']['token']);

        return $this->result;
    }


    /**
     * 用户退出登录
     *2016年5月5日 下午4:24:06
     */
    public function actionLogout()
    {
        $token = $this->sessions['userinfo']['token'];
        if (isset($token)) {
            $this->ucenter->User()->logout($token);
        }
        $this->sessions['userinfo'] = null;
        $this->cookies_2->add(new \yii\web\Cookie([
            'name' => '_ttl',
            'value' => null,
            'domain' => \YII::$app->params['COOKIE_DOMAIN'],
            'expire' => time() - 1
        ]));
        $this->cookies_2->add(new \yii\web\Cookie([
            'name' => 'remember_me_token',
            'value' => null,
            'domain' => \YII::$app->params['COOKIE_DOMAIN'],
            'expire' => time() - 1
        ]));
        $this->cookies_2->add(new \yii\web\Cookie([
            'name' => 'user_auth',
            'value' => null,
            'domain' => \YII::$app->params['COOKIE_DOMAIN'],
            'expire' => time() - 1
        ]));
        $this->cookies_2->add(new \yii\web\Cookie([
            'name' => 'user_region',
            'value' => null,
            'domain' => \YII::$app->params['COOKIE_DOMAIN'],
            'expire' => time() - 1
        ]));
        $this->cookies_2->add(new \yii\web\Cookie([
            'name' => 'user_auth_sign',
            'value' => null,
            'domain' => \YII::$app->params['COOKIE_DOMAIN'],
            'expire' => time() - 1
        ]));
        $this->cookies_2->add(new \yii\web\Cookie([
            'name' => 'lastttl',
            'value' => null,
            'domain' => \YII::$app->params['COOKIE_DOMAIN'],
            'expire' => time() - 1
        ]));
        if (isset($_SERVER['HTTP_REFERER'])) {
            $this->redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->redirect('/');
        }
    }


    public function actionCheckuser($email = '', $type = '')
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $email = $email ? $email : \Yii::$app->request->post('email', '');
        $type = $type ? $type : \Yii::$app->request->post('type', '');
        $returnData = $this->ucenter->User()->account($email);
        if (!$returnData) {
            $this->result['code'] = 1025;
            $this->result['error'] = '1025';
            $this->result['msg'] = \Yii::t('common', 'validEmail');
            return $this->result;
        }
        if ($returnData['code']) {
            $this->result['code'] = 1026;
            $this->result['error'] = '1026';
            $this->result['msg'] = \Yii::t('common', 'validEmail');
            return $this->result;
        }

        $data = $returnData['data'];

        if ($data) {
            if ($type == "checked") {
                return true;
            } elseif ($type == 'entity') {
                return $returnData;
            } else {
                return false;
            }
        } else {
            if ($type == "checked") {
                return false;
            } elseif ($type == 'entity') {
                return $returnData;
            } else {
                return true;
            }
        }
    }

    /**
     * 用户忘记密码
     *2016年5月28日 下午3:48:14
     * @param string $email
     */
    public function actionGetpassword()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $email = \YII::$app->request->post('email', '');
        if (myhelper::grepcheck($email)) {
            $checkUser = $this->actionCheckuser($email, 'entity');
            if ($checkUser['data'] == 1) {
                $to = $email;
                $subject = "Password Reset";
                $code = myhelper::get_resetpwd_code($email);

                if (!$code) {
                    $ajax_data['error'] = 2;
                    $ajax_data['msg'] = "User does not exist.";
                    return $ajax_data;
                }

                $callback_url = \YII::$app->params['MY_URL']['WEB'] . 'index/index?email=' . $email . '&code=' . $code;
                $body = 'Dear ' . $email . ',<br /><br />
                            You are receiving this email because you requested a new password for your MutantBox account.
                            Please click the link below to reset your password.<br /><br />
                            <a href="' . $callback_url . '">' . $callback_url . '</a><br /><br />
                            If you have received this message in error, please disregard it.
                            <br /><br />
                            The MutantBox Team';
                $send_result = myhelper::sendEmail($subject, $to, $body);
                if ($send_result) {
                    $ajax_data['error'] = 0;
                    $ajax_data['get_password_code'] = $code;
                    $this->sessions['get_password_code'] = $code;
                    $ajax_data['msg'] = "Password recovery instructions have been sent to your email.<br>*If you don't receive this email, please check your junk mail folder.";
                    return $ajax_data;
                } else {
                    $ajax_data['error'] = 1;
                    $ajax_data['msg'] = $send_result;
                    return $ajax_data;
                }
            } else {
                $ajax_data['error'] = 2;
                $ajax_data['msg'] = "User does not exist.";
                return $ajax_data;
            }
        } else {
            $ajax_data['error'] = 2;
            $ajax_data['msg'] = "User does not exist.";
            return $ajax_data;
        }
    }

    /**
     * 用户重设密码
     *2016年5月28日 下午5:08:16
     */
    public function actionRepassword()
    {
        if (\Yii::$app->request->isAjax) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $password = \Yii::$app->request->post('password', '');
            $email = \Yii::$app->request->post('email', '');
            $res = $this->resetpwd($email, $password);//通过接口修改用户密码
            if ($res === false) {
                $ajax_data['error'] = 1;
                $ajax_data['msg'] = "Please request another password recovery email.";
                return $ajax_data;
            } else {
                $this->sessions['get_password_id'] = null;
                $ajax_data['error'] = 0;
                $ajax_data['msg'] = "Password successfully reset.";
                return $ajax_data;
            }
        } else {
            return $this->redirect(['/404']);
        }
    }

    /**
     * 通过接口重置密码
     *2016年6月1日 上午10:08:33
     * @param string $email
     * @param string $password
     */
    private function resetapi($email = '', $password = '')
    {
        $data = false;
        if ($email && $password) {
            $key = \YII::$app->params['TOKEN']['ucentkey'];
            $ucenter = \YII::$app->params['MY_URL']['UCENTER'];
            $url = $ucenter . '/api/resetpw';

            $sign = md5('00001' . $email . $password . $key);
            $params = array(
                'email' => $email,
                'password' => $password,
                'sid' => '00001',
                'sign' => $sign,
            );
            $returnData = myhelper::http($url, $params, 'POST');
            if ($returnData) {
                $returnArr = json_decode($returnData, true);
                $state = $returnArr['state'];
                if ($state == 0)
                    $data = true;
            }
        }
        return $data;
    }

    private function resetpwd($email = '', $password = '')
    {
        $data = false;
        if ($email && $password) {
            $key = \YII::$app->params['TOKEN']['ucentkey'];
            $ucenter = \YII::$app->params['MY_URL']['UCENTER'];
            $url = $ucenter . '/api/reset-password';

            $post = $params = array(
                'timestamp' => time(),
                'email' => $email,
                'password' => $password,
                'sid' => '00001',
            );
            sort($params, SORT_STRING);
            $sign = md5($key . implode("", $params));
            $post['signature'] = $sign;
            $returnData = myhelper::http($url, $post, 'POST');

            if ($returnData) {
                $returnArr = json_decode($returnData, true);
                $state = $returnArr['state'];
                if ($state == 0)
                    $data = true;
            }
        }
        return $data;
    }


    public function actionCheckpwd()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $email = \Yii::$app->request->post('email', '');
        $password = \Yii::$app->request->post('password', '');
        $returnData = $this->ucenter->Login()->loginGW($email, $password);
        if (!$returnData) {
            $this->result['code'] = 1028;
            return false;
        }
        if ($returnData['code']) {
            $this->result['code'] = 1029;
            $this->result['data'] = $returnData;
            return false;
        }
        $data = $returnData['data'];

        if ($data) {
            return true;
        } else {
            return false;
        }
    }


    private function createcookie($ucenter)
    {
        //cookie保存时长
        $expire = 60 * 60 * 24;//1 day

        $user_ip = myhelper::get_client_ip();
        if (isset($ucenter['ip']) && !empty($ucenter['ip'])) {
            $user_ip = $ucenter['ip'];
            unset($ucenter['ip']);
        }
        $Ip = new IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
        $region_result = $Ip->getlocation($user_ip); // 获取某个IP地址所在的位置

        if (!empty($region_result['country'])) {
            $where['name_zh'] = $region_result['country'];
        } else {
            $where['name_zh'] = "美国";
        }

        if ((new \yii\db\Query())->from(Region::tableName())->where($where)->count() > 0) {
            $user_region = (new \yii\db\Query())->select("id,region_name,area_code,name_zh")->from(Region::tableName())->where($where)->one();
        } else {
            $where['name_zh'] = "美国";
            $user_region = (new \yii\db\Query())->select("id,region_name,area_code,name_zh")->from(Region::tableName())->where($where)->one();
        }

        $user_region['ip'] = $user_ip;

        $auth = array(
            'uid' => $ucenter['uid'],
            'username' => !empty($ucenter['username']) ? $ucenter['username'] : $ucenter['account'],
            'thumb_avatar' => $ucenter['avatar'],
            'email' => $ucenter['account'],
            'last_login_time' => isset($ucenter['lasttime']) ? $ucenter['lasttime'] : time(),
        );

        $this->cookies_2->add(new \yii\web\Cookie([
            'name' => 'user_region',
            'value' => $user_region,
            'expire' => time() + $expire,
            'domain' => \YII::$app->params['COOKIE_DOMAIN'],
        ]));
        $this->cookies_2->add(new \yii\web\Cookie([
            'name' => 'user_auth',
            'value' => $auth,
            'expire' => time() + $expire,
            'domain' => \YII::$app->params['COOKIE_DOMAIN'],
        ]));
        $this->cookies_2->add(new \yii\web\Cookie([
            'name' => 'user_auth_sign',
            'value' => myhelper::data_auth_sign($auth),
            'expire' => time() + $expire,
            'domain' => \YII::$app->params['COOKIE_DOMAIN'],
        ]));
    }


    /**
     * @param $token ：注册后返回的token
     * set国家:对应数据库的ID
     */
    private function setCountry($token)
    {
        //得到国家对应数据库的ID
        $country_name = myhelper::getLocationInfoByIp();
        $area_code = (new \yii\db\Query())->select("id")->from(Region::tableName())->where('region_name like \'%' . $country_name . '%\'')->one();
        if (!empty($area_code) && $area_code['id']) {
            (new \Ucenter\User(['env' => ENV, 'domain' => DOMAIN]))->updateuser($token, array(
                'country' => $area_code['id'],
            ));
        }
    }

    //设置以太cookie
    public function actionSetWebCookie()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        header('P3P: CP="NOI DEV PSA PSD IVA PVD OTP OUR OTR IND OTC"');
        $ttl = \YII::$app->request->post('_ttl', '');
        setcookie('_ttl', $ttl, time() + 86400, '/', DOMAIN);

        //设置该域名下的cookie
        $sendmessage = new Mlog();
        $sendmessage->inlog('ucenter', 'actionSetWebCookie', $ttl);
        return ['ok' => 0, 'data' => []];
    }

    //设置交易密码
    public function actionSetPaypwd()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $pwd = \Yii::$app->request->post('password','');
        $pwd2 = \Yii::$app->request->post('confirm_password','');
        $token = \Yii::$app->request->post('token','');
        $model = new Ucenter();
        $data = $model->Auth()->verifyToken($token);
        if (!$data) {
            return ['code'=>-1,'message'=>'token is error'];
        }
        if($pwd !== $pwd2){
            return ['code'=>-2,'message'=>'password error'];
        }

        $ucenter = \YII::$app->params['MY_URL']['WALLET'];
        $url = $ucenter.'/mapping/set';
        $params = [
            'project' => 'cryptoterritories',
            'platform' => 'mutantbox',
            'source' => 'player',
            'userid' => $data['uid'],
            'password' => self::encryptPwd($pwd),
            'email' => $data['account'],
            'tmp' => (string)time(),
        ];
        $params['sign'] = $model->User()->genrateSign($params);
        $json = myhelper::http($url, $params, 'POST');

        return json_decode($json,true);
    }

    public function actionChangePaypwd()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $old_pwd = \Yii::$app->request->post('old_pwd','');
        $pwd1 = \Yii::$app->request->post('new_pwd1','');
        $pwd2 = \Yii::$app->request->post('new_pwd2','');
        $token = \Yii::$app->request->post('token','');

        $model = new Ucenter();
        $data = $model->Auth()->verifyToken($token);
        if (!$data) {
            return ['code'=>-1,'message'=>'token is error'];
        }
        if($pwd1 !== $pwd2){
            return ['code'=>-2,'message'=>'password error'];
        }

        $ucenter = \YII::$app->params['MY_URL']['WALLET'];
        $url = $ucenter.'/mapping/update';
        $params = [
            'project' => 'cryptoterritories',
            'platform' => 'mutantbox',
            'source' => 'player',
            'userid' => $data['uid'],
            'password_old' => self::encryptPwd($old_pwd),
            'password_new' => self::encryptPwd($pwd1),
            'tmp' => (string)time(),
        ];
        $test = $params;
        sort($test,SORT_STRING);
        $params['sign'] = $model->User()->genrateSign($params);
        $json = myhelper::http($url, $params, 'POST');
        //var_dump($params,$test,$json);exit;

        return json_decode($json,true);
    }

    public function actionGetPaypwd()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $code = \Yii::$app->request->post('code','');
        $pwd1 = \Yii::$app->request->post('pwd1','');
        $pwd2 = \Yii::$app->request->post('pwd2','');
        $token = \Yii::$app->request->post('token','');

        $model = new Ucenter();
        $data = $model->Auth()->verifyToken($token);
        if (!$data) {
            return ['code'=>-1,'message'=>'token is error'];
        }
        if(empty($code)){
            return ['code'=>-3,'message'=>'code error'];
        }
        if($pwd1 !== $pwd2){
            return ['code'=>-2,'message'=>'password error'];
        }

        $ucenter = \YII::$app->params['MY_URL']['WALLET'];
        $url = $ucenter.'/mapping/reset';
        $params = [
            'project' => 'cryptoterritories',
            'platform' => 'mutantbox',
            'source' => 'player',
            'userid' => $data['uid'],
            'code' => $code,
            'password_new' => self::encryptPwd($pwd1),
            'tmp' => (string)time(),
        ];
        $params['sign'] = $model->User()->genrateSign($params);
        $json = myhelper::http($url, $params, 'POST');

        return json_decode($json,true);
    }

    public function actionSendEmail()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $email = \Yii::$app->request->post('email','');
        $token = \Yii::$app->request->post('token','');

        $model = new Ucenter();
        $data = $model->Auth()->verifyToken($token);
        if (!$data) {
            return ['code'=>-1,'message'=>'token is error'];
        }

        //获取code
        $ucenter = \YII::$app->params['MY_URL']['WALLET'];
        $url = $ucenter.'/mapping/forget';
        $params = [
            'project' => 'cryptoterritories',
            'platform' => 'mutantbox',
            'source' => 'player',
            'userid' => $data['uid'],
            'tmp' => (string)time(),
        ];
        $params['sign'] = $model->User()->genrateSign($params);
        //var_dump($params['sign']);exit;
        $json = myhelper::http($url, $params, 'POST');
        $arr = json_decode($json,true);
        if(0 == $arr['code']){
            $to = $email;
            $subject = "Password Reset";
            $body = 'Dear ' . $data['account'] . ',<br /><br />
                            You are receiving this email because you requested a new pay password for your MutantBox account.
                            Code is '.$arr['data']['code'].'.<br /><br />
                            If you have received this message in error, please disregard it.
                            <br /><br />
                            The MutantBox Team';
            $send_result =  myhelper::sendEmail($subject, $to, $body);
            if ($send_result) {
                $ajax_data['error'] = 0;
                $ajax_data['message'] = "Password recovery instructions have been sent to your email.<br>*If you don't receive this email, please check your junk mail folder.";
                return $ajax_data;
            } else {
                $ajax_data['error'] = 1;
                $ajax_data['message'] = $send_result;
                return $ajax_data;
            }
        }else{
            $ajax_data['error'] = 2;
            $ajax_data['message'] = 'error';
            return $ajax_data;
        }

    }

    private function encryptPwd($pwd)
    {
        $encryter = new Encrypter();
        $encryter->setGlobalPassword('0323a44e5d1');
        $res = $encryter->encrypt($pwd);

        return $res;
    }

}
