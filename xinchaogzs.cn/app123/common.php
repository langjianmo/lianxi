<?php

// 应用公共文件

use app\common\controller\WeChatService;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Http\StreamResponse;
use EasyWeChat\Kernel\Support\XML;
use Endroid\QrCode\QrCode;
use GuzzleHttp\Client;
use think\facade\Config;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\File;
use think\Image;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

function db($table_name = '')
{
    $prefix = config('database.connections.mysql.prefix');

    return Db::table($prefix . $table_name);
}

/**
 * 设置菜单类名.
 *
 * @param $controller
 * @param string $action
 * @param string $class_name
 *
 * @return string
 */
function setMenuClass($controller, $action = '', $class_name = 'active')
{
    if (is_array($controller)) {
        if (in_array(request()->controller(true), $controller)) {
            return $class_name;
        }
    } else {
        if ($controller == request()->controller(true) && $action == '') {
            return $class_name;
        }
        if ($controller == request()->controller(true) && $action == request()->action(true)) {
            return $class_name;
        }
    }
}

/**
 * 获取用户ip,位置.
 *
 * @param string $field
 *
 * @return bool|mixed
 */
function get_client_address($field = '')
{
    $ip = false;
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip) {
            array_unshift($ips, $ip);
            $ip = false;
        }
        for ($i = 0; $i < count($ips); ++$i) {
            if (!eregi('^(10│172.16│192.168).', $ips[$i])) {
                $ip = $ips[$i];
                break;
            }
        }
    }
    $ip = $ip ? $ip : $_SERVER['REMOTE_ADDR'];
    $api = 'https://api.map.baidu.com/location/ip';
    $client = new Client();
    $res = $client->get(
        $api,
        [
            'query' => [
                'ak' => '21mALphM82PET4k4SftFBiTcfrgCReNI',
                'ip' => $ip,
            ],
        ]
    );
    $res = json_decode(
        $res->getBody()
            ->getContents(),
        true
    );
    $res['ip'] = $ip;

    // array:4 [▼
    //  "address" => "CN|山西|None|None|CMNET|0|0"
    //  "content" => array:3 [▼
    //    "address_detail" => array:6 [▼
    //      "province" => "山西省"
    //      "city" => ""
    //      "district" => ""
    //      "street" => ""
    //      "street_number" => ""
    //      "city_code" => 10
    //    ]
    //    "address" => "山西省"
    //    "point" => array:2 [▼
    //      "y" => "4534120.26"
    //      "x" => "12525303.99"
    //    ]
    //  ]
    //  "status" => 0
    //  "ip" => "120.208.121.241"
    //]
    if ($field && isset($res[$field])) {
        return $res[$field];
    } else {
        return $res;
    }
}

/**
 * php数组对象转js数组.
 *
 * @param $obj
 *
 * @return string
 */
function obj2jsArr($obj)
{
    $jsArr = '[';
    if (is_array($obj)) {
        foreach ($obj as $k => $v) {
            $jsArr .= "'{$v}',";
        }
    }
    $jsArr = substr($jsArr, 0, -1);
    $jsArr .= ']';

    return $jsArr;
}

/**
 * 获取系统配置.
 *
 * @param $name
 * @param string $default
 *
 * @return bool|mixed
 */
function system_config($name, $default = '')
{
    $field = '';
    if (strpos($name, '.')) {
        list($name, $field) = explode('.', $name);
    }
    $data = db('system')
        ->where(['type' => $name])
        ->value('data');
    $data = json_decode($data, true);
    if (!empty($data)) {
        if ($field && isset($data[$field])) {
            return $data[$field];
        }

        return $data;
    } else {
        return $default;
    }
}

/**
 * 返回文件类型.
 *
 * @param $imagePath
 *
 * @return bool|mixed|string
 */
function getImageMime($imagePath)
{
    if (file_exists($imagePath)) {
        //Array
        //(
        //    [0] => 600
        //    [1] => 1064
        //    [2] => 3
        //    [3] => width="600" height="1064"
        //    [bits] => 8
        //    [mime] => image/png
        //)
        $image = getimagesize($imagePath);
        $mime = $image['mime'];
        $mime = explode('/', $mime);

        return $mime[1];
    }

    return false;
}

/**
 * 修改图片尺寸.
 *
 * @param string $imagePath 要修改的图片的路径
 * @param $width
 * @param $height
 *
 * @return false|resource resource
 */
function resizeImage($imagePath, $width, $height)
{
    $ext = getImageMime($imagePath);
    $funName = "imagecreatefrom{$ext}";
    $im = $funName($imagePath);

    $x = imagesx($im);
    $y = imagesy($im);
    if ($x <= $width && $y <= $height) {
        $return = $im;
    } else {
        if ($x >= $y) {
            $newx = $width;
            $newy = $newx * $y / $x;
        } else {
            $newy = $height;
            $newx = $x / $y * $newy;
        }
        $im2 = imagecreatetruecolor($newx, $newy);
        imagecopyresized($im2, $im, 0, 0, 0, 0, floor($newx), floor($newy), $x, $y);

        $return = $im2;
    }

    return $return;
}

/**
 * 生成海报.
 *
 * @param $backgroundPath 海报模板路径
 * @param $waterPath 二维码路径
 * @param $waterWidth 修改二维码宽度
 * @param $waterHeight 修改二维码高度
 * @param $waterLeft 二维码X坐标
 * @param $waterTop 二维码Y坐标
 * @param $posterPath 生成的海报保存路径
 *
 * @return mixed
 */
function makePoster($backgroundPath, $waterPath, $waterWidth, $waterHeight, $waterLeft, $waterTop, $posterPath)
{
    $im = resizeImage($waterPath, $waterWidth, $waterHeight);
    $posterIm = mergeImage($backgroundPath, $im, $waterLeft, $waterTop);

    return saveImage($posterIm, $posterPath);
}

function mergeImage($img1, $img2, $left, $top)
{
    if (is_resource($img1)) {
        $im1 = $img1;
    } else {
        $ext1 = getImageMime($img1);
        $funName = "imagecreatefrom{$ext1}";
        $im1 = $funName($img1);
    }

    if (is_resource($img2)) {
        $im2 = $img2;
    } else {
        $ext2 = getImageMime($img2);
        $funName = "imagecreatefrom{$ext2}";
        $im2 = $funName($img2);
    }
    $width2 = imagesx($im2);
    $height2 = imagesy($im2);

    imagecopymerge($im1, $im2, $left, $top, 0, 0, $width2, $height2, 100);

    return $im1;
}

