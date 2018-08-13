<?php

namespace app\modules\home\models;

class Getpassword extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%get_password}}';
    }
    public function scenarios() {
        return [
            'create' => ['id','email', 'email_code', 'get_pwd_time', 'last_login_time', 'last_login_ip','status','validated_code','upatetime','createtime'],
        ];
    }
}
