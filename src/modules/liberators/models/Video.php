<?php
namespace app\modules\liberators\models;
use app\modules\home\models\ArticleCategory;

class Video extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%video}}';
    }

    /**
     * @param int $platform 默认是9 属于游戏平台的
     * @return array
     */
    public function getVideoList($platform=9)
    {
        $cacheKey = 'video_list_'.$platform;
        if ($data = \Yii::$app->cache->get($cacheKey)) {
            return $data;
        }
        $data = (new \yii\db\Query())
            ->select(self::tableName().'.*,ww2_photo.img_source')
            ->from(self::tableName())
            ->where([ 'display' => 0])
            ->andWhere(['<>','cover_id',0])
            ->join('LEFT JOIN','ww2_photo','ww2_photo.id='.self::tableName().'.cover_id')
            ->leftJoin(ArticleCategory::tableName(), ArticleCategory::tableName().'.id='.self::tableName().'.tid')
            ->where([ArticleCategory::tableName().'.platform' => $platform])
            ->orderBy(self::tableName().".create_time desc")
            ->all();

        if($data){
            \Yii::$app->cache->set($cacheKey, $data, 600);
        }
        return $data;
    }

}
