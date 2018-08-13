<?php

namespace app\modules\home\controllers;

use app\helpers\myhelper;
use app\modules\home\models\Region;
use app\modules\home\models\Message;
use app\Library\gameapi\Play;

class UsercenterController extends CommonController
{
    public $defaultAction = 'index';

    public function init()
    {
        parent::init();
        $this->layout = '@module/views/' . GULP . '/public/user.html';
        if (!$this->is_login) {
            $this->redirect(['/']);
            \YII::$app->end();
        }

        $user_menu = [];
        $user_menu[0] = array("url" => "/usercenter/index", "name" => \yii::t("common", "GeneralSettings"));
        $user_menu[1] = array("url" => "/usercenter/binduser", "name" => \yii::t("common", "Connect Accounts"));
        $user_menu[2] = array("url" => "/usercenter/updatepwd", "name" => \yii::t("common", "UpdatePassword"));
        $user_menu[3] = array("url" => "/usercenter/orderlist", "name" => \yii::t("common", "PaymentHistory"));
        $user_menu[5] = array("url" => "/usercenter/message", "name" => \yii::t("common", "System Messages"));

        $result = $this->ucenter->getbinded($this->ttl_to_token($this->user_info['ttl']), $this->user_info['id']);
        if (!isset($result['code']) || $result['code'] != 0) {
            $this->logout();//接口失败 清空所有COOKIE 并退出
            return $this->redirect(['/404']);
        }
        if (!in_array('gw', $result['data'])) {
            unset($user_menu[2]);
        }
        foreach ($user_menu as $key => $value) {
            if (strip_tags($_SERVER['REQUEST_URI']) == $value['url']) {
                $user_menu[$key]['active'] = "on";
            } else {
                $user_menu[$key]['active'] = "off";
            }
        }
        $this->view->params['user_menu'] = $user_menu;

        $this->view->params['meta_title'] = \YII::t('common', 'TitLiberatorsSignIn');
        $this->view->params['keyword'] = "liberators sign in,liberators game,strategy games online,mutantbox";
        $this->view->params['description'] = "Information about users of liberators sign in online games. Enter liberators catalogue to find users' info, ! Play strategy games online with mutantbox!";
    }

    public function actionIndex()
    {
        if (\Yii::$app->request->post()) {
            $result = (new \Ucenter\User(['env' => ENV]))->updateuser(null, array(
                'username' => \Yii::$app->request->post('username'),
                'gender' => (int)\Yii::$app->request->post('gender'),
                'birth' => \Yii::$app->request->post('birth_data'),
                'country' => \Yii::$app->request->post('region_id'),
                'mobile' => \Yii::$app->request->post('mobile'),
                'skype' => \Yii::$app->request->post('skype'),
            ));
            $ajax_data = array('error' => 1, 'msg' => 'Unkonw Error');
            if (isset($result['code']) && $result['code'] == 0) {
                $ajax_data['error'] = 0;
                $ajax_data['msg'] = \YII::t('common', 'ChangeSuccessful');
                $this->cookies_2->add(new \yii\web\Cookie([
                    'name' => 'last_update_time',
                    'value' => time(),
                    'domain' => \YII::$app->params['COOKIE_DOMAIN'],
                ]));
                $this->sessions['user_data'] = null;
            } else {
                if (isset($result['error'])) {
                    $ajax_data['msg'] = $result['error'];
                }
            }
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $ajax_data;
        } else {
            $region_list = (new \yii\db\Query())->select("*")->from(Region::tableName())->all();
            return $this->render('index.html', [
                'region_list' => $region_list,
                'max_age' => date("Y-m-d", strtotime("-13 year")),
            ]);
        }
    }


    public function actionBinduser()
    {
        $user_info = $this->user_info;
        $userCenter = new \Ucenter\User(['env' => ENV]);
        $result = $userCenter->getbinded(null, $user_info['id']);

        if (!isset($result['code']) || $result['code'] != 0) {
            return $this->redirect(['/404']);
        }
        return $this->render('bindUser.html', [
            'account_list' => $result['data'],
        ]);
    }

    public function actionUpdatepwd()
    {
        $user_info = $this->user_info;
        if (\Yii::$app->request->post()) {
            $result = (new \Ucenter\User(['env' => ENV]))->updatepwd(null, \Yii::$app->request->post('oldpassword'), \Yii::$app->request->post('password'));

            $ajax_data = array('error' => 1, 'msg' => 'Unkonw Error');
            if (isset($result['code']) && $result['code'] == 0) {
                $ajax_data['error'] = 0;
                $ajax_data['msg'] = \YII::t('common', 'ChangeSuccessful');
            } else {
                if (isset($result['code']) && $result['code'] == 1027) {
                    $ajax_data['msg'] = 'old password is error';
                }
            }
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $ajax_data;
        } else {

            $userCenter = new \Ucenter\User(['env' => ENV]);
            $result = $userCenter->getbinded(null, $user_info['id']);

            if (!isset($result['code']) || $result['code'] != 0) {
                return $this->redirect(['/404']);
            }

            return $this->render('updatePwd.html', [
                'show_oldpassword' => 0,
            ]);
        }
    }

