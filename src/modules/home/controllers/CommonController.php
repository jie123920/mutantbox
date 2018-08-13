<?php
namespace app\modules\home\controllers;
use yii\web\Controller;
use app\modules\home\models\Message;
use app\helpers\myhelper;
use Ucenter\Library\XteaEncrypt;

class CommonController extends Controller {
    public $enableCsrfValidation = false;//CSRF
    public $view = NULL;
    public $cookies = NULL;//read
    public $cookies_2 = NULL;//write
    public $sessions = NULL;
    public $layout = '';
    public $ucenter = null;
    public $user_info = [];
    public $is_login = 0;
    public $game_list = [];
    
    public function init(){
        $this->layout = '@module/views/'.GULP.'/public/main.html';
        $this->ucenter = new \Ucenter\User(['env'=>ENV,'domain'=>DOMAIN]);
        $this->view = \Yii::$app->view;
        $this->cookies = \Yii::$app->request->cookies;
        $this->cookies_2 = \Yii::$app->response->cookies;
        $this->sessions = \Yii::$app->session;
        $this->game_list = [
            [
                'id'=>4,
                'game_name'=>'Liberators'
            ],
            [
                'id'=>9,
                'game_name'=>'BattleSpace'
            ],
//            [
//                'id'=>3,
//                'game_name'=>'Survivor Legacy'
//            ]
        ];
        $this->view->params['is_login']= 0;
        $this->view->params['login_show']= 0;
        $this->view->params['user_info']= [];
        $this->view->params['ticket_count']= 0;
        $this->view->params['system_msg_count']= 0;
        $this->view->params['cart_num']= 0;
        $user_info = $this->getCookieUser();//判断用户是否登录
        if ($user_info) {
            $this->view->params['is_login'] = $this->is_login = 1;
            $this->view->params['user_info'] = $this->user_info = $user_info;
            
            $count_where = [];
            $count_where['is_read'] = 0;
            $count_where['uid'] = $user_info['id'];
            $count_where['ww2_message.game_id'] = 4;
            if ($ticket_count = \Yii::$app->cache->get('web_ticket_count_'.json_encode($count_where))) {
            }else{
                $ticket_count = (new \yii\db\Query())
                    ->select("id")
                    ->from(Message::tableName())
                    ->join('INNER JOIN','ww2_message','ww2_message'.'.id='.Message::tableName().'.message_id')
                    ->where($count_where)
                    ->count();
                if($ticket_count){
                    \Yii::$app->cache->set('web_ticket_count_'.json_encode($count_where), $ticket_count, 600);
                }
            }



            $where = [];
            $where['uid'] = $user_info['id'];
            $where['status'] = 0;
            if ($system_msg_count = \Yii::$app->cache->get('web_system_msg_count_'.json_encode($where))) {
            }else{
                $system_msg_count = (new \yii\db\Query())
                    ->select("id")
                    ->from('ww2_reply_forum')
                    ->where($where)
                    ->andWhere(['<>','admin_id',0])
                    ->count();
                if($system_msg_count){
                    \Yii::$app->cache->set('web_system_msg_count_'.json_encode($where), $system_msg_count, 600);
                }
            }

            $message_count = $ticket_count + $system_msg_count;

            $this->view->params['message_count']= $message_count;
            $this->view->params['ticket_count']= $ticket_count;
            $this->view->params['system_msg_count']= $system_msg_count;
        }
    }

    /**
     * 内部用户退出登录
     */
    public  function logout() {
        $token = $this->sessions['userinfo']['token'];
        if (isset($token)){
            (new \Ucenter\Ucenter(['domain'=>DOMAIN, 'env'=>ENVUC]))->User()->logout($token);
        }
        $this->sessions['userinfo'] = null;
        $this->cookies_2->removeAll();
    }

    /**
     * 检测用户是否登录
     * @return integer 0-未登录，大于0-当前登录用户ID
     */
    function is_login($type = 'user')
    {
        if($this->ucenter->getToken()){
            return true;
        }else {
            if ($this->ucenter->getEncodeCookie('remember_me_token')) {
                return true;
            }
            return 0;
        }
    }

    /* 空操作，用于输出404页面 */
    public function actionEmpty() {
        header(" HTTP/1.0  404  Not Found");
        return $this->render('/public/404.html', [
            'login_show'=>1
        ]);
        \YII::$app->end();
    }

    public function actionMaintenance() {
        return $this->render('/public/maintenance.html', [
        ]);
        \YII::$app->end();
    }

    //类似于无限极分类的做法，通过子类找父类
    //面包屑导航
    public function getParents ($list, $id) {
        $arr = array();
        foreach ($list as $v) {
            if($v['parent_id'] == 0) continue;
            if ($v['id'] == $id) {
                $arr[] = $v;
                $arr = array_merge($this->getParents($list, $v['parent_id']),$arr);
            }
        }
        return $arr;
    }

