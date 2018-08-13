<?php
namespace app\modules\home\controllers;
use app\Library\Mlog;
use app\Library\SendMessage;
use app\helpers\myhelper;
use app\Library\gameapi\Play;
use app\Library\gameapi\Server;
use Yii;
class GameController extends CommonController
{
    public function init()
    {
        parent::init();
        $this->view->params['meta_title']  = \YII::t('common','TitLiberatorsOnline');
        $this->view->params['keyword']  = "liberators online,liberators play now,play liberators free,mutantbox";
        $this->view->params['description'] = "Have a question about play liberators free? Want to suggest an idea about playing liberators ? Submit your query and contact the mutantbox.com support team. ";
    }

    public function actionRedirecturl()
    {
        $gameId = (int) Yii::$app->request->get("game_id", 4);

        //验证用户是否登录
        $uc = new \Ucenter\Ucenter(['domain' => DOMAIN, 'env' => ENVUC]);
        if (!$uinfo = $uc->Auth()->verify()) {
            $this->cookies_2->add(new \yii\web\Cookie([
                'name' => 'user_auth',
                'value' => NULL,
                'domain'=>\YII::$app->params['COOKIE_DOMAIN'],
            ]));
            $this->cookies_2->add(new \yii\web\Cookie([
                'name' => 'user_region',
                'value' => NULL,
                'domain'=>\YII::$app->params['COOKIE_DOMAIN'],
            ]));
            $this->cookies_2->add(new \yii\web\Cookie([
                'name' => 'user_auth_sign',
                'value' => NULL,
                'domain'=>\YII::$app->params['COOKIE_DOMAIN'],
            ]));
            //$this->error('User information to get failed, please re login.1003', C('MY_URL.LIBERATORS'));
            exit('User information to get failed, please re login.1003');
        }
        if ($uinfo['code']) {
            $this->cookies_2->add(new \yii\web\Cookie([
                'name' => 'user_auth',
                'value' => NULL,
                'domain'=>\YII::$app->params['COOKIE_DOMAIN'],
            ]));
            $this->cookies_2->add(new \yii\web\Cookie([
                'name' => 'user_region',
                'value' => NULL,
                'domain'=>\YII::$app->params['COOKIE_DOMAIN'],
            ]));
            $this->cookies_2->add(new \yii\web\Cookie([
                'name' => 'user_auth_sign',
                'value' => NULL,
                'domain'=>\YII::$app->params['COOKIE_DOMAIN'],
            ]));
            //$this->error('User information to get failed, please re login.1004', C('MY_URL.LIBERATORS'));
            //用户没有登录
            exit('User information to get failed, please re login.1004');
        }
        $uinfo = $uinfo['data'];
        if (false === isset($uinfo['uid'])) {
            $this->cookies_2->add(new \yii\web\Cookie([
                'name' => 'user_auth',
                'value' => NULL,
                'domain'=>\YII::$app->params['COOKIE_DOMAIN'],
            ]));
            $this->cookies_2->add(new \yii\web\Cookie([
                'name' => 'user_region',
                'value' => NULL,
                'domain'=>\YII::$app->params['COOKIE_DOMAIN'],
            ]));
            $this->cookies_2->add(new \yii\web\Cookie([
                'name' => 'user_auth_sign',
                'value' => NULL,
                'domain'=>\YII::$app->params['COOKIE_DOMAIN'],
            ]));
            //$this->error('User information to get failed, please re login.1005', C('MY_URL.LIBERATORS'));
            exit('User information to get failed, please re login.1005');
        }

        // 获取用户玩过的区服
        $playApi           = new Play();
        $playedServerLists = current($playApi->getEnteredServerList($gameId, $uinfo['uid'], '', 1));

        $serverId = Yii::$app->request->get( 'server_id', 0 );
        if ( !$serverId ) {
            if ($playedServerLists) {
                $serverId = $playedServerLists['server_id'];
            } else {
                // 服务器为空.通过IP获取服务器
                $adsServerInfo = json_decode($playApi->getAdServerID($gameId, myhelper::get_client_ip()), true);
                if (true === is_array($adsServerInfo)) {
                    $serverId = array_shift($adsServerInfo);
                }
            }
        }
        if (!$serverId) {
            $serverAPI           = new Server();
            $result = $serverAPI->getAllServerList(
                $gameID          = $gameID,
                $platform        = 'gw',
                $userID          = $uinfo['uid'],
                $userIP          = \Yii::$app->request->userIP,
                $showMaintaining = 0,
                $showOpeningSoon = 0
            );
            if (isset($result['data']) && $result['data']) {
                $server_list = $result['data']; //必须重新复制,不然无效
                $serverId   = current($server_list)['server_id'];
            }
        }
        $params  = '';
        $playUrl = \YII::$app->params['MY_URL']['WEB'] . 'play/index?game_id=' . $gameId . '&server_id=' . $serverId;
        if (isset($_GET['ref'])) {
            $params .= 'ref=' . $_GET['ref'] . '&';
        }
        if (isset($_GET['tag'])) {
            $params .= 'tag=' . $_GET['tag'] . '&';
        }
        if ($params) {
            $playUrl .= '?' . substr($params, 0, -1);
        }
        $this->redirect($playUrl);\YII::$app->response->send();
    }