    public function actionOrderlist()
    {
        $user_info = $this->user_info;
        $page = 1; //当前页
        $perpage = 6; //每次显示条数
        $start = ($page - 1) * $perpage;
        $uid = '\'fb_' . $user_info['id'] . '\',' . '\'gw_' . $user_info['id'] . '\',' . '\'' . $user_info['id'] . '\'';
        $payOrderInfo = \Yii::$app->dbpay->createCommand("select * from pay_orders where uid IN($uid) and status !=0  and gameid=4 order by createtime DESC limit $start, $perpage")->queryAll();
        if (!empty($payOrderInfo)) {
            foreach ($payOrderInfo as $key => $value) {
                $packid = $value['pack_id'];
                $mealInfo = \Yii::$app->dbpay->createCommand("select * from pay_platform_currency where id=$packid")->queryAll();
                $name = isset($mealInfo[0]['name']) ? $mealInfo[0]['name'] : '';
                $payOrderInfo[$key]['mealName'] = $name;
                $payOrderInfo[$key]['serverName'] = Play::getserverinfo($value['serverid']);
                if ($payOrderInfo[$key]['currency'] == 'US') {
                    $payOrderInfo[$key]['currency'] = 'USD';
                }
                $payOrderInfo[$key]['price'] = $payOrderInfo[$key]['currency'] . ' ' . $payOrderInfo[$key]['amount'];
            }
        }
        return $this->render('orderList.html', [
            'payOrderInfo' => $payOrderInfo,
        ]);
    }

