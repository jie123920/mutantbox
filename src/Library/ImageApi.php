<?php
namespace app\Library;

use \app\Library\curl\Curl;
use yii\base\Exception;
use yii;

class ImageApi {
    /**
     * 构造函数
     */
    public function __construct($host, $appId, $secretKey, $version = "v1")
    {
        $this->appId     = $appId;
        $this->secretKey = $secretKey;
        $this->version   = $version;
        $this->host      = $host;
    }

    public function setResource($resource) {
        $this->resource = $resource;
    }

    public function getResource() {
        return $this->resource;
    }

    // 上传图片
    public function upload($filename) {
        try {
            $data = [
                "app_id" => $this->appId,
                "timestamp" => time(),
            ];
            $data = $this->signature($data);
            $url = $this->host . '/' . $this->version . '/' . 'upload';
            $curl = new Curl();
            $curl->setHeader("Content-Type", "multipart/form-data");
            $fileinfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimetype = $fileinfo->buffer(file_get_contents($filename));
            $data['image'] = new \CurlFile($filename, $mimetype, 'image');
            $res = $curl->post($url, $data);
            if (isset($res->code) && $res->code === 0) {
                $this->resource = $res->data->src;
                return true;
            } else {
                throw new Exception("upload fail");
            }
        } catch (Exception $e) {
            throw new Exception("upload fail, unknown");
        }
    }

    // 获取图片大小
    public  function resize( $protocol, $width, $height) {
        if (empty($this->resource)) {
            throw new Exception("not exists resource");
        }

        if (!in_array($protocol, ['http', 'https'])) {
            throw new Exception("not support protocol");
        }

        if ($width <= 0) {
            throw new Exception("width is too small");
        }

        if ($height <= 0) {
            throw new Exception("height is too small");
        }

        return preg_replace("/((?:\.jpg)|(?:\.jpeg)|(?:\.png))$/i", "_{$width}x{$height}_100\${1}", $protocol . ':' . $this->resource );
    }

    // 获取原始上传图片url
    public function getUrl( $protocol ) {
        if (empty($this->resource)) {
            throw new Exception("not exists resource");
        }

        if (!in_array($protocol, ['http', 'https'])) {
            throw new Exception("not support protocol");
        }

        return $protocol . ':' . $this->resource;
    }

    function signature($data) {
        $arr = [];
        foreach($data as $key => $value) {
            if (!is_array($value)) {
                $arr[] = $key . $value;
            }
        }
        sort($arr, SORT_STRING);
        $sign = md5($this->secretKey . implode("", $arr));
        $data['signature'] = $sign;

        return $data;
    }
}
