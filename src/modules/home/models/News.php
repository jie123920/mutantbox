<?php

namespace app\modules\home\models;

class News extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%new_article}}';
    }

    public function getSimple()
    {
        return $this->hasMany(News_language::className(), ['pid' => 'id']);
    }
}
