<?php

namespace app\modules\home\models;

class ReplyForum extends \yii\db\ActiveRecord
{
    const STATUS_NEW         = 0;
    const STATUS_REPLIED     = 1;
    const STATUS_NEW_REPLY   = 2;
    const STATUS_CLOSED      = 3;
    const STATUS_DUPLICATED  = 4;
    const STATUS_IN_Progress = 5;
    public static function tableName()
    {
        return '{{%reply_forum}}';
    }
    public function rules()
    {
        return array(
            array(['forum_id','content','add_time','uid'], 'required',  'message'=>\YII::t('common','Every option is required'))
        );
    }
}
