<?php
namespace app\modules\liberators;
class Module extends \yii\base\Module {
    public $controllerNamespace = 'app\modules\liberators\controllers';

    public function init() {
        parent::init();
        $config = require __DIR__ . '/config/web.php';
        \Yii::configure(\Yii::$app, $config);
        \Yii::setAlias('@module', __DIR__);
    }
}
