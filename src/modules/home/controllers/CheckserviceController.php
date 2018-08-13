<?php
namespace app\modules\home\controllers;

use Yii;
use yii\web\Controller;

/**
 * code错误码从600开始
 *
 */
class CheckserviceController extends Controller {
    public $defaultAction = 'index';

    public function init(){
        parent::init();
    }
    public static $ERROR_CODE = [
        'PARAMS_ERROR' => 600,
    ];
    public $layout = false;
    public $result;
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        $this->result               = Yii::$app->params['result'];

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    public function actionIndex() {
        $password = (string) \Yii::$app->request->get('password', '');
        $game_id = (int) \Yii::$app->request->get('game_id', 4);
        if ($password !== 'eG3ARSgm2QMe4eyn') {
            return [
                'code'    => 1,
                'message' => 'passowrd verify error',
            ];
        }

        $time = time();
        //------------------------------------------------------------------------
        // 验证redis读写是否正确
        try {
            \Yii::$app->redis->setex("webServer:checkServiceState", 10, $time);
            if (\Yii::$app->redis->get("webServer:checkServiceState") != $time) {
                throw new \Exception("redis value is not correct", 1);
            };
        } catch (\Exception $e) {
            return [
                'code'    => 1,
                'message' => $e->getMessage(),
            ];
        }
        // TODO
        // redis暂时没用所以忽略验证

        //------------------------------------------------------------------------
        // 验证数据库读写是否正常
        try {

            $connection = \Yii::$app->db;
            $command    = $connection->createCommand('SELECT * FROM `check_status` WHERE `key` = \'time\' LIMIT 1;');
            $originData = $command->queryOne();
            if ($originData) {
                $command = $connection->createCommand('UPDATE `check_status` SET `value` = ' . $time . ' WHERE `key` = \'time\';');
            } else {
                $command = $connection->createCommand('INSERT INTO `check_status` (`key`, `value`) VALUES (\'time\', ' . $time . ');');
            }
            $command->execute();

            $command = $connection->createCommand('SELECT * FROM `check_status` WHERE `key` = \'time\' LIMIT 1;');
            $newData = $command->queryOne();
            if ($newData['value'] != $time) {
                throw new \Exception("db value is not correct", 1);
            }
        } catch (\Exception $e) {
            return [
                'code'    => 1,
                'message' => $e->getMessage(),
            ];
        }

        //------------------------------------------------------------------------
        // 验证ucenter连接是否正常
        try {
            $ucenterClass = new \Ucenter\User(['env' => YII_ENV]);
            $curl         = new \app\Library\curl\Curl();
            $curl->setTimeout(5);
            $curl->get($ucenterClass->url);
            $tmpData = json_decode($curl->rawResponse, true);
            if (!$tmpData) {
                throw new \Exception("ucenter error", 1);
            }
        } catch (\Exception $e) {
            return [
                'code'    => 1,
                'message' => $e->getMessage(),
            ];
        }

        //------------------------------------------------------------------------
        // 验证facebook是否正常
        try {
            $games_cfg             = Yii::$app->params['THINK_SDK_FACEBOOK'];
            $app_id                = $games_cfg['APP_KEY'];
            $app_secret            = $games_cfg['APP_SECRET'];

            $curl = new \app\Library\curl\Curl();
            $curl->setTimeout(5);
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);
            $curl->setOpt(CURLOPT_SSL_VERIFYHOST, FALSE);
            // $curl->setOpt(CURLOPT_PROXY, '127.0.0.1');
            // $curl->setOpt(CURLOPT_PROXYPORT, '1080');

            $fb = new \Facebook\Facebook(['app_id' => $app_id, 'app_secret' => $app_secret]);

            $curl->get($fb->getClient()->getBaseGraphUrl());
            $tmpData = json_decode($curl->rawResponse, true);
            if (!$tmpData) {
                throw new \Exception("facebook connect error", 1);
            }
        } catch (\Exception $e) {
            return [
                'code'    => 1,
                'message' => $e->getMessage(),
            ];
        }

        //------------------------------------------------------------------------
        // 验证sapi是否正常
        try {
            $curl = new \app\Library\curl\Curl();
            $curl->setTimeout(5);
            $curl->get(\Yii::$app->params['MY_URL']['SAPI'] . "/index.php/game/list/game_id/" . $game_id . "/no_cache/1");
            $tmpData = json_decode($curl->rawResponse, true);
            if (!$tmpData) {
                throw new \Exception("sapi connect error", 1);
            }
        } catch (\Exception $e) {
            return [
                'code'    => 1,
                'message' => $e->getMessage(),
            ];
        }
        //------------------------------------------------------------------------
        // 验证play是否正常
        try {
            $curl = new \app\Library\curl\Curl();
            $curl->setTimeout(5);
            $curl->get(\Yii::$app->params['play'] . '/spi');
            $tmpData = json_decode($curl->rawResponse, true);
            if (!$tmpData) {
                throw new \Exception("play connect error", 1);
            }
        } catch (\Exception $e) {
            return [
                'code'    => 1,
                'message' => $e->getMessage(),
            ];
        }

        //------------------------------------------------------------------------
        // 一切正常
        return [
            'code'    => 0,
            'message' => 'everything is ok',
        ];
    }
}
