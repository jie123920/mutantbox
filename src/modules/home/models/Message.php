<?php

namespace app\modules\home\models;
use app\helpers\myhelper;
class Message extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%user_message}}';
    }



//新闻首页
    public static function messageList($map = array(), $order = 'id', $start = '1', $rows = "10") {
        return (new \yii\db\Query())
            ->select('ww2_message.id,ww2_message.title,ww2_message.content,ww2_message.send_time,is_read')
            ->from(self::tableName())
            ->where($map)
            ->join('INNER JOIN','ww2_message','ww2_message'.'.id='.self::tableName().'.message_id')
            ->orderBy("$order")
            ->limit("$rows")
            ->offset("$start")
            ->all();
    }

    public static function msgFormatting($list) {
        foreach ($list as $key => $value) {
            if($list[$key]['id'] != 30){
                $list[$key]['source_content'] = strip_tags(htmlspecialchars_decode($value['content']));
            }else{
                $list[$key]['source_content'] = htmlspecialchars_decode($value['content']);
            }
            $list[$key]['content'] = myhelper::msubstr($list[$key]['source_content'], 0,320, "utf-8");
            $list[$key]['send_time'] = date("M d, Y-H:i", $value['send_time']);
        }
        return $list;
    }

    public static function msgAjax($list) {
        $str = "";
        foreach ($list as $key => $value) {
            if($list[$key]['id'] != 30){
                $list[$key]['source_content'] = strip_tags(htmlspecialchars_decode($value['content']));
            }else{
                $list[$key]['source_content'] = htmlspecialchars_decode($value['content']);
            }
            $list[$key]['content'] = myhelper::msubstr($list[$key]['source_content'], 0, 320, "utf-8");
            $list[$key]['send_time'] = date("M d, Y-H:i", $value['send_time']);
            $str .= '<li id="msg' . $value['id'] . '"><div class="img"><img src="/Public/' . BIND_MODULE . '/images/system_pic.jpg"></div><div class="text">';

            if ($value['is_read'] == 1) {
                $str.='<h2 class="is_read">' . $value['title'] . '</h2>';
            } else {
                $str .='<h2 class="msg_title">' . $value['title'] . '</h2>';
            }

            $str .='<div class = "edit">' . $list[$key]['content'] . '<a href = "javascript:void(0);" class = "read_more" data = "' . $value['id'] . '">Read More</a></div>
            <div class = "other">
            <span>System</span><em>|</em><span>' . $list[$key]['send_time'] . '</span>
            </div>
            </div>
            </li>';
        }
        return $str;
    }
}
