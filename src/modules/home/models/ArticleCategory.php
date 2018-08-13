<?php

namespace app\modules\home\models;
class ArticleCategory extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return '{{%article_category}}';
    }

    public function getCat($parent_id, $cat_name)
    {
        $cacheKey = 'news_category_'.$parent_id.'_'.$cat_name;
        if ($data = \Yii::$app->cache->get($cacheKey)) {
            return $data;
        }
        $where['platform'] = $parent_id;
        $where['name']      = $cat_name;
        $data = (new \yii\db\Query())
            ->select('id')
            ->from(self::tableName())
            ->where($where)
            ->one();

        if($data){
            \Yii::$app->cache->set($cacheKey, $data, 600);
        }

        return $data;
    }

    public function getCatList($parent_id)
    {
        $cacheKey = 'news_category_list_'.$parent_id.LANG_SET;
        if ($cat_list = \Yii::$app->cache->get($cacheKey)) {
            return $cat_list;
        }

        $where['parent_id'] = $parent_id;
        $cat_list = (new \yii\db\Query())
            ->select('id,name,sort')
            ->from(self::tableName())
            ->where($where)
            ->orderBy("sort ASC,id ASC")
            ->all();
        foreach ($cat_list as $key => $value) {
            $where               = array();
            $where['s.fid']      = $value['id'];
            $where['s.language'] = LANG_SET;
            $l =    News_Multi_Language::getlist($where, "s.create_time DESC", 0, 6);
            if(empty($l)) $where['s.language'] = 'en-us';//默认英文
            $l =    News_Multi_Language::getlist($where, "s.create_time DESC", 0, 6);
            $cat_list[$key]['article'] =    $l;
        }
        if($cat_list){
            \Yii::$app->cache->set($cacheKey, $cat_list, 600);
        }
        return $cat_list;
    }

}
