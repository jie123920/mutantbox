<?php
namespace app\modules\home\controllers;
use app\helpers\myhelper;
use app\modules\home\models\Connect;
use app\Library\gameapi\Play;
use app\Library\gameapi\Server;
class PlayController extends CommonController
{
    /* 游戏接入 */
    public function init()
    {
        parent::init();
        $this->layout = false;
    }
    public function actionIndex()
    {
        if (!($this->is_login)) {
            $this->redirect(['/login?referer='.\Yii::$app->request->getHostInfo().\Yii::$app->request->url]);\YII::$app->end();
        }
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
            $this->redirect(['/login?referer='.\Yii::$app->request->getHostInfo().\Yii::$app->request->url]);\YII::$app->end();
            //return $this->error('User information to get failed, please re login.1003');
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
            $this->redirect(['/login?referer='.\Yii::$app->request->getHostInfo().\Yii::$app->request->url]);\YII::$app->end();
            //return $this->error('User information to get failed, please re login.1004');
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
            $this->redirect(['/login?referer='.\Yii::$app->request->getHostInfo().\Yii::$app->request->url]);\YII::$app->end();
            //return $this->error('User information to get failed, please re login.1005');
        }

        $pf        = 'gw';
        $game_id   = isset($_GET['game_id']) ? trim($_GET['game_id']) : '4'; //游戏ID
        $server_id = isset($_GET['server_id']) ? trim($_GET['server_id']) : ''; //游戏区服
//        $playApi           = new Play();
//
//        if (!$server_id) {
//            // 广告用户且服务器为空.通过IP获取服务器
//            $playedServerLists = current($playApi->getEnteredServerList($game_id, $uinfo['uid'], '', 1));
//
//            $server_id = false;
//            if ($playedServerLists) {
//                $server_id = $playedServerLists['server_id'];
//            } else {
//                // 服务器为空.通过IP获取服务器
//                $adsServerInfo = json_decode($playApi->getAdServerID($game_id, myhelper::get_client_ip()), true);
//                if (true === is_array($adsServerInfo)) {
//                    $server_id = array_shift($adsServerInfo);
//                }
//            }
//        }
//        if (!$server_id) {
//            $serverAPI           = new Server();
//            $result = $serverAPI->getAllServerList(
//                $gameID          = $game_id,
//                $platform        = $pf,
//                $userID          = $uinfo['uid'],
//                $userIP          = \Yii::$app->request->userIP,
//                $showMaintaining = 0,
//                $showOpeningSoon = 0
//            );
//            if (isset($result['data']) && $result['data']) {
//                $server_list = $result['data']; //必须重新复制,不然无效
//                $server_id   = current($server_list)['server_id'];
//            }
//        }
//        if (!$game_id || !$server_id) {
//            //return $this->error('The server does not exist.1000');
//        }



        //游戏配置信息
//        if (!$game_cfg = \YII::$app->params['GAME_CONFIG']) {
//            //return $this->error('Game config to get failed.1001');
//        }
//        if (!isset($game_cfg[$game_id])) {
//            //return $this->error('Game config to get failed.1002');
//        }
        $game_cfg = \YII::$app->params['GAME_CONFIG'];
        if (!isset($game_cfg[$game_id])) {
            return $this->error('Game config to get failed.1002');
        }

        // 判断服务器是否允许登录
        // 取得所有服务器
//        $canPlayServer = $playApi->getOpenedServerList($game_id, $uinfo['uid']);
//        if (!isset($canPlayServer[$server_id])) {
//            //$logStr = date('Y-m-d H:i:s') . '-----------PlayControllerError' . PHP_EOL .
//            //'ad_server_id:' . $server_id . PHP_EOL .
//            //'canPlayServer:' . json_encode($canPlayServer) . PHP_EOL;
//            //\Think\Log::record($logStr);
//            //return $this->error('The server does not exist or is being maintained.1006');
//        }

        //安全KEY
        $conect = new Connect();
        $secret = $conect->makeSecret($pf, $uinfo['uid'], $game_id, $server_id);

        //构造跳转URL
        $http_get['pf']        = $pf;
        $http_get['uid']       = $uinfo['uid'];
        $http_get['game_id']   = $game_id;
        $http_get['server_id'] = $server_id;
        $http_get['token']     = $secret['secret'];
        $http_get['time']      = $secret['time'];
        $http_get['ref']       = isset($_GET['ref']) ? trim($_GET['ref']) : '';
        $http_get['tag']       = isset($_GET['tag']) ? trim($_GET['tag']) : '';
        $http_get['lang']      = LANG_SET;

        $gameUrl = CONNECTURL2 . '?' . http_build_query($http_get + $_GET);

        return $this->render('index.html', [
            'gameUrl'=>$gameUrl,
            'domain'=>DOMAIN,
        ]);
    }
}