/**
 * 将图片资源生成图片文件.
 *
 * @param $im
 * @param $savePath
 *
 * @return mixed
 */
function saveImage($im, $savePath)
{
    $array = explode('.', $savePath);
    $ext = strtolower(end($array));
    $ext = $ext == 'jpg' ? 'jpeg' : $ext;
    $funName = "image{$ext}";

    return $funName($im, $savePath);
}

/**
 * 生成二维码图片.
 *
 * @param $string
 * @param int $width
 * @param int $height
 * @param int $margin
 * @param bool $real_path
 *
 * @return bool
 */
function makeQrCode($string, $width = 300, $height = 300, $margin = 2.5, $real_path = false)
{
    trace($string, 'debug');
    if ($string) {
        $path = Config::get('filesystem.disks.public.root');
        $name = md5($string . $width . $height . $margin) . '.png';
        $save_path = $path . '/' . $name;
        if (!file_exists($save_path)) {
            $qrCode = new QrCode($string);
            $qrCode->setSize($width);
            $qrCode->setWriterByName('png');
            $qrCode->setMargin($margin);
            $qrCode->writeFile($save_path);
        }
        $url = Config::get('filesystem.disks.public.url') . '/' . $name;

        return $real_path ? $save_path : $url;
    } else {
        return false;
    }
}

/**
 * 解密小程序参数.
 *
 * @param $str
 *
 * @return string
 */
function decode($str)
{
    $staticchars = 'PXhw7UT1B0a9kQDKZsjIASmOezxYG4CHo5Jyfg2b8FLpEvRr3WtVnlqMidu6cN';
    $decodechars = '';
    for ($i = 1; $i < strlen($str);) {
        $num0 = strpos($staticchars, $str[$i]);
        if ($num0 !== false) {
            $num1 = ($num0 + 59) % 62;
            $code = $staticchars[$num1];
        } else {
            $code = $str[$i];
        }
        $decodechars .= $code;
        $i += 3;
    }

    return $decodechars;
}

function getmakeQrCode($data = array())
{
    $APPID = system_config('wechat')['AppID'];
    $state = base64_encode(json_encode($data));
    $redirect_url = 'https://' . Request::host() . '/index/Wechatsever.html';
    return makeQrCode("https://open.weixin.qq.com/connect/oauth2/authorize?appid=$APPID&redirect_uri=" . urlencode($redirect_url) . "&response_type=code&scope=snsapi_base&state=$state#wechat_redirect");

}

/**
 * @param string $scene 小程序参数 eg. printer_id=1
 * @param string $page 小程序页面路径
 *
 * @return string
 *
 * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
 * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
 */
function getMiniQrcode($scene = '', $page = 'pages/index/index', $refresh = 0)
{
    $savepath = \config('filesystem.disks.public.root') . '/printer_mini_code';
    $filename = md5($scene) . '.png';

    if (!file_exists($savepath . DIRECTORY_SEPARATOR . $filename) || $refresh == 1) {
        $app = wechat(true);
        $res = $app->app_code->getUnlimit($scene, ['page' => $page, 'width' => 500]);
        // 保存小程序码到文件
        if ($res instanceof StreamResponse) {

            $res->saveAs($savepath, $filename);
        } else {
            print_r($res);
            die;
        }
    }

    return \config('filesystem.disks.public.url') . '/printer_mini_code/' . $filename;
}

/**
 * 返回打印价格
 *
 * @param $printer_id
 * @param $type
 *
 * @return int
 */
function getPrinterPrice($printer_id, $type)
{
    /**
     * type:
     *  1:普通文档   黑白单面
     *  2:普通文档   黑白双面
     *  3:普通文档   彩色单面
     *  4:普通文档   彩色双面
     *  5:拍照复印   黑白单面
     *  6:拍照复印   黑白双面
     *  7:拍照复印   彩色单面
     *  8:拍照复印   彩色双面
     *  9:身份证复印 黑白单面
     * 10:身份证复印 彩色单面
     * 11:相片打印.
     */
    $where = [
        [
            'printer_id',
            '=',
            $printer_id,
        ],
        [
            'type',
            '=',
            $type,
        ],
    ];

    return Db::table('do_printer_price')
        ->where($where)
        ->value('price');
}

/**
 * 获取doc文件页数.
 *
 * @param $filename
 *
 * @return mixed
 */
function getWordPages($filename, $file_type)
{
    switch ($file_type) {
        case 'application/pdf':
            $pdftext = file_get_contents($filename);
            $pages = preg_match_all("/\/Page\W/", $pdftext, $dummy);
            break;
        case 'application/msword':
        case 'application/CDFV2':
            if (class_exists('COM')) {
                try {
                    $word = new COM('word.application');
                    $word->Visible = 0;
                    $document = $word->Documents->Open($filename, false, false, false, '1', '1', true);
                    $document->Repaginate;
                    $pages = $document->BuiltInDocumentProperties(14)->Value;
                    $word->Quit();
                } catch (Exception $e) {
                    Log::write('com组件异常');
                    Log::write($e->getMessage());
                    $pages = 1;
                }
            } else {
                $pages = 1;
            }
            break;
        case 'application/zip':
            $zip = new ZipArchive();
            if ($zip->open($filename) === true) {
                if (($index = $zip->locateName('docProps/app.xml')) !== false) {
                    $data = $zip->getFromIndex($index);
                    $data = XML::parse($data);
                    Log::write($data);
                    $pages = $data['Pages'] ?? 1;
                } else {
                    $pages = 1;
                }
                $zip->close();
            } else {
                $pages = 1;
            }
            break;
        case 'application/vnd.ms-powerpoint':
            $power = new COM('Powerpoint.Application');
            $power->visible = true;
            $power->Presentations->Open(realpath($filename));
            $pages = $power->ActivePresentation->Slides->Count;
            $power->ActivePresentation->Close();
            $power->Quit();
            break;
        default:
            $pages = 1;
    }

    return $pages;
}

/**
 * doc,ppt,xls转pdf 返回转换后的文件路径及pdf页数.
 *
 * @param $input
 *
 * @return array|bool
 */
