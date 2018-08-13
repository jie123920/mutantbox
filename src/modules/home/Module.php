<?php
namespace app\modules\home;
class Module extends \yii\base\Module {
    public $controllerNamespace = 'app\modules\home\controllers';
    public function init() {
        parent::init();
        \Yii::configure(\Yii::$app, require (__DIR__ . '/config/web.php'));
        \Yii::setAlias('@module', __DIR__);
    }
}
?>