    public function actionMoreorder()
    {
        if (\Yii::$app->request->isAjax) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $user_info = $this->user_info;
            $page = \Yii::$app->request->post('page'); //当前页
            $perpage = 6; //每次显示条数
            $start = ($page - 1) * $perpage;
            $uid = '\'fb_' . $user_info['id'] . '\',' . '\'gw_' . $user_info['id'] . '\',' . '\'' . $user_info['id'] . '\'';
            $payOrderInfo = \Yii::$app->dbpay->createCommand("select * from pay_orders where uid IN($uid) and status !=0 and gameid=4  order by createtime DESC limit $start, $perpage")->queryAll();
            $str = '';
            if (!empty($payOrderInfo)) {
                foreach ($payOrderInfo as $key => $value) {
                    $packid = $value['pack_id'];
                    $serverName = Play::getserverinfo($value['serverid']);
                    $mealInfo = \Yii::$app->dbpay->createCommand("select * from pay_platform_currency where id=$packid")->queryAll();
                    $name = isset($mealInfo[0]['name']) ? $mealInfo[0]['name'] : '';
                    $payOrderInfo[$key]['serverName'] = $serverName;
                    $payOrderInfo[$key]['mealName'] = $name;
                    if ($payOrderInfo[$key]['currency'] == 'US') {
                        $payOrderInfo[$key]['currency'] = 'USD';
                    }
                    $payOrderInfo[$key]['price'] = $payOrderInfo[$key]['currency'] . ' ' . $payOrderInfo[$key]['amount'];
                    $status = $value['status'] == 1 ? 'Completed' : 'Unfinished';
                    $str .= '<tr class="tr_item tr_item2">'
                        . '<td class="item item01">'
                        . '<h2>Date & Time</h2>'
                        . '<p>' . date("M d, Y", $value['createtime']) . '<br />' . date("H:i:s", $value['createtime']) . '</p>'
                        . '</td>'
                        . '<td class="item item02">'
                        . '<h2>Game</h2>'
                        . '<p>Liberators</p>'
                        . '</td>'
                        . '<td class="item item03">'
                        . '<h2>Server</h2>'
                        . '<p>' . $serverName . '</p>'
                        . '</td>'
                        . '<td class="item item04">'
                        . '<h2>Pack</h2>'
                        . '<p>' . $payOrderInfo[$key]['mealName'] . '</p>'
                        . '</td>'
                        . '<td class="item item05">'
                        . '<h2>Price</h2>'
                        . '<p>' . $payOrderInfo[$key]['price'] . '</p>'
                        . '</td>'
                        . '<td class="item item06">'
                        . '<h2>Order ID</h2>'
                        . '<p>' . $value['orderid'] . '</p>'
                        . '</td>'
                        . '<td class="item item07">'
                        . '<h2>Status</h2>'
                        . '<p>' . $status . '</p>'
                        . '</td>'
                        . '</tr>';
                }
            }
            return array('ap_str' => $str);
        }
    }


    public function actionReadmore()
    {
        $where['id'] = \Yii::$app->request->post('msg_id');
        $content = (new \yii\db\Query())
            ->select('content')
            ->from('ww2_message')
            ->where($where)
            ->one();

        $result_data['content'] = htmlspecialchars_decode($content['content']);
        $UserMessage = new Message;
        $uid = $this->user_info['id'];
        $data['is_read'] = 1;
        $UserMessage->updateAll($data, 'message_id=:message_id', [':message_id' => \Yii::$app->request->post("msg_id")]);
        $count_where['uid'] = $uid;
        $count_where['is_read'] = 0;
        $result_data['message_count'] = (new \yii\db\Query())
            ->select('id')
            ->from($UserMessage::tableName())
            ->where($count_where)
            ->count();
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $result_data;
    }

    public function actionMessage()
    {
        $uid = $this->user_info['id'];
        $where['uid'] = $uid;
        $where['game_id'] = 4;
        //每页记录
        $data['page'] = \Yii::$app->request->get('p');
        $data['page_count'] = 4;
        if (\Yii::$app->request->isAjax) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $list = $this->page($where, $data, "ajax");
            return $list;
        } else {
            $list = $this->page($where, $data);
            return $this->render('message.html', [
                'message_list' => $list,
            ]);
        }
    }

    /* 获取数据类 */

    public function page($where = array(), $data = array(), $type = "")
    {
        $list = Message::messageList($where, 'id DESC', ($data['page'] - 1) * $data['page_count'], $data['page_count']);
        if ($type == "ajax") {
            $list = Message::msgAjax($list);
        } else {
            $list = Message::msgFormatting($list);
        }
        return $list;
    }

    public function actionAjaxregioncode()
    {
        $region_id = \Yii::$app->request->post('region_id');
        if (!empty($region_id)) {
            $where['id'] = $region_id;
            $result = (new \yii\db\Query())
                ->select('area_code')
                ->from(Region::tableName())
                ->where($where)
                ->one();
            $data['area_code'] = $result['area_code'];
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $data;
        }
    }

    public function actionUploadportrait()
    {
        if ($_FILES['portrait_file']) {
            $upload_config = \Yii::$app->params['ARTICLEPICTURE_UPLOAD'];
            $upload = new \app\helpers\Image\Upload($upload_config); // 实例化上传类
            $info = $upload->upload();
            $ajax_data = array("result" => "", "msg" => "", "src" => "", "path" => "", "width" => "", "height" => "");
            if (!$info) {
                $ajax_data['result'] = 1;
                $ajax_data['msg'] = $upload->getError();
                echo '<html>
                            <head>
                            <meta charset="UTF-8"><title></title>
                            <script>document.domain = \'' . $_SERVER['HTTP_HOST'] . '\'</script>
                            </head>
                            <body>' . json_encode($ajax_data) . '</body>
                            </html>';
            } else {
                $ajax_data['result'] = 0;
                $ajax_data['msg'] = "上传成功";
                $image = new \app\helpers\Image\Image();
                if( YII_ENV === 'prod' ){
                    if (!is_dir(UPLOAD_NEW_FILE . $info['portrait_file']['savepath'])) {
                        mkdir(UPLOAD_NEW_FILE . $info['portrait_file']['savepath'], 0777, true);
                    }
                    $image->open(UPLOAD_NEW_FILE . $info['portrait_file']['savepath'] . $info['portrait_file']['savename']);
                    $upload_path = UPLOAD_NEW_FILE.$info['portrait_file']['savepath'].$info['portrait_file']['savename'];
                    $uploadCDNResult = true;
                    foreach (\YII::$app->params['RSYNC_CDN_ADDRESS'] as $address) {
                        $address = $address .'/images/'.$info['portrait_file']['savepath'];
                        system("rsync -avr {$upload_path} {$address} > /dev/null", $systemResult);
                        if ($systemResult != 0) {
                            $uploadCDNResult = false;
                            break;
                        }
                    }
                    if ($uploadCDNResult) {
                        $avatar = CDN_NEW_URL.'/images/'.$info['portrait_file']['savepath'].$info['portrait_file']['savename'];
                    } else {
                        $ajax_data['result'] = 2;
                        $ajax_data['msg'] = "Failed to update profile picture.";
                        return $ajax_data;
                    }
                }else{
                    if (!is_dir(UPLOAD_IMAGE_FILE . $info['portrait_file']['savepath'])) {
                        mkdir(UPLOAD_IMAGE_FILE . $info['portrait_file']['savepath'], 0777, true);
                    }
                    $image->open(UPLOAD_IMAGE_FILE . $info['portrait_file']['savepath'] . $info['portrait_file']['savename']);
                    $avatar = UPLOAD_IMAGE_FILE_URL.$info['portrait_file']['savepath'] . $info['portrait_file']['savename'];
                    $upload_path = UPLOAD_IMAGE_FILE.$info['portrait_file']['savepath'].$info['portrait_file']['savename'];
                }
                $width = $image->width(); // 返回图片的宽度
                $height = $image->height(); // 返回图片的高度
                $ajax_data['width'] = $width;
                $ajax_data['height'] = $height;
                $ajax_data['src'] = $avatar;
                $ajax_data['path'] = $upload_path;
                echo '<html>
                            <head>
                            <meta charset="UTF-8"><title></title>
                            <script>document.domain = \'' . $_SERVER['HTTP_HOST'] . '\'</script>
                            </head>
                            <body>' . json_encode($ajax_data) . '</body>
                            </html>';
            }
        }
    }

    public function actionCropportrait()
    {

        $crop_img = \Yii::$app->request->post();
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $image = new \app\helpers\Image\Image();
        $image->open($crop_img['src']);
        $save_dir = dirname($crop_img['src']);
        $str = substr($save_dir, -6);
        $save_name = "thumb_" . basename($crop_img['src']);
        $img_info = $image->crop($crop_img['w'], $crop_img['h'], $crop_img['x'], $crop_img['y'], 200, 200)->save($save_dir . '/' . $save_name);
        if (!$img_info) {
            $ajax_data['result'] = 1;
            $ajax_data['msg'] = $image->getError();
            return $ajax_data;
        } else {
            @unlink($crop_img['src']);//删除原图
            $user_info = $this->user_info;
            $data['id'] = $user_info['id'];
            if( YII_ENV === 'prod' ) {
                //图片同步CDN
                $uploadCDNResult = true;
                $upload_path = $save_dir . '/' . $save_name;
                foreach (\YII::$app->params['RSYNC_CDN_ADDRESS'] as $address) {
                    $address = $address . '/images/' . $str . '/';
                    system("rsync -avr {$upload_path} {$address} > /dev/null", $systemResult);
                    if ($systemResult != 0) {
                        $uploadCDNResult = false;
                        break;
                    }
                }
                if ($uploadCDNResult) {
                    @unlink($upload_path);//删除缩略图
                    $avatar = CDN_NEW_URL . '/images/' . $str . "/" . $save_name;
                } else {
                    $ajax_data['result'] = 2;
                    $ajax_data['msg'] = "Failed to update profile picture.";
                    return $ajax_data;
                }
            }else{
                $avatar = UPLOAD_IMAGE_FILE_URL.$str.'/'.$save_name;
            }

            $updateData = (new \Ucenter\User(['env' => ENV]))->updateuser(null, array('id' => $user_info['id'], 'avatar' => $avatar));
            if (isset($updateData['error']) && empty($updateData['error'])) {
                $ajax_data['result'] = 0;
                $ajax_data['msg'] = "Profile picture updated.";

                $this->cookies_2->add(new \yii\web\Cookie([
                    'name' => 'last_update_time',
                    'value' => time(),
                    'domain' => \YII::$app->params['COOKIE_DOMAIN'],
                ]));
                $this->sessions['user_data'] = null;
                return $ajax_data;
            } else {
                $ajax_data['result'] = 2;
                $ajax_data['msg'] = "Failed to update profile picture.";
                return $ajax_data;
            }
        }
    }

    public function actionAjaxsaveportrait()
    {
        $avater_src = \Yii::$app->request->post('src');
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!empty($avater_src)) {
            $user_info = $this->user_info;
            $data['id'] = $user_info['id'];
            $data['avatar_url'] = basename($avater_src);
            $data['thumb_avatar_url'] = basename($avater_src);
            $updateData = (new \Ucenter\User(['env' => ENV]))->updateuser(null, array('id' => $user_info['id'], 'avatar' => $avater_src));
            if (isset($updateData['error']) && empty($updateData['error'])) {
                $this->cookies_2->add(new \yii\web\Cookie([
                    'name' => 'last_update_time',
                    'value' => time(),
                    'domain' => \YII::$app->params['COOKIE_DOMAIN'],
                ]));
                $this->sessions['user_data'] = null;
                $ajax_data['result'] = 0;
                $ajax_data['msg'] = "Profile picture updated.";
                return $ajax_data;
            } else {
                $ajax_data['result'] = 2;
                $ajax_data['msg'] = "Failed to update profile picture.";
                return $ajax_data;
            }
        } else {
            $ajax_data['result'] = 1;
            $ajax_data['msg'] = "Failed to update profile picture.";
            return $ajax_data;
        }
    }

}