function office2pdf($input)
{
    $exts = [
        'doc',
        'docx',
        'ppt',
        'pptx',
        'xls',
        'xlsx',
        'pdf',
    ];
    $ext = explode('.', $input);
    $ext = end($ext);
    if (!in_array($ext, $exts) || !file_exists($input)) {
        return false;
    }

    $root = config('filesystem.disks.public.root') . '/';
    $base_url = config('filesystem.disks.public.url');
    $date_dir = date('Ymd');
    if (!is_dir($root . $date_dir)) {
        @mkdir($root . $date_dir, '0777', true) && !is_dir($root);
    }
    $filename = md5(microtime()) . '.pdf';
    $output = $root . '/' . $date_dir . '/' . $filename;
    $output_path = $base_url . '/' . $date_dir . '/' . $filename;

    $office = '';
    switch ($ext) {
        case 'doc':
        case 'docx':
            try {
                $office = new COM('word.application') or die("Can't start Word!");
                $office->Visible = 0;
                $office->Documents->Open($input, false, false, false, '1', '1', true);
                $office->ActiveDocument->final = false;
                $office->ActiveDocument->Saved = true;
                $office->ActiveDocument->ExportAsFixedFormat($output, 17, false, 0, 3, 1, 5000, 7, true, true, 1);
                $office->ActiveDocument->Close();
                $office->Quit();
            } catch (Exception $e) {
                Log::write('word转pdf失败');
                Log::write(iconv('gb2312', 'utf-8', $e->getMessage()));
                if (method_exists($office, 'Quit')) {
                    $office->Quit();
                }
            }
            break;
        case 'ppt':
        case 'pptx':
            try {
                $office = new COM('powerpoint.application');
                $presentation = $office->Presentations->Open($input, false, false, false);
                $presentation->SaveAs($output, 32, 1);
                $presentation->Close();
                $office->Quit();
            } catch (Exception $e) {
                Log::write('ppt转pdf失败');
                Log::write(iconv('gb2312', 'utf-8', $e->getMessage()));
                if (method_exists($office, 'Quit')) {
                    $office->Quit();
                }
            }
            break;
        case 'xls':
        case 'xlsx':
            try {
                $office = new COM('excel.application') or die('Unable to instantiate excel');
                $workbook = $office->Workbooks->Open($input, null, false, null, '1', '1', true);
                $workbook->ExportAsFixedFormat(0, $output);
                $workbook->Close();
                $office->Quit();
            } catch (Exception $e) {
                Log::write('xls,xlsx转pdf失败');
                Log::write(iconv('gb2312', 'utf-8', $e->getMessage()));
                if (method_exists($office, 'Quit')) {
                    $office->Quit();
                }
            }
            break;
        case 'pdf':
            $output = $input;
            $output_path = str_replace(config('filesystem.disks.public.root'), $base_url, $input);
            break;
        default:
            return false;
    }

    if (is_file($output)) {
        $pdf_file = file_get_contents($output);
        $pages = preg_match_all("/\/Page\W/", $pdf_file, $dummy);
    } else {
        $pages = 1;
        $output_path = '';
    }

    return [
        'pages' => $pages,
        'pdf' => $output_path,
    ];
}

/**
 * 图片旋转.
 *
 * @param string $savename 图片路径
 * @param int $degrees 旋转角度
 *
 * @return string 返回旋转后的图片路径
 */
function rotateImage($savename, $degrees = 90)
{
    if (file_exists($savename)) {
        Image::open($savename)
            ->rotate($degrees)
            ->save($savename);
    }

    return $savename;
}

/**
 * 图片生成可打印尺寸 A4纸.
 *
 * @param string $savename 图片真实路径
 *
 * @return string 返回A4图片url
 */
function makePrinterA4Image($savename)
{
    $root = config('filesystem.disks.public.root');
    //A4模板真实路径
    $template_a4 = $root . '/template/template_a4.jpg';

    $tmp1 = tempnam(sys_get_temp_dir(), 'image_');
    $tmp2 = tempnam(sys_get_temp_dir(), 'image_');
    //图片缩放到A4纸尺寸,图片比A4纸小时不放大图片
    Image::open($savename)
        ->thumb(1204, 1754, Image::THUMB_SCALING)
        ->save($tmp1);
    //图片居中放到A4纸上
    Image::open($template_a4)
        ->water($tmp1, Image::WATER_CENTER)
        ->save($tmp2);

    //生成打印文件
    $file = new File($tmp2);
    $date_dir = date('Ymd');
    $ext = explode('.', $savename);
    $ext = strtolower(end($ext));
    $name = md5((string)microtime(true)) . '.' . $ext;
    $url = config('filesystem.disks.public.url') . "/{$date_dir}/" . $name;
    $save_path = config('filesystem.disks.public.root') . '/' . $date_dir;

    $file->move($save_path, $name);

    return $url;
}

/**
 * 返回文件文件输出目录.
 *
 * @param bool $ds 返回路径末尾是否带斜杠
 *
 * @return false|string
 */
function makeSavePath($ds = false)
{
    $root = \config('filesystem.disks.public.root');
    $date = date('Ymd', time());
    $savepath = $root . DIRECTORY_SEPARATOR . $date;
    if (!is_dir($savepath)) {
        if (!mkdir($savepath, 0777, true) && !is_dir($savepath)) {
            return false;
        }
    }

    return $savepath . ($ds ? DIRECTORY_SEPARATOR : '');
}

/**
 * 创建一个临时文件.
 *
 * @return false|string
 *
 * @throws Exception
 */
function makeTempFile()
{
    return tempnam(sys_get_temp_dir(), (string)random_int(1111, 9999));
}

/**
 * 创建一个外部可访问的文件,返回本地完整路径.
 *
 * @param string $ext
 *
 * @throws Exception
 */
function makeSaveFile($ext = '')
{
    $filename = md5((string)random_int(1111, 9999)) . ".{$ext}";
    $savepath = makeSavePath(true);

    $file = $savepath . $filename;
    file_put_contents($file, '');

    return $file;
}

function get_wenku_st($int)
{
    if ($int == 1) {
        return '正常显示';
    }
    if ($int == 0) {
        return '不显示';
    }
    return '未知状态';
}

function get_wenku_stype($type)
{
    switch ($type) {
        case 0:
            return '公共';
        case 1:
            return '私人';
        default:
            return '未知';
    }
}

function get_shwenku_st($int)
{
    switch ($int) {
        case 0:
            return '待审核';
        case 1:
            return '同意审批';
        case 2:
            return '驳回审批';
        default:
            return '未知状态';

    }
}

function get_wenku_type($int)
{
    if ($int == 1) {
        return 'doc';
    }
    if ($int == 2) {
        return 'docx';
    }
    if ($int == 3) {
        return 'xls';
    }
    if ($int == 4) {
        return 'xlsx';
    }
    if ($int == 5) {
        return 'ppt';
    }
    if ($int == 6) {
        return 'pptx';
    }
    if ($int == 7) {
        return 'pdf';
    }
    return '未知';

}

