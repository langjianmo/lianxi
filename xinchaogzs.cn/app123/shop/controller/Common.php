<?php
declare (strict_types=1);

namespace app\shop\controller;

use app\BaseController;
use think\App;
use think\facade\Db;
use think\facade\View;

class Common extends BaseController
{

    public $shop;

    public function initialize()
    {
        parent::initialize();
        View::assign($this->plugins());
        $shop = session("shop");
        $user = Db::table("do_mini_user")
            ->where(["id" => $shop["uid"]])
            ->find();
        if ($shop != null) {
            $this->shop = $shop;
            $this->user = $user;
            View::assign(
                [
                    "shop" => $shop,
                    "user" => $user
                ]);
        } else {
            $login = url("login/index");
            echo "未登录，<a href='{$login}'>点击登录</a>";
            die;
        }
    }

    /**
     * 前端组件生成
     * @return array
     */
    protected function plugins()
    {
        $plugins = config("view.plugins");
        $arr = [];
        foreach ($plugins as $plug_name => $v) {
            $arr[$plug_name] = "";
            if (is_array($v)) {
                foreach ($v as $url) {
                    $type = explode(".", $url);
                    switch (end($type)) {
                        case "css":
                            $arr[$plug_name] .= "<link rel='stylesheet' type='text/css' href='{$url}'>";
                            break;
                        case "js":
                            $arr[$plug_name] .= "<script type='text/javascript' src='{$url}'></script>";
                            break;
                    }
                }
            }
        }

        return $arr;
    }

    /**
     * 提示页面
     * @param $msg
     * @param string $url
     * @param string $type
     * @param int $timeout
     * @return mixed
     */
    public function message($msg, $url = "", $type = "success", $timeout = 1)
    {

        return view(
            "common/message", [
            "msg" => $msg,
            "url" => $url,
            "type" => $type,
            "timeout" => $timeout
        ]);
    }

    /**
     * ajax返回成功信息
     * @param $msg
     * @param int $code
     * @param string $url
     * @return \think\response\Json
     */
    public function success($msg, $code = 0, $url = "index")
    {
        if (\request()->isAjax()) {
            $return = [
                "code" => $code,
                "msg" => $msg,
                "url" => (string)url($url)
            ];

            return json($return);
        }
    }

}
