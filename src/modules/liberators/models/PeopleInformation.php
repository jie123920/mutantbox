<?php
namespace app\modules\liberators\models;
class PeopleInformation extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return '{{%people_information}}';
    }

    public function getInfoById($uid)
    {
        $where['language'] = LANG_SET;

        if ($uid === true) {
            $key = 'one_rand_people_'.$uid.LANG_SET.PROTOCOL;
            if ($data = \Yii::$app->cache->get($key)) {
                return $data;
            }
            $data = (new \yii\db\Query())
                ->select('*')
                ->from(self::tableName())
                ->where($where)
                ->orderBy('rand()')
                ->groupBy('hid')
                ->one();
        } else {
            $key = 'one_people_'.$uid.LANG_SET.PROTOCOL;
            if ($data = \Yii::$app->cache->get($key)) {
                return $data;
            }
            $where['hid']      = $uid;
            $data = (new \yii\db\Query())
                ->select('*')
                ->from(self::tableName())
                ->where($where)
                ->orderBy('id')
                ->groupBy('hid')
                ->one();
        }

        if (!$data) {
            return false;
        }
        $data['id'] = $data['hid'];

        $data['m_portraits']           = __IMG__. '/herobig/' . $data['m_portraits'];
        $data['portraits']             = __IMG__ . '/herosmall/' . $data['portraits'];
        $data['arm_services_picture']  = __IMG__ . '/wep/' . $data['arm_services_picture'];
        $data['arm_services_picture1'] = __IMG__ . '/army/' . $data['arm_services_picture1'];
        $data['arm_services_picture2'] = __IMG__ . '/army/' . $data['arm_services_picture2'];
        $data['arm_services_picture3'] = __IMG__ . '/army/' . $data['arm_services_picture3'];
        $data['arm_services_picture4'] = __IMG__ . '/army/' . $data['arm_services_picture4'];

        \Yii::$app->cache->set($key, $data, 600);

        return $data;
    }

    /**
     * 获取单个字段信息
     * $fieldString  多个字段用逗号分隔
     */
    public function getFields($fieldString)
    {
        $where['language'] = LANG_SET;

        if ($data = \Yii::$app->cache->get('people'.LANG_SET.PROTOCOL)) {
            return $data;
        }
        $data = (new \yii\db\Query())
            ->select($fieldString)
            ->from(self::tableName())
            ->where($where)
            ->orderBy('id')
            ->groupBy('hid')
            ->all();
        if($data){
            foreach ($data as $k => $v) {
                $data[$k]['portraits'] = __IMG__ . '/herosmall/' . $v['portraits'];
            }
            \Yii::$app->cache->set('people'.LANG_SET.PROTOCOL, $data, 600);
        }
        return $data;
    }

}
