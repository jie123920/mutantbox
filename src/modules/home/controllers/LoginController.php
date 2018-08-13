<?php
namespace app\modules\home\controllers;

use Facebook\Facebook;
use Google\Google;
class LoginController extends CommonController
{

    protected $Type      = '';
    protected $AppKey    = '';
    protected $AppSecret = '';
    protected $fb        = '';

    public function init()
    {
        parent::init();
        $config = \YII::$app->params['THINK_SDK_FACEBOOK'];

        if (empty($config['APP_KEY']) || empty($config['APP_SECRET'])) {
            exit('请配置您申请的APP_KEY和APP_SECRET');
        } else {
            $this->AppKey    = $config['APP_KEY'];
            $this->AppSecret = $config['APP_SECRET'];
        }
        $this->Type = "facebook";
        $this->fb   = $this->fb();
    }

    protected function fb()
    {
        $fb = new Facebook([
            'app_id'                => $this->AppKey,
            'app_secret'            => $this->AppSecret,
            'default_graph_version' => 'v2.10',
        ]);
        return $fb;
    }

    public function actionFacebookcallback()
    {
        $this->getAccessToken();

        $account_user = $this->getGraph("/me?fields=id,name,email,gender,token_for_business,picture", $_SESSION['fb_access_token']);

//        if (APP_DEBUG == true) {
//            file_put_contents(date('Ymd') . '.log', date('Y-m-d H:i:s') . '::account_user::' . json_encode($account_user) . "\n", FILE_APPEND);
//            file_put_contents(date('Ymd') . '.log', date('Y-m-d H:i:s') . '::id::' . $account_user['id'] . "\n", FILE_APPEND);
//            file_put_contents(date('Ymd') . '.log', date('Y-m-d H:i:s') . '::name::' . $account_user['name'] . "\n", FILE_APPEND);
//            file_put_contents(date('Ymd') . '.log', date('Y-m-d H:i:s') . '::email::' . $account_user['email'] . "\n", FILE_APPEND);
//            file_put_contents(date('Ymd') . '.log', date('Y-m-d H:i:s') . '::gender::' . $account_user['gender'] . "\n", FILE_APPEND);
//            file_put_contents(date('Ymd') . '.log', date('Y-m-d H:i:s') . '::token_for_business::' . $account_user['token_for_business'] . "\n", FILE_APPEND);
//            file_put_contents(date('Ymd') . '.log', date('Y-m-d H:i:s') . '::picture::' . $account_user['picture'] . "\n", FILE_APPEND);
//        }

        if (!empty($account_user)) {

//            if (APP_DEBUG == true) {
//                file_put_contents(date('Ymd') . '.log', date('Y-m-d H:i:s') . '::gender::' . $account_user['gender'] . "\n", FILE_APPEND);
//            }

            $User        = D("User");
            $ConnectUser = D("ConnectUser");

            if (empty($account_user['email'])) {
                $account_user['email'] = $account_user['token_for_business'] . "@facebook.com";
            }

            $account_user['gender'] = $account_user['gender'] == "male" ? 0 : 1;
            $account_user['media']  = 'facebook';

            $count = $ConnectUser->existsMediaId($account_user['token_for_business'], "facebook");

            if ($count > 0) {
                $account_info = $ConnectUser->getMediaInfo($account_user['token_for_business'], "facebook");

                if ($account_info['user_id'] > 0) {
                    $where['id'] = $account_info['user_id'];
                    $user_info   = $User->userInfo($where);

                    $avatar       = $this->uploadAvatar($account_user);
                    $avatar['id'] = $user_info['id'];
                    if ($User->saveAvatar($avatar) === false) {
                        $result_data['error'] = 101;
                        $result_data['msg']   = "Error updating profile picture.";
                        $this->ajaxReturn($result_data);
                    }
                } else {
                    $this->userChecked($account_user, "facebook");
                }

                if ($User->autoLogin($user_info, 30)) {
                    $result_data['error'] = 0;
                    $result_data['msg']   = "Facebook login successfully.";
                    $data                 = array();
                    $data['uid']          = $user_info['id'];
                    $data['username']     = $user_info['username'];
                    $data['email']        = $user_info['email'];
                    $data['headimg']      = $user_info['thumb_avatar_url'];
                    $gw_param             = base_encode(json_encode($data));
                    $jumpUrl              = C("MY_URL")['WEB'] . "index.php/Game/suc/paramInfo/$gw_param";

                    $result_data['url'] = $jumpUrl;
                    $this->ajaxReturn($result_data);
                } else {
                    $result_data['error'] = 102;
                    $result_data['msg']   = "Facebook login error. Code:102";
                    $this->ajaxReturn($result_data);
                }
            } else {
                $data['media']           = 'facebook';
                $data['media_id']        = $account_user['id'];
                $data['account_name']    = $account_user['name'];
                $data['account_email']   = $account_user['email'];
                $data['business_token']  = $account_user['token_for_business'];
                $data['account_headimg'] = $account_user['picture']['url'];
                if ($ConnectUser->createAccount($data)) {
                    $this->userChecked($account_user, "facebook");
                } else {
                    $result_data['error'] = 103;
                    $result_data['msg']   = "Facebook login error. Code:103";
                    $this->ajaxReturn($result_data);
                }
            }
        } else {
            $result_data['error'] = 104;
            $result_data['msg']   = "No data returned from facebook. Code:104";
            $this->ajaxReturn($result_data);
        }
    }

