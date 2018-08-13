<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/6
 * Time: 16:39
 */

namespace yii\helpers;


class Myhelp
{
    function elixir($file)
    {
        static $manifest = null;

        if (is_null($manifest)) {
            $manifest = json_decode(file_get_contents(ROOT.'/dist/rev/assets/rev-manifest.json'), true);
        }

        if (isset($manifest[$file])) {
            return '/build/'.$manifest[$file];
        }

        return true;
    }
}