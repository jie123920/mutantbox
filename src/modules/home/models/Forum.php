<?php

namespace app\modules\home\models;

class Forum extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%forum}}';
    }

    public function rules()
    {
        return array(
            //array('email', 'required', 'message'=>\YII::t('common','Email is required')),
            array(['email'], 'email',  'message'=>\YII::t('common','Invalid email address')),
            array(['subject'], 'required',  'message'=>\YII::t('common','Subject is required')),
            array(['descrption'], 'required',  'message'=>\YII::t('common','Descrption is required')),
            array(['forum_id','game_id','server_id','topic_id','add_time','clientinfo','language','uid','game_name'], 'required',  'message'=>\YII::t('common','Every option is required'))
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplyForum() {
        return $this->hasMany(ReplyForum::className(), ['forum_id' => 'id'])->
            orderBy(['add_time' => SORT_ASC]);
    }

}