function getFileUrl($savepath)
{
    $root = \config('filesystem.disks.public.root');
    $base_url = \config('filesystem.disks.public.url');
    $url = str_replace(
        [
            $root,
            '\\',
        ],
        [
            $base_url,
            '/',
        ],
        $savepath
    );

    return $url;
}

function getLocalPath($url)
{
    $root = \config('filesystem.disks.public.root');
    $base_url = \config('filesystem.disks.public.url');
    $file = str_replace($base_url, $root, $url);
    if (file_exists($file)) {
        return $file;
    }

    return false;
}

/**
 * 图片转pdf.
 *
 * @param string|array $input 本地图片路径
 * @param string $format pdf尺寸 mm
 *
 * @return string 转换后的pdf路径
 *
 * @throws Exception
 */
function image2pdf($input, $format = 'A4')
{
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, $format, true, 'gb2312', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->setCellMargins(0, 0, 0, 0);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(true, 0);
    if (is_string($input)) {
        $pdf->AddPage();
        $pdf->Image($input);
    }
    if (is_array($input) && count($input) > 0) {
        foreach ($input as $img) {
            $pdf->AddPage();
            $pdf->Image($img);
        }
    }
    $savename = makeSaveFile('pdf');
    $data = $pdf->Output($savename, 'S');
    file_put_contents($savename, $data);

    return getFileUrl($savename);
}

/**
 * 毫米转像素.
 *
 * @param $mm
 *
 * @return float
 */
function mm2px($mm)
{
    $pt_per_px = 0.75;
    $in_per_pt = 72;
    $cm_per_pt = 28.3;
    $mm_per_pt = 2.83;
    $emu_per_px = 9525;

    return round(mm2pt($mm) * $pt_per_px, 0);
}

/**
 * 毫米转pt.
 *
 * @param $mm
 *
 * @return float
 */
function mm2pt($mm)
{
    $pt_per_px = 0.75;
    $in_per_pt = 72;
    $cm_per_pt = 28.3;
    $mm_per_pt = 2.83;
    $emu_per_px = 9525;

    return round($mm * $mm_per_pt, 0);
}

/**
 * 彩色图片转灰度图片.
 *
 * @param string $savepath 要转换的图片路径
 *
 * @return string
 */
function img2gray($savepath)
{
    $info = getimagesize($savepath);
    $ext = image_type_to_extension($info[2], false);
    $ext = strtolower($ext);
    $fun = "imagecreatefrom{$ext}";
    $im = $fun($savepath);

    imagefilter($im, IMG_FILTER_GRAYSCALE);
    //保存图像
    $output = makeSaveFile($ext);
    if ('jpeg' === $ext || 'jpg' === $ext) {
        //JPEG图像设置隔行扫描
        imageinterlace($im, true);
        imagejpeg($im, $output, 100);
    } elseif ('png' === $ext) {
        //设定保存完整的 alpha 通道信息
        imagesavealpha($im, true);
        //ImagePNG生成图像的质量范围从0到9的
        imagepng($im, $output, min((int)(100 / 10), 9));
    } else {
        $fun = 'image' . $ext;
        $fun($im, $output);
    }

    return getFileUrl($output);
}

/**
 * easywechat小程序初始化
 * @param bool $app true:app false:payment
 */
function wechat($app = true,$type = 'mini_wx')
{
    //^ array:7 [
    //  "name" => "云打印"
    //  "appid" => "wx42c6560f56dd6"
    //  "secret" => "bd452166f1c70f20b181591d4a0fb"
    //  "mch_id" => "1609797"
    //  "key" => "shanxishengchangzhisluchengquwan"
    //  "client_key" => "https://tp.funn.cn/storage/20210524/7923e4e646ef8485.pem"
    //  "client_cert" => "https://tp.fuun.cn/storage/20210524/0cdd6c22d9e79913.pem"
    //]
    $data = system_config($type);
    $type_val= $type=="mini_wx"?'wx':'qq';
    $config = [
        'app_id' => $data['appid'],
        'secret' => $data['secret'],
        'mch_id' => $data['mch_id'],
        'key' => $data['key'],
        'cert_path' => $data['client_cert']??app()->getRootPath() . 'extend/'.$type_val.'/cert/apiclient_cert.pem',
        'key_path' => $data['client_key']??app()->getRootPath() . 'extend/'.$type_val.'/cert/apiclient_key.pem',
        'rsa_public_key_path' => $data['rsa_public_key']??app()->getRootPath() . 'extend/'.$type_val.'/cert/rsa_public_key_v8.pem',
        'notify_url' => (string)url('api/notify/index', [], null, true),
        'refund_url' => (string)url('api/notify/refund', [], null, true),
        'response_type' => 'array',
        'log' => [
            'default' => 'dev',
            'channels' => [
                'dev' => [
                    'driver' => 'single',
                    'path' => app()->getRuntimePath() . '/log/' . date('Ym') . '/easywechat_' . date('d') . '.log',
                    'level' => 'debug',
                ],
                'prod' => [
                    'driver' => 'daily',
                    'path' => app()->getRuntimePath() . '/log/' . date('Ym') . '/easywechat_' . date('d') . '.log',
                    'level' => 'info',
                ],
            ],
        ],
        'http' => [
            'max_retries' => 1,
            'retry_delay' => 500,
            'timeout' => 30,
        ],
    ];

    if ($app) {
        $app = Factory::miniProgram($config);
    } else {
        $app = Factory::payment($config);
    }
    return $app;
}

/**
 * easywechat公众号初始化
 * @param bool $app true:app false:payment
 */
