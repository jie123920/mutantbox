<?php

namespace app\modules\home\models;

class Invest extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%invest}}';
    }

    public function rules()
    {
        return array(
            array(['other_lang','character_id','oid','uid'], 'trim'),
            array(['email'], 'email',  'message'=>\YII::t('common','Invalid email address')),
            array(['server_id','game_name','birth','prefer_lang','game_id','add_time','platform','language'], 'required',  'message'=>' is required'),
        );
    }

}