    public function actionVideoreg(){
        $this->layout = false;
        $request = \Yii::$app->request;
        if(\Yii::$app->request->isAjax){
            $advname = $request->post('tag', '');
            $actType = $request->post('actType', '');
            $uid     = $request->post('uid', '');
            $again   = $request->post('again', ''); //二次记录
            $ip = isset($_COOKIE['user_region_ip'])? $_COOKIE['user_region_ip'] : '';

            $platform = 'gw';
            $actType  = ($actType === 'fb') ? 'fb' : 'gw';
            if ($actType === 'fb') {
                $platform = 'facebook';
            }

            $log_file = 'count_reg_monitor';
            if ($again) {
                $log_file = 'count_reg_monitor_sec';
            }
            $log         = array(1, $advname, $actType . '_' . $uid, $ip, $platform, 1, 'isAd', time());
            $sendmessage = new SendMessage();
            $sendmessage->Send($log_file, $log);
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $log;
        }

        $adv_key = $request->get('tag', '');

        $http_referer = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
        $http_referer = substr($http_referer, 0, 1000);

        $sendmessage2 = new Mlog();
        $message2 = array(
            'timestamp' => time(),
            'tpuid' => '',
            'uid' => '',
            'mainref' => 'adv',
            'subref' => $adv_key,
            'entry' => 'gw',
            'count' => '1',
            'ip' => myhelper::get_client_ip(),
            'step' => '1',
            'appid' => '4',
            'ref' => $http_referer,
        );
        $sendmessage2->Send($message2, 'adv');

        return $this->render('videoreg.html', [
            'adv_key'=>$adv_key
        ]);
    }



    public function actionNewreg(){
        $this->layout = false;
        $request = \Yii::$app->request;
        if(\Yii::$app->request->isAjax){
            $advname = $request->post('tag', '');
            $actType = $request->post('actType', '');
            $uid     = $request->post('uid', '');
            $again   = $request->post('again', ''); //二次记录
            $ip = isset($_COOKIE['user_region_ip'])? $_COOKIE['user_region_ip'] : '';

            $platform = 'gw';
            $actType  = ($actType === 'fb') ? 'fb' : 'gw';
            if ($actType === 'fb') {
                $platform = 'facebook';
            }

            $log_file = 'count_reg_monitor';
            if ($again) {
                $log_file = 'count_reg_monitor_sec';
            }
            $log         = array(1, $advname, $actType . '_' . $uid, $ip, $platform, 1, 'isAd', time());
            $sendmessage = new SendMessage();
            $sendmessage->Send($log_file, $log);
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $log;
        }

        $adv_key = $request->get('tag', '');

        $http_referer = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
        $http_referer = substr($http_referer, 0, 1000);

        $sendmessage2 = new Mlog();
        $message2 = array(
            'timestamp' => time(),
            'tpuid' => '',
            'uid' => '',
            'mainref' => 'adv',
            'subref' => $adv_key,
            'entry' => 'gw',
            'count' => '1',
            'ip' => myhelper::get_client_ip(),
            'step' => '1',
            'appid' => '4',
            'ref' => $http_referer,
        );
        $sendmessage2->Send($message2, 'adv');

        return $this->render('newreg.html', [
            'adv_key'=>$adv_key
        ]);
    }
    
    /**
     * tag日志打印
     * 2016年7月6日 上午9:42:50
     * @author liyee
     */
    public function actionInlogtag(){
        $tag = \YII::$app->request->get('tag');
        $channel = 'game';
        $action = 'inlogtag';
        if ($tag){
            //file_put_contents(ROOT.'/runtime/tag'.date('Ymd').'.log', date('Y-m-d H:i:s').'::'.$channel.'::'.$action.'::'.json_encode($tag)."\n", FILE_APPEND);
        }
    }
    
    /**
     * 请求tag无法检测的tag
     * 2016年7月12日 上午11:50:07
     * @author liyee
     * @param string $tag
     */
    public function actionTotag(){
        $tag = \YII::$app->request->get('tag');
        if ($tag && ($tag == 'AN-WD-cpmstar-CPA')){
            return $this->render('totag.html', [
                'tag'=>$tag
            ]);
        }
    }





    public function actionVideoreg2(){
        $this->layout = false;
        $request = \Yii::$app->request;
        if(\Yii::$app->request->isAjax){
            $advname = $request->post('tag', '');
            $actType = $request->post('actType', '');
            $uid     = $request->post('uid', '');
            $again   = $request->post('again', ''); //二次记录
            $ip = isset($_COOKIE['user_region_ip'])? $_COOKIE['user_region_ip'] : '';

            $platform = 'gw';
            $actType  = ($actType === 'fb') ? 'fb' : 'gw';
            if ($actType === 'fb') {
                $platform = 'facebook';
            }

            $log_file = 'count_reg_monitor';
            if ($again) {
                $log_file = 'count_reg_monitor_sec';
            }
            $log         = array(1, $advname, $actType . '_' . $uid, $ip, $platform, 1, 'isAd', time());
            $sendmessage = new SendMessage();
            $sendmessage->Send($log_file, $log);
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $log;
        }

        $adv_key = $request->get('tag', '');

        $http_referer = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
        $http_referer = substr($http_referer, 0, 1000);

        $sendmessage2 = new Mlog();
        $message2 = array(
            'timestamp' => time(),
            'tpuid' => '',
            'uid' => '',
            'mainref' => 'adv',
            'subref' => $adv_key,
            'entry' => 'gw',
            'count' => '1',
            'ip' => myhelper::get_client_ip(),
            'step' => '1',
            'appid' => '4',
            'ref' => $http_referer,
        );
        $sendmessage2->Send($message2, 'adv');

        return $this->render('videoreg2.html', [
            'adv_key'=>$adv_key
        ]);
    }
}