function wechatAccount()
{
    $data = system_config('wechat');
    $config = [
        'app_id' => $data['AppID'],
        'secret' => $data['AppSecret'],
        'response_type' => 'array',
        'log' => [
            'default' => 'dev',
            'channels' => [
                'dev' => [
                    'driver' => 'single',
                    'path' => app()->getRuntimePath() . '/log/' . date('Ym') . '/easyAccount_' . date('d') . '.log',
                    'level' => 'debug',
                ],
                'prod' => [
                    'driver' => 'daily',
                    'path' => app()->getRuntimePath() . '/log/' . date('Ym') . '/easyAccount_' . date('d') . '.log',
                    'level' => 'info',
                ],
            ],
        ],
        'http' => [
            'max_retries' => 1,
            'retry_delay' => 500,
            'timeout' => 30,
        ],
    ];

    $app = Factory::officialAccount($config);
    if (empty($data['Tips_Order'])){
        if(setIndustry($app)){
            $res = $app->template_message->addTemplate('OPENTM417724456');
            if ($res['errcode'] == 0){
                $data['Tips_Order'] = $res['template_id'];
                Db::table('do_system')->where(['type'=>'wechat'])->update(["data"=>json_encode($data)]);
            }
        }
    }
    if (empty($data['Tips_Refund'])){
        if(setIndustry($app)){
            $res = $app->template_message->addTemplate('OPENTM418001675');
            if ($res['errcode'] == 0){
                $data['Tips_Refund'] = $res['template_id'];
                Db::table('do_system')->where(['type'=>'wechat'])->update(["data"=>json_encode($data)]);
            }
        }
    }
    if (empty($data['Tips_Device_Error'])){
        if(setIndustry($app)){
            $res = $app->template_message->addTemplate('OPENTM417958202');
            if ($res['errcode'] == 0){
                $data['Tips_Device_Error'] = $res['template_id'];
                Db::table('do_system')->where(['type'=>'wechat'])->update(["data"=>json_encode($data)]);
            }
        }
    }
    if (empty($data['Tips_Withdrawal'])){
        if(setIndustry($app)){
            $res = $app->template_message->addTemplate('OPENTM408064835');
            if ($res['errcode'] == 0){
                $data['Tips_Withdrawal'] = $res['template_id'];
                Db::table('do_system')->where(['type'=>'wechat'])->update(["data"=>json_encode($data)]);
            }
        }
    }
    return $app;
}

/**
 * 检测并设置制定行业
 * @param $app
 */
