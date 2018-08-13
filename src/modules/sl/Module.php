<?php
namespace app\modules\sl;
class Module extends \yii\base\Module {
    public $controllerNamespace = 'app\modules\sl\controllers';

    public function init() {
        parent::init();
        $config = require __DIR__ . '/config/web.php';
        \Yii::configure(\Yii::$app, $config);
        \Yii::setAlias('@module', __DIR__);
    }
}
