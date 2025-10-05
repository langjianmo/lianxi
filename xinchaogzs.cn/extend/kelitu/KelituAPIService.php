<?php

namespace kelitu;
/**
 * 使用GuzzleHttp库
 */
use think\facade\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class KelituAPIService
{
    public $api_url = "https://www.clipimg.com/api";
    public $idphoto = '/idphoto/make';
    public $idphoto_preview = '/idphoto/preview';
    public $idphoto_download = '/idphoto/download';
    public $print_idphoto_preview = '/idphoto/print_preview';
    public $print_idphoto_download = '/idphoto/print_download';
    public $changeClothes = '/idphoto/change_clothes';
    public $change_clothes_preview = '/idphoto/change_clothes/preview';
    public $change_clothes_download = '/idphoto/change_clothes/download';
    public $scan_auto_crop = '/pic/scan/auto_crop';
    public $scan_idcard = '/pic/scan';
    public $scan_preview = '/pic/scan/preview';
    public $scan_download = '/pic/scan/download';
    public $auto_crop_cards = '/pic/scan/auto_crop_imgs';
    public $human_matting = "/human/matting";
    public $human_make_personal_portrait = "/human/make_personal_portrait";
    public $personal_portrait_preview = '/human/personal_portrait/preview';
    public $personal_portrait_download = '/human/personal_portrait/download';
    public $apiKey = "";

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function GenSavePath($path, $formate)
    {
        $format = date("Ymd");
        $dirname = $path . "/" . $format . "/";
        if (!file_exists($dirname)) {
            mkdir($dirname, 0777, true);
        }
        return $dirname . uniqid() . "." . $formate;
    }

    /**
     * 制作证件照
     * $img 上传的人像图片本地地址
     * 参数说明参考文档https://clipimg.com/wp/api-doc/
     */
    public function IdphotoMake($img, $color, $spec_id, $fair_level, $dpi = 300)
    {
        $client = new Client();
        $base64_img = base64_encode(file_get_contents($img));//转换成base64格式
        $response = $client->request('POST', $this->api_url . $this->idphoto, [
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(["file" => $base64_img, "spec_id" => $spec_id, 'dpi' => $dpi, "color" => $color, 'fair_level' => $fair_level])
        ]);
        $json = json_decode($response->getBody()->getContents());

        return $json;
    }

    /**
     * 预览证件照
     */
    public function IdphotoPreview($preview_img_name)
    {
        $client = new Client();
        $response = $client->request('GET', $this->api_url . $this->idphoto_preview . '/' . $preview_img_name, [
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ]
        ]);
        return $response->getBody();//图片流
    }

    /**
     * 证件照下载
     */
    public function IdphotoDownload($img_name)
    {
        $path = $this->GenSavePath('storage', 'png');//生成目标文件地址
        try {
            $client = new Client();
            $body = [];
            $response = $client->request('POST', $this->api_url . $this->idphoto_download . '/' . $img_name, [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ], 'body' => json_encode($body)
            ]);
            $status_code = $response->getStatusCode();
            $r = $response->getBody();//图片流
            file_put_contents($path, $r);//存放到目标目录
            return $path;
        } catch (RequestException $e) {
            $status_code = $e->getResponse()->getStatusCode();
        } finally {
        }
        return $status_code;
    }

    /**
     * 证件照换装
     */
    public function IdphotoChangeClothes($img_name, $clothes_id, $dpi = 300)
    {
        $client = new Client();
        $response = $client->request('POST', $this->api_url . $this->changeClothes, [
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(["img_name" => $img_name, "clothes_id" => $clothes_id, 'bg_color' => "#ffffff", 'dpi' => $dpi])
        ]);
        $json = json_decode($response->getBody()->getContents());
        return $json;
    }

    /**
     * 换装预览
     */
    public function IdphotoChangeClothesPreview($preview_img_name)
    {
        $client = new Client();
        $response = $client->request('GET', $this->api_url . $this->change_clothes_preview . '/' . $preview_img_name, [
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ]
        ]);
        return $response->getBody();
    }

    /**
     * 换装照下载
     */
    public function IdphotoChangeClothesDownload($img_name)
    {
        $path = $this->GenSavePath('storage', 'png');//生成目标文件地址
        Log::write($img_name);
        try {
            $client = new Client();
            $body = [];
            $response = $client->request('POST', $this->api_url . $this->change_clothes_download . '/' . $img_name, [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ], 'body' => json_encode($body)
            ]);
            $status_code = $response->getStatusCode();
            $r = $response->getBody();//图片流
            Log::write('ppppppppppp');
            Log::write(json_decode($response->getBody()->getContents(),true));
            file_put_contents($path, $r);//存放到目标目录
            return $path;
        } catch (RequestException $e) {
            Log::write($e->getResponse());
            $status_code = $e->getResponse()->getStatusCode();
        } finally {
        }
        return $status_code;
    }

    /**
     * 排版照预览
     */
    public function PrintIdphotoPreview($preview_img_name, $text = '')
    {
        $client = new Client();
        $response = $client->request('GET', $this->api_url . $this->print_idphoto_preview . '/' . $preview_img_name, [
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ], 'body' => json_encode(empty($text) ? [] : ["text" => $text])
        ]);
        return $response->getBody();
    }

    /**
     * 排版照下载 
     */
    public function PrintIdphotoDownload($img_name, $text = '')
    {
        $path = $this->GenSavePath('storage', 'png');
        try {
            $client = new Client();
            $body = [];
            if (!empty($text)) {
                $path='storage'.$text;
            }
            $response = $client->request('POST', $this->api_url . $this->print_idphoto_download . '/' . $img_name, [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ], 'body' => json_encode($body)
            ]);
            $status_code = $response->getStatusCode();
            $r = $response->getBody();
            file_put_contents($path, $r);
            return $path;
        } catch (RequestException $e) {
            $status_code = $e->getResponse()->getStatusCode();
        } finally {
        }
        return $status_code;
    }

    public function Scan($card_type, $img, $dpi)
    {
        @ini_set('memory_limit', '512M');
        $client = new Client();
        try {
            $exif = exif_read_data($img);
        } catch (\Exception $e) {
        }
        if (!empty($exif['Orientation'])) {
            $image = imagecreatefromstring(file_get_contents($img));
            $img = $this->GenSavePath('temporary', 'jpg');
            switch ($exif['Orientation']) {
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
            }
            imagejpeg($image, $img);
            $base64_img = base64_encode(file_get_contents($img));
            imagedestroy($image);
        } else {
            $base64_img = base64_encode(file_get_contents($img));
        }

        $response = $client->request('POST', $this->api_url . $this->scan_idcard, [
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(["file" => $base64_img, "card_type" => $card_type, 'dpi' => $dpi])
        ]);
        $json = json_decode($response->getBody()->getContents(), true);
        return $json;
    }

    public function AutoCrop($card_type, $points, $img_id)
    {
        $client = new Client();
        $response = $client->request('POST', $this->api_url . $this->scan_auto_crop, [
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(["image_id" => $img_id, "points" => $points, "card_type" => $card_type])
        ]);
        $json = json_decode($response->getBody()->getContents());
        return $json;
    }

    public function AutoCropImgs($list, $card_type)
    {
        $client = new Client();
        $response = $client->request('POST', $this->api_url . $this->auto_crop_cards, [
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(["list" => $list, "card_type" => $card_type])
        ]);
        $json = json_decode($response->getBody()->getContents());
        return $json;
    }

    public function ScanPreview($preview_img_name)
    {
        try {
            $client = new Client();
            $response = $client->request('GET', $this->api_url . $this->scan_preview . '/' . $preview_img_name, [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ]
            ]);
            return $response->getBody();
        } catch (RequestException $e) {
            return $e->getResponse()->getStatusCode();
        }
    }

    public function ScanDownload($img_name)
    {
        if (strpos($img_name, '_pdf')) {
            $path = $this->GenSavePath('storage', 'pdf');
        } else {
            $path = $this->GenSavePath('storage', 'png');
        }
        try {
            $client = new Client();
            $body = [];
            $response = $client->request('POST', $this->api_url . $this->scan_download . '/' . $img_name, [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ], 'body' => json_encode($body)
            ]);
            $status_code = $response->getStatusCode();
            $r = $response->getBody();
            file_put_contents($path, $r);
            return $path;
        } catch (RequestException $e) {
            $status_code = $e->getResponse()->getStatusCode();
        } finally {

        }
        return $status_code;
    }

    public function PersonalPortraitMake($img)
    {
        $client = new Client();
        $base64_img = base64_encode(file_get_contents($img));
        $response = $client->request('POST', $this->api_url . $this->human_make_personal_portrait, [
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(["file" => $base64_img, 'is_crop' => 0])
        ]);
        $json = json_decode($response->getBody()->getContents());
        return $json;
    }

    public function PersonalPortraitPreview($preview_img_name)
    {
        $client = new Client();
        $response = $client->request('GET', $this->api_url . $this->personal_portrait_preview . '/' . $preview_img_name, [
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ]
        ]);
        return $response->getBody();
    }

    public function PersonalPortraitDownload($img_name)
    {
        $path = $this->GenSavePath('storage', 'png');
        try {
            $client = new Client();
            $body = [];
            $response = $client->request('POST', $this->api_url . $this->personal_portrait_download . '/' . $img_name, [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ], 'body' => json_encode($body)
            ]);
            $status_code = $response->getStatusCode();
            $r = $response->getBody();
            file_put_contents($path, $r);
            return $path;
        } catch (RequestException $e) {
            $status_code = $e->getResponse()->getStatusCode();
        } finally {
        }
        return $status_code;
    }
}