    protected function ttl_to_token($ttl){
        $token = null;
        $token_json_str = XteaEncrypt::getInstance()->Decrypt(base64_decode($ttl));
        $token_arr = json_decode($token_json_str, true);
        if( isset($token_arr['token']) ){
            $token = $token_arr['token'];
        }
        return $token;
    }

    protected function getCookieUser()
    {   
        if(!isset($_COOKIE['_ttl']) && !isset($_COOKIE['remember_me_token']) && !\YII::$app->request->get('_ttl', '')){
            return false;
        }
        $userData = $ttl = $token = null;
        if (isset($this->sessions['user_data'])) {
            $userData = $this->sessions['user_data'];
        }
        if (isset($_COOKIE['_ttl'])) {//只存在1天
            $ttl = $_COOKIE['_ttl'];
        }

        if (\YII::$app->request->get('_ttl', '')) {
            $ttl = \YII::$app->request->get('_ttl', '');
            $token = $this->ttl_to_token($ttl);
            setcookie('_ttl', $ttl, time()+86400, '/', DOMAIN);
        }

        if ($userData && $ttl == $userData['ttl']) {
            return $userData;
        }

        $userData = $this->ucenter->userinfo($token, 'id,email,country,username,avatar,gender,birth,skype,mobile,platform');
        if(($userData && $userData['code']!=0) || !$userData){
            $remember_me_token = $this->ucenter->getEncodeCookie('remember_me_token');
            if($remember_me_token){
                $userData = $this->autoLogin($remember_me_token);
                if($userData && $userData['code'] == 0){
                    $userData = $this->ucenter->userinfo($userData['data']['token'], 'id,email,country,username,avatar,gender,birth,skype,mobile,platform');
                    if($userData && $userData['code']!=0){//未获取接口 退出
                        return false;
                    }
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }
        $userData               = $userData['data'];
        $userData['avatar'] = empty($userData['avatar'])? DEFAULT_AVATAR : $userData['avatar'] ;
        $userData['avatar_url'] = $userData['avatar'];
        $userData['birth_data'] = strtotime($userData['birth']);
        $userData['ttl']        = $ttl;
        $this->sessions['user_data'] = $userData;
        setcookie('username',$userData['username'],time()+86400,'/',\YII::$app->params['COOKIE_DOMAIN']);//供手机版读取
        // if(empty($userData['avatar'])) $userData['avatar'] = DEFAULT_AVATAR;
        return $userData;
    }

    public function isLogin() {
        if (!($this->is_login)) {
            header('Location: '.\YII::$app->params['MY_URL']['WEB'].'login/login');\YII::$app->end();
        }
    }


    /**
     * 记住我功能
     * @param string $remember_me_token
     */
    public function autoLogin($remember_me_token='') {
        $returnData = (new \Ucenter\Ucenter(['domain'=>DOMAIN, 'env'=>ENVUC]))->User()->autoLogin($remember_me_token);
        if($returnData && $returnData['code']==0){
            $data = $returnData['data'];
            $this->sessions['userinfo'] = $data;
            $this->sessions['user_data'] = null;

            $expire =  60 * 60 * 24;//1 day
            $auth = array(
                'uid' => $data['uid'],
                'username' => !empty($data['username'])?$data['username']:$data['account'],
                'thumb_avatar'=>$data['avatar'],
                'email' => $data['account'],
                'last_login_time' => isset($data['lasttime'])?$data['lasttime']:time(),
            );

            $this->cookies_2->add(new \yii\web\Cookie([
                'name' => 'user_auth',
                'value' => $auth,
                'expire'=>time()+$expire,
                'domain'=>\YII::$app->params['COOKIE_DOMAIN'],
            ]));
            $this->cookies_2->add(new \yii\web\Cookie([
                'name' => 'user_auth_sign',
                'value' => myhelper::data_auth_sign($auth),
                'expire'=>time()+$expire,
                'domain'=>\YII::$app->params['COOKIE_DOMAIN'],
            ]));

            $this->cookies_2->add(new \yii\web\Cookie([
                'name' => 'last_update_time',
                'value' => time(),
                'domain'=>\YII::$app->params['COOKIE_DOMAIN'],
            ]));
        }
        return $returnData;
    }


    public function error($title){
        $this->layout = '@module/views/'.GULP.'/public/main.html';
        return $this->render('/public/404.html', [
            'title'=>$title,
        ]);
    }

    /**
     * 检测登录
     * @param string $referer
     * @param bool $isAjax
     * @return array
     */
    public function check_user($referer='/',$isAjax=false){
        if(!$this->is_login()){
            if($isAjax){
                echo json_encode([ 'code' => -1, 'message' => 'please log in', 'data'    => '' ]) ;exit;
            }else{
                $this->redirect(['/login?referer='.$referer]);\YII::$app->end();
            }
        }
    }


    /**
     * 定义json返回结果统一格式
     * 2017年5月24日 下午2:44:42
     * @author liyee
     * @param number $code
     * @param string $message
     * @param unknown $data
     */
    protected function result($code = 0, $message = '', $data = []) {
        return [
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];
    }
}