    public function actionBindfacebookcallback()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $this->getAccessToken();
        $userCenter = new \Ucenter\User(['env'=>ENV,'domain'=>DOMAIN]);
        $userData   = $this->user_info;

        $result = $userCenter->bindfb(null, $userData['id'], $_SESSION['fb_access_token']);

        if (isset($result['code']) && $result['code'] == 0) {
            $result_data['error'] = 0;
            $result_data['msg']   = "Successfully signed with facebook accounts.";
        }elseif (isset($result['code']) && $result['code'] == 1713) {
            $result_data['error'] = $result['code'];
            $result_data['msg']   = "account is duplicate";
        } else {
            $result_data['error'] = $result['code'];
            $result_data['msg']   = "Unknown error sign with facebook accounts.";
        }
        return $result_data;
    }

    protected function getAccessToken()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $helper = $this->fb->getJavaScriptHelper();
        try {
            $access_token = $helper->getAccessToken();
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $result_data['error'] = 401;
            $result_data['msg']   = "Graph returned an error: " . $e->getMessage() . " Code:401";
            return $result_data;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $result_data['error'] = 402;
            $result_data['msg']   = "Facebook returned an error: " . $e->getMessage() . " Code:402";
            return $result_data;
        }

        if (!isset($access_token)) {
            $result_data['error'] = 403;
            $result_data['msg']   = "No cookie set or no OAuth data could be obtained from cookie. Code:403";
            return $result_data;
        }
        $_SESSION['fb_access_token'] = (string) $access_token;
    }

    protected function getGraph($url = "", $access_token = "", $user_id = "")
    {
        try {
            $response = $this->fb->get($url, $access_token);
        } catch (Facebook\Exceptions\FacebookResponseException $e) {

            $result_data['error'] = 404;
            $result_data['msg']   = "Graph returned an error: " . $e->getMessage() . " Code:404";
            $this->ajaxReturn($result_data);

        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            $result_data['error'] = 405;
            $result_data['msg']   = "Facebook returned an error: " . $e->getMessage() . " Code:405";
            $this->ajaxReturn($result_data);
        }

        return $response->getGraphNode();
    }

    public function uploadAvatar($user_info, $type = "facebook", $picture = "")
    {

        if ($type == "facebook") {
            $picture = $this->getGraph("/$user_info[id]/picture?type=large&redirect=0", $_SESSION['fb_access_token']);
        }

        $Directory = new \Think\Directory();
        $save_path = __ROOT__ . "Uploads/UserAvatar/" . date("Ym", NOW_TIME) . "/";
        if (!$Directory->checkRootPath($save_path)) {
            $Directory->mkdir($save_path);
        }

        $save_name = $save_path . $user_info['id'] . ".jpg";
        Http::curlDownload($picture['url'], $save_name);

        $image = new \Think\Image();
        $image->open($save_name);
        $save_thumb_name = $save_path . "thumb_" . $user_info['id'] . ".jpg";
        $image->thumb(100, 100, \Think\Image::IMAGE_THUMB_CENTER)->save($save_thumb_name);

        $avatar['avatar_url']       = date("Ym", NOW_TIME) . "/" . $user_info['id'] . ".jpg";
        $avatar['thumb_avatar_url'] = date("Ym", NOW_TIME) . "/thumb_" . $user_info['id'] . ".jpg";
        return $avatar;
    }

    public function test()
    {
        $this->display();
    }

    public function googleCallBack()
    {
        $code   = I("post.code");
        $Google = new Google($code);
        $token  = $Google->getAccessToken($code);

        $_SESSION["google_access_token"] = $token['access_token'];

        $account_user = $Google->call("userinfo");
        if (!empty($account_user)) {
            $User        = D("User");
            $ConnectUser = D("ConnectUser");

            if (empty($account_user['email'])) {
                $account_user['email'] = $account_user['id'] . "@gmail.com";
            }

            $account_user['gender'] = 0;
            $account_user['media']  = "google";

            $count = $ConnectUser->existsMediaId($account_user['id'], "google");

            if ($count > 0) {
                $account_info = $ConnectUser->getMediaInfo($account_user['id'], "google");

                if ($account_info['user_id'] > 0) {
                    $where['id']    = $account_info['user_id'];
                    $user_info      = $User->userInfo($where);
                    $picture['url'] = $account_user['picture'];
                    $avatar         = $this->uploadAvatar($account_user, "google", $picture);
                    $avatar['id']   = $user_info['id'];
                    if ($User->saveAvatar($avatar) === false) {
                        $result_data['error'] = 201;
                        $result_data['msg']   = "Error updating profile picture.";
                        $this->ajaxReturn($result_data);
                    }
                } else {
                    $this->userChecked($account_user, "google");
                }

                if ($User->autoLogin($user_info, 30)) {
                    $data             = array();
                    $data['uid']      = $user_info['id'];
                    $data['username'] = $user_info['username'];
                    $data['email']    = $user_info['email'];
                    $data['headimg']  = $user_info['thumb_avatar_url'];
                    $gw_param         = base_encode(json_encode($data));
                    $jumpUrl          = C("MY_URL")['WEB'] . "Game/suc/paramInfo/$gw_param";

                    $result_data['error'] = 0;
                    $result_data['msg']   = "Successfully signed with google accounts.";
                    $result_data['url']   = $jumpUrl;
                    $this->ajaxReturn($result_data);
                } else {
                    $result_data['error'] = 202;
                    $result_data['msg']   = "Google login error.";
                    $this->ajaxReturn($result_data);
                }
            } else {
                $data['media']         = "google";
                $data['media_id']      = $account_user['id'];
                $data['account_name']  = $account_user['name'];
                $data['account_email'] = $account_user['email'];

                if ($ConnectUser->createAccount($data)) {
                    $this->userChecked($account_user, "google");
                }elseif (isset($result['code']) && $result['code'] == 1713) {
                    $result_data['error'] = 302;
                    $result_data['msg']   = "account is duplicate";
                    $this->ajaxReturn($result_data);
                } else {
                    $result_data['error'] = 203;
                    $result_data['msg']   = "Google login error.";
                    $this->ajaxReturn($result_data);
                }
            }
//            echo "<pre>";
            //            print_r($user);
            //            echo "</pre>";
        } else {
            $result_data['error'] = 204;
            $result_data['msg']   = "No data returned from google.";
            $this->ajaxReturn($result_data);
        }
    }

    public function actionBindgooglecallback()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $code = \YII::$app->request->post("code");
        $userCenter = new \Ucenter\User(['env'=>ENV,'domain'=>DOMAIN]);
        $userData   = $this->user_info;


        $result = $userCenter->bindgg(null, $userData['id'], $code);

        if (isset($result['code']) && $result['code'] == 0) {
            $result_data['error'] = 0;
            $result_data['msg']   = "Successfully signed with google accounts.";
        }elseif (isset($result['code']) && $result['code'] == 1713) {
            $result_data['error'] = $result['code'];
            $result_data['msg']   = "account is duplicate";
        } else {
            $result_data['error'] = $result['code'];
            $result_data['msg']   = "Unknown error sign with google accounts.";
        }
        return $result_data;
    }

    public function userChecked($account_user, $type = "facebook")
    {
        $User         = D("User");
        $ConnectUser  = D("ConnectUser");
        $ucwords_type = ucwords($type);
        if ($User->checkedEmail($account_user['email']) > 0) {
            $where          = array();
            $where['email'] = $account_user['email'];
            $user_info      = $User->userInfo($where);

            if ($type == "facebook") {
                $save_where['business_token'] = $account_user['token_for_business'];
            } else {
                $save_where['media_id'] = $account_user['id'];
            }
            $save_where['media'] = $type;

            $save_data['user_id'] = $user_info['id'];

            if ($ConnectUser->where($save_where)->save($save_data) !== false) {
                $picture['url'] = $account_user['picture'];
                $avatar         = $this->uploadAvatar($account_user, $type, $picture);

                $avatar['id'] = $user_info['id'];
                if ($User->saveAvatar($avatar) === false) {
                    $result_data['error'] = 901;
                    $result_data['msg']   = "Error updating profile picture.";
                    $this->ajaxReturn($result_data);
                }

                if ($User->autoLogin($user_info, 30)) {
                    $result_data['error'] = 0;
                    $result_data['msg']   = $ucwords_type . " login successfully.";
                    $this->ajaxReturn($result_data);
                } else {
                    $result_data['error'] = 903;
                    $result_data['msg']   = $ucwords_type . " login error.";
                    $this->ajaxReturn($result_data);
                }
            } else {
                $result_data['error'] = 904;
                $result_data['msg']   = $ucwords_type . " login error.";
                $this->ajaxReturn($result_data);
            }
        } else {
            if ($User->regAccountUser($account_user) !== false) {
                $where          = array();
                $where['email'] = $account_user['email'];
                $user_info      = $User->userInfo($where);

                $picture['url'] = $account_user['picture'];
                $avatar         = $this->uploadAvatar($account_user, $type, $picture);

                $avatar['id'] = $user_info['id'];
                if ($User->saveAvatar($avatar) === false) {
                    $result_data['error'] = 905;
                    $result_data['msg']   = "Error updating profile picture.";
                    $this->ajaxReturn($result_data);
                }

                if ($User->autoLogin($user_info, 30)) {
                    $result_data['error'] = 0;
                    $result_data['isNew'] = 1;
                    $result_data['uid']   = $user_info['id'];
                    $result_data['msg']   = $ucwords_type . " login successfully.";
                    $this->ajaxReturn($result_data);
                } else {
                    $result_data['error'] = 906;
                    $result_data['msg']   = $ucwords_type . " login error.";
                    $this->ajaxReturn($result_data);
                }
            } else {
                $result_data['error'] = 907;
                $result_data['msg']   = $ucwords_type . " login error.";
                $this->ajaxReturn($result_data);
            }
        }
    }


    public function actionLogin(){
        if($this->is_login){
            $this->redirect(['/']);\YII::$app->end();
        }

        $referer = \yii::$app->request->get('referer','');
        $this->view->params['referer']= $referer;
        $this->layout = '@module/views/'.GULP.'/public/login.html';
        return $this->render('/usercenter/login.html', [
        ]);
    }

    public function actionRegister(){
        if($this->is_login){
            $this->redirect(['/']);\YII::$app->end();
        }
        $referer = \yii::$app->request->get('referer','');
        $this->view->params['referer']= $referer;
        $this->layout = '@module/views/'.GULP.'/public/login.html';
        return $this->render('/usercenter/login.html', [
            'register'=>1,
        ]);
    }

    public function actionCtLogin(){
        /*if($this->is_login){
            $this->redirect(['/']);\YII::$app->end();
        }*/

        $referer = \yii::$app->request->get('referer','');
        $this->view->params['referer']= $referer;
        $this->layout = '@module/views/'.GULP.'/public/ct-login.html';
        return $this->render('/usercenter/ct-login.html', [
        ]);
    }

    public function actionCtRegister(){
        /*if($this->is_login){
            $this->redirect(['/']);\YII::$app->end();
        }*/
        $referer = \yii::$app->request->get('referer','');
        $this->view->params['referer']= $referer;
        $this->layout = '@module/views/'.GULP.'/public/ct-login.html';
        return $this->render('/usercenter/ct-login.html', [
            'register'=>1,
        ]);
    }
}