function setIndustry($app,$industryId1 = 3, $industryId2 = 2){
    $Industry = $app->template_message->getIndustry();
    if (!empty($Industry['primary_industry'])){
        if(wechatIndustryToType($Industry['primary_industry']['first_class'],$Industry['primary_industry']['second_class']) != $industryId1
            && wechatIndustryToType($Industry['primary_industry']['first_class'],$Industry['primary_industry']['second_class']) != $industryId2
            && wechatIndustryToType($Industry['secondary_industry']['first_class'],$Industry['secondary_industry']['second_class']) != $industryId1
            && wechatIndustryToType($Industry['secondary_industry']['first_class'],$Industry['secondary_industry']['second_class']) != $industryId2){
            $data = $app->template_message->setIndustry($industryId1, $industryId2);
            if ($data['errcode'] === 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }
    return false;
}

/**
 * 微信公众号行业描述转code
 * @param $first_class
 * @param $second_class
 * @return mixed
 */
function wechatIndustryToType($first_class,$second_class){
    $data = [
        'IT科技'=>[
            'IT软件与服务'=>2,
            'IT硬件与设备'=>3,
            '电子技术'=>4,
            '互联网/电子商务'=>1,
            '通信与运营商'=>5,
            '网络游戏'=>6
        ],
        '餐饮'=>[
            '餐饮'=>10
        ],
        '房地产'=>[
            '建筑'=>29,
            '物业'=>30
        ],
        '交通工具'=>[
            '飞机相关'=>28,
            '火车相关'=>27,
            '摩托车相关'=>26,
            '汽车相关'=>25
        ],
        '教育'=>[
            '培训'=>16,
            '院校'=>17
        ],
        '金融业'=>[
            '保险'=>9,
            '基金理财信托'=>8,
            '银行'=>7
        ],
        '酒店旅游'=>[
            '酒店'=>11,
            '旅游'=>12
        ],
        '其它'=>[
            '其它'=> 41
        ],
        '商业服务'=>[
            '法律'=>32,
            '会展'=>33,
            '认证'=>35,
            '审计'=>36,
            '中介服务'=>34
        ],
        '文体娱乐'=>[
            '传媒'=>37,
            '体育'=>38,
            '娱乐休闲'=>39
        ],
        '消费品'=>[
            '消费品'=>31
        ],
        '医药护理'=>[
            '保健与卫生'=>24,
            '护理美容'=>23,
            '医药医疗'=>22
        ],
        '打印'=>[
            '印刷'=>40
        ],
        '运输与仓储'=>[
            '仓储'=>15,
            '快递'=>13,
            '物流'=>14
        ],
        '政府与公共事业'=>[
            '博物馆'=>20,
            '公共事业非盈利机构'=>21,
            '交警'=>19,
            '学术科研'=>18
        ],
    ];
    return $data[$first_class][$second_class];
}

/**
 * 打印参数设置
 * @param $data
 * @return array
 */
function get_dy_params($data)
{
    $printer = $data['order']['printer'];
    switch ($data['page_type']) {
        case 1:
            $dmPaperSize = $printer['config']['a4']['papers'] ?? 9;
            $jpAutoScale = $printer['config']['a4']['jpAutoScale'] ?? $printer['config']['jpAutoScale'] ?? 4;
            $jpAutoAlign = $printer['config']['a4']['jpAutoAlign'] ?? $printer['config']['jpAutoAlign'] ?? "z5";
            $dmPrintQuality = $printer['config']['a4']['dmPrintQuality'] ?? -4;
            $dmMediaType = $printer['config']['a4']['dmMediaType'] ?? 1;
            $dmOrientation = $data['orientation']??1;
            $dmDefaultSource = $printer['config']['a4']['dmDefaultSource'] ?? 1;
            break;
        case 2:
            $dmPaperSize = $printer['config']['c6']['papers'] ?? 9;
            $jpAutoScale = $printer['config']['c6']['jpAutoScale'] ?? $printer['config']['jpAutoScale'] ?? 4;
            $jpAutoAlign = $printer['config']['c6']['jpAutoAlign'] ?? $printer['config']['jpAutoAlign'] ?? "z5";
            $dmMediaType = $printer['config']['c6']['dmMediaType'] ?? 1;
            $dmDefaultSource = $printer['config']['c6']['dmDefaultSource'] ?? 1;
            $dmPrintQuality = $printer['config']['c6']['dmPrintQuality'] ?? -4;
            $dmOrientation = $data['orientation']??1;
            break;
        case 3:
            $dmPaperSize = $printer['config']['a3']['papers'] ?? 9;
            $jpAutoScale = $printer['config']['a3']['jpAutoScale'] ?? $printer['config']['jpAutoScale'] ?? 4;
            $jpAutoAlign = $printer['config']['a3']['jpAutoAlign'] ?? $printer['config']['jpAutoAlign'] ?? "z5";
            $dmMediaType = $printer['config']['a3']['dmMediaType'] ?? 1;
            $dmDefaultSource = $printer['config']['a3']['dmDefaultSource'] ?? 1;
            $dmPrintQuality = $printer['config']['a3']['dmPrintQuality'] ?? -4;
            $dmOrientation = $data['orientation']??1;
            break;
        default:
            $dmPaperSize = $printer['config']['a4'] ?? 9;
            $jpAutoScale = $printer['config']['a4']['jpAutoScale'] ?? $printer['config']['jpAutoScale'] ?? 4;
            $jpAutoAlign = $printer['config']['a4']['jpAutoAlign'] ?? $printer['config']['jpAutoAlign'] ?? "z5";
            $dmOrientation = $data['orientation'];
            $dmMediaType = $printer['config']['a4']['dmMediaType'] ?? 1;
            $dmPrintQuality = $printer['config']['a4']['dmPrintQuality'] ?? -4;
            $dmDefaultSource = $printer['config']['a4']['dmDefaultSource'] ?? 1;
    }

    $params = [
        "dmPaperSize" => $dmPaperSize,
        "dmOrientation" => $dmOrientation,
        "dmCopies" => $data['copies'],
        "dmDefaultSource" => $dmDefaultSource,
        "dmColor" => $data['color'],
        "dmDuplex" => $data['side'],
        "dmMediaType" => $dmMediaType,
        "dmPrintQuality" => $dmPrintQuality,
        "isPreview" => "0",
        "devicePort" => $printer['device_port'] ?? 1,
        "htmlKernel" => "chrome",
        "jpPageRange" => $data['page_start'] . "-" . $data['page_end'],
        "jpAutoScale" => $jpAutoScale,
        "jpAutoAlign" => $jpAutoAlign,
    ];
    return $params;
}

/**
 * 更新打印机中纸张数量
 * @param integer $cart_id 打印任务id
 */
function updatePapers($cart_id)
{
    $data = Db::table("do_cart")->where(["id" => $cart_id])->find();
    $pages = ($data["page_end"] - $data["page_start"] + 1) * $data['copies'];
    $page_type = $data["page_type"];

    //打印机id
    $print_id = Db::table("do_order")->where(["id" => $data["order_id"]])->value("printer_id");
    $printer_info = Db::table("do_printer")->where(["id" => $print_id])->find();
    switch ($page_type) {
        case 1:
            $field = "page_a4";
            $Tips = system_config('wechat.Tips_A4');
            $text = "A4";
            break;
        case 2:
            $field = "page_photo";
            $Tips = system_config('wechat.Tips_C6');
            $text = "照片";
            break;
        case 3:
            $field = "page_a3";
            $Tips = system_config('wechat.Tips_A3');
            $text = "A3";
            break;
        default:
            $field = "page_a4";
            $Tips = system_config('wechat.Tips_A4');
            $text = "A4";
    }


    if ($printer_info[$field] - $pages < $Tips) {
        send_Notice_printer_all($print_id, $text, $printer_info[$field] - $pages);
    }

    if ($printer_info[$field] - $pages > 0) {
        return Db::table("do_printer")->where(["id" => $print_id])->dec($field, $pages)->update();
    } else {
        return Db::table("do_printer")->where(["id" => $print_id])->update([$field => 0]);
    }

}

/**
 * 推送退款订单通知
 * @param $or_id
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\DbException
 * @throws \think\db\exception\ModelNotFoundException
 */
function send_Notice_refund_all($or_id)
{
    $or_info = Db::table("do_order")
        ->alias('a')
        ->field('a.*,b.shop_name')
        ->leftJoin("do_shop b", 'a.shop_id = b.id')
        ->where('a.id', $or_id)
        ->find();

    if ($or_info['proxy_id'] === 0) {
        $proxy_id = -1;
    } else {
        $proxy_id = $or_info['proxy_id'];
    }

    if ($or_info['shop_id'] != 0) {
        $shop_id = $or_info['shop_id'];
    } else {
        $shop_id = -1;
    }

    $where[] = ['a.notice_type', '=', 1];
    $where[] = ['a.notice_id', '>', 0];
    $user = Db::table("do_mini_user")
        ->alias('a')
        ->leftJoin('do_notice b', 'a.notice_id = b.id')
        ->field("b.openid")
        ->where($where)
        ->whereRaw('a.proxy_id in(-1,:proxy_id) or (b.shop_id = :shop_id)', ['proxy_id' => $proxy_id, 'shop_id' => $shop_id])
        ->select()
        ->toArray();

    $color = "#848484";
    $template_id = "poG-312AhWs-21y-8enLxQrz43NmP6KqL0sUZO3wNHA";
    $data = [
        "keyword1" => $or_info['shop_name'],
        "keyword2" => $or_info['out_trade_no'],
        "keyword3" => sprintf("%.2f", $or_info['total_fee'] / 100) . "元",
        "keyword4" => $or_info['out_refund_msg']
    ];
    $template = NoticeConfig("refund", $data, $color);
    $wx = wechatAccount();
    foreach ($user as $k => $v) {
        $template['touser'] = $v["openid"];
        $wx->template_message->send($template);
    }
}

/**
 * 推送设备异常通知
 * @param $print_id
 * @param $page_type
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\DbException
 * @throws \think\db\exception\ModelNotFoundException
 */
function send_Notice_printer_all($print_id, $page_type, $sum)
{
    $or_info = Db::table("do_printer")
        ->alias('a')
        ->field('a.*,b.shop_name')
        ->join("do_shop b", 'a.shop_id = b.id')
        ->where('a.id', $print_id)
        ->find();

    if ($or_info['proxy_id'] === 0) {
        $proxy_id = -1;
    } else {
        $proxy_id = $or_info['proxy_id'];
    }

    if ($or_info['shop_id'] != 0) {
        $shop_id = $or_info['shop_id'];
    } else {
        $shop_id = -1;
    }
    $where[] = ['a.notice_type', '=', 1];
    $where[] = ['a.notice_id', '>', 0];
    $user = Db::table("do_mini_user")
        ->alias('a')
        ->leftJoin('do_notice b', 'a.notice_id = b.id')
        ->field("b.openid")
        ->where($where)
        ->whereRaw('a.proxy_id in(-1,:proxy_id) or (b.shop_id = :shop_id)', ['proxy_id' => $proxy_id, 'shop_id' => $shop_id])
        ->select()
        ->toArray();

    $color = "#848484";
    $template_id = "Kqwne3Gdpdo2mWtFO-mCimoW1HqP2T1xX1Pj4_2smqs";
    $data = [
        "keyword1" => $or_info['device_id'],
        "keyword2" => '设备缺纸,' . $page_type . '纸张现存:' . $sum,
        "keyword3" => (string)date("Y-m-d H:i:s"),
        "keyword4" => $or_info['shop_name']
    ];
    $template = NoticeConfig("device_error", $data, $color);
    $wx = wechatAccount();
    foreach ($user as $k => $v) {
        $template['touser'] = $v["openid"];
        $wx->template_message->send($template);
    }

}

/**
 * 推送打印订单通知
 * @param $out_trade_no
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\DbException
 * @throws \think\db\exception\ModelNotFoundException
 */
function send_Notice_order_all($out_trade_no)
{
    $or_info = Db::table("do_order")
        ->alias('a')
        ->field('a.*,b.shop_name')
        ->leftjoin("do_shop b", 'a.shop_id = b.id')
        ->where('a.out_trade_no', $out_trade_no)
        ->find();

    if ($or_info['proxy_id'] === 0) {
        $proxy_id = -1;
    } else {
        $proxy_id = $or_info['proxy_id'];
    }

    if ($or_info['shop_id'] != 0) {
        $shop_id = $or_info['shop_id'];
    } else {
        $shop_id = -1;
    }

    $where[] = ['a.notice_type', '=', 1];
    $where[] = ['a.notice_id', '>', 0];
    $user = Db::table("do_mini_user")
        ->alias('a')
        ->leftJoin('do_notice b', 'a.notice_id = b.id')
        ->field("b.openid")
        ->where($where)
        ->whereRaw('a.proxy_id in(-1,:proxy_id) or (b.shop_id = :shop_id)', ['proxy_id' => $proxy_id, 'shop_id' => $shop_id])
        ->select()
        ->toArray();

    $color = "#848484";
    $template_id = "D9anyoaG-wt7LyzP55_VJ6jlTASwQVPsARsiELF0joA";
    $data = [
        "keyword1" => $out_trade_no,
        "keyword2" => (string)date('Y-m-d H:i:s', $or_info['inserttime']),
        "keyword3" => $or_info['shop_name'],
        "keyword4" => "金额：" . sprintf("%.2f", $or_info['total_fee'] / 100) . "元"
    ];
    $template = NoticeConfig("order", $data, $color);
    $wx = wechatAccount();
    foreach ($user as $k => $v) {
        $template['touser'] = $v["openid"];
        $wx->template_message->send($template);
    }
}

/**
 * 模板消息参数合成
 * @param $type
 * @param $template_id
 * @param array $data
 * @param string $color
 * @return array
 */
function NoticeConfig($type, $data = array(), $color = "#848484")
{
    switch ($type) {
        case 'order' :
            $template = [
                'template_id' => system_config("wechat.Tips_Order")??"D9anyoaG-wt7LyzP55_VJ6jlTASwQVPsARsiELF0joA",
                'data' => [
                    'first' => [
                        'value' => "唯艺提醒您，有新打印订单：",
                        'color' => $color
                    ],
                    "remark" => [
                        'value' => "感谢你的使用",
                        'color' => $color
                    ]
                ],
            ];
            break;
        case 'refund' :
            $template = [
                'template_id' => system_config("wechat.Tips_Refund")??"poG-312AhWs-21y-8enLxQrz43NmP6KqL0sUZO3wNHA",
                'data' => [
                    'first' => [
                        'value' => "唯艺提醒您，有新的退款申请：",
                        'color' => $color
                    ],
                    "remark" => [
                        'value' => "请尽快处理审批！",
                        'color' => $color
                    ]
                ],
            ];
            break;
        case 'device_error' :
            $template = [
                'template_id' => system_config("wechat.Tips_Device_Error")??"Kqwne3Gdpdo2mWtFO-mCimoW1HqP2T1xX1Pj4_2smqs",
                'data' => [
                    'first' => [
                        'value' => "唯艺提醒您，有新的设备异常：",
                        'color' => $color
                    ],
                    "remark" => [
                        'value' => "请尽快检查设备！",
                        'color' => $color
                    ]
                ],
            ];
            break;
        case 'withdrawal' :
            $template = [
                'template_id' => system_config("wechat.Tips_Withdrawal")??"OYjUJORCi0GpgVf7PVI9LNn8OujHj7m0HE-Ax1jI6Y8",
                'data' => [
                    'first' => [
                        'value' => "唯艺提醒您，您有一笔提现到账：",
                        'color' => $color
                    ],
                    "remark" => [
                        'value' => "请尽快查收！",
                        'color' => $color
                    ]
                ],
            ];
            break;
        case 'debug':
            $template = [
                'template_id' => system_config("wechat.Tips_Order")??"D9anyoaG-wt7LyzP55_VJ6jlTASwQVPsARsiELF0joA",
                'data' => [
                    'first' => [
                        'value' => "消息测试标题",
                        'color' => $color
                    ],
                    "remark" => [
                        'value' => "消息测试备注",
                        'color' => $color
                    ]
                ],
            ];
            break;
        default:
            $template = [
                'template_id' => system_config("wechat.Tips_Order")??"D9anyoaG-wt7LyzP55_VJ6jlTASwQVPsARsiELF0joA",
                'data' => [
                    'first' => [
                        'value' => "未定义参数，异常消息",
                        'color' => $color
                    ],
                    "remark" => [
                        'value' => "未定义参数，异常消息",
                        'color' => $color
                    ]
                ],
            ];
            break;
    }

    foreach ($data as $k => $v) {
        if (!empty($v)) {
            $template['data'][$k]["value"] = $v;
            $template['data'][$k]["color"] = $color;
        }
    }

    return $template;
}

/**
 * 合成6寸照片
 * @param $cart_id
 * @param string $log_url
 * @return bool
 * @throws \think\db\exception\DbException
 */
function MakePhotos($cart_id, $log_url = './logo/LOGO.png')
{
    $file = Db::table('do_cart')->where(['id' => $cart_id])->value("file");
    if (!empty($file) || $file != '') {
        $imge_file = str_replace("https://wx.scweichuang.com", ".", $file);
        if (file_exists($imge_file)) {
            $mkdir = './MakePhotos/' . date('Ym') ;
            if (!is_dir($mkdir)) {
                mkdir($mkdir);
                if (!is_dir('./MakePhotos')) {
                    mkdir($mkdir);
                }
            }
            $imge = \think\Image::open($imge_file);
            $_log = $mkdir ."/". md5((string)microtime(true)) . '.png';
            if($imge->height() > 1287||$imge->width()>864){
                $imge_file = $mkdir ."/". md5((string)microtime(true)) . '.png';
                $imge->thumb(864,1287,6)->save($imge_file);
                \think\Image::open('./logo/ditu.png')->water($imge_file, [78, 78])->save($_log);
                unlink($imge_file);
            } else {
                \think\Image::open('./logo/ditu.png')->water($imge_file, [78, 78])->save($_log);
            }
            $cp_img = \think\Image::open($_log);
            $file_photo = $mkdir ."/". md5((string)microtime(true)) . '.png';
            $cp_img->water($log_url, [652, 1218])->save($file_photo);
            $file_photo = str_replace( "./","https://wx.scweichuang.com/", $file_photo);
            Db::table('do_cart')->where(['id' => $cart_id])->update(['file_photo' => $file_photo]);
            unlink($_log);
        }
    }
    return false;
}

function downloadExcel(array $header,array $data, $filename = '', $format = 'Xls')

{
    $newExcel = new Spreadsheet();  //创建一个新的excel文档
    $objSheet = $newExcel->getActiveSheet();  //获取当前操作sheet的对象
    $objSheet->setTitle('Sheet1');  //设置当前sheet的标题

    $span = ord("A");
    foreach ($header as $val) {
        //设置宽度为true,不然太窄了
        //$objSheet->getColumnDimension(chr($span))->setAutoSize(true);
        //设置表头内容
        $objSheet->setCellValue(chr($span)."1", $val);
        $span ++;
    }

    $column = 2;
    foreach ($data as $key => $row) {
        $span = ord("A");
        foreach ($row as $keyName => $value){
            $objSheet->setCellValue(chr($span).$column, $value);
            $span ++;
        }
        $column ++;
    }
    $span = ord("A");
    foreach ($header as $val) {
        //设置宽度为true,不然太窄了
        $objSheet->getColumnDimension(chr($span))->setAutoSize(true);
        $span ++;
    }

    // $format只能为 Xlsx 或 Xls

    if ($format == 'Xlsx') {

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    } elseif ($format == 'Xls') {

        header('Content-Type: application/vnd.ms-excel');

    }


    if(empty($filename) || $filename == '') {
        $filename = md5(time());
        header("Content-Disposition: attachment;filename=". $filename . '.' . strtolower($format));
    } else {
        header("Content-Disposition: attachment;filename=". $filename . date('Y-m-d H:i:s') . '.' . strtolower($format));
    }




    header('Cache-Control: max-age=0');

    $objWriter = IOFactory::createWriter($newExcel, $format);

    $objWriter->save('php://output');

    exit;

}

/**
 * 银行编号列表，详情参考：https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=24_4
 * @params string $bank_name : 银行名称，4个汉字
 * return int $bank_code : 银行编码
 * */
function getBankCode($bank_name){
    $bank_code = 0;
    switch ($bank_name){
        case '工商银行':    $bank_code = 1002;  break;
        case '农业银行':    $bank_code = 1005;  break;
        case '建设银行':    $bank_code = 1003;  break;
        case '中国银行':    $bank_code = 1026;  break;
        case '交通银行':    $bank_code = 1020;  break;
        case '招商银行':    $bank_code = 1001;  break;
        case '邮储银行':    $bank_code = 1066;  break;
        case '民生银行':    $bank_code = 1006;  break;
        case '平安银行':    $bank_code = 1010;  break;
        case '中信银行':    $bank_code = 1021;  break;
        case '浦发银行':    $bank_code = 1004;  break;
        case '兴业银行':    $bank_code = 1009;  break;
        case '光大银行':    $bank_code = 1022;  break;
        case '广发银行':    $bank_code = 1027;  break;
        case '华夏银行':    $bank_code = 1025;  break;
        case '宁波银行':    $bank_code = 1056;  break;
        case '北京银行':    $bank_code = 4836;  break;
        case '上海银行':    $bank_code = 1024;  break;
        case '南京银行':    $bank_code = 1054;  break;
        case '长子县融汇村镇银行':    $bank_code = 4755;  break;
        case '长沙银行':    $bank_code = 4216;  break;
        case '浙江泰隆商业银行':    $bank_code = 4051;  break;
        case '中原银行':    $bank_code = 4753;  break;
        case '企业银行（中国）':    $bank_code = 4761;  break;
        case '顺德农商银行':    $bank_code = 4036;  break;
        case '衡水银行':    $bank_code = 4752;  break;
        case '长治银行':    $bank_code = 4756;  break;
        case '大同银行':    $bank_code = 4767;  break;
        case '河南省农村信用社':    $bank_code = 4115;  break;
        case '宁夏黄河农村商业银行':    $bank_code = 4150;  break;
        case '山西省农村信用社':    $bank_code = 4156;  break;
        case '安徽省农村信用社':    $bank_code = 4166;  break;
        case '甘肃省农村信用社':    $bank_code = 4157;  break;
        case '天津农村商业银行':    $bank_code = 4153;  break;
        case '广西壮族自治区农村信用社':    $bank_code = 4113;  break;
        case '陕西省农村信用社':    $bank_code = 4108;  break;
        case '深圳农村商业银行':    $bank_code = 4076;  break;
        case '宁波鄞州农村商业银行':    $bank_code = 4052;  break;
        case '浙江省农村信用社联合社':    $bank_code = 4764;  break;
        case '江苏省农村信用社联合社':    $bank_code = 4217;  break;
        case '江苏紫金农村商业银行股份有限公司':    $bank_code = 4072;  break;
        case '北京中关村银行股份有限公司':    $bank_code = 4769;  break;
        case '星展银行（中国）有限公司':    $bank_code = 4778;  break;
        case '枣庄银行股份有限公司':    $bank_code = 4766;  break;
        case '海口联合农村商业银行股份有限公司':    $bank_code = 4758;  break;
        case '南洋商业银行（中国）有限公司':    $bank_code = 4763;  break;
    }
    return $bank_code;
}

function BubbleSort(array $arr , $key)
{
    for ($i=0 ; $i <count($arr) ; $i++) {
        //设置一个空变量
        $data = '';
        for ($j=$i ; $j < count($arr)-1 ; $j++) {
            if ($arr[$i][$key] > $arr[$j+1][$key]) {
                $data      = $arr[$i][$key];
                $arr[$i][$key]   = $arr[$j+1][$key];
                $arr[$j+1][$key] = $data;
            }
        }
    }
    return $arr;
}
