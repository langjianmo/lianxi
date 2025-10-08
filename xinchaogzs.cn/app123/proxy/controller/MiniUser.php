<?php

declare(strict_types=1);

namespace app\proxy\controller;

use app\model\CartModel;
use think\facade\Db;
use think\facade\View;
use think\Request;

class MiniUser extends Common
{
    public static $table = "do_mini_user";

    public function initialize()
    {
        parent::initialize();
    }

    public function index()
    {
        $data = Db::table(self::$table)
            ->order("id desc")
            ->paginate();

        return view(
            "index",
            [
                "lists" => $data,
                "pages" => $data->render()
            ]
        );
    }

    /**
     * select2组件中使用
     */
    public function users()
    {
        $where = [];

        $nickname = input("param.nickname", "", "trim");
        if ($nickname) {
            $where[] = [
                "nickname", "like", "%{$nickname}%"
            ];
        }

        $data = Db::table(self::$table)
            ->field("id,nickname text")
            ->where($where)
            ->select();
        return json($data);
    }

    public function platform()
    {

        $platform = Db::table(self::$table)
            ->field('platform as id')
            ->group('platform')
            ->select();

        foreach ($platform as $k => $v) {
            switch ($v['id']) {
                case 1 :
                    $v["text"] = "微信";
                    break;
                case 2 :
                    $v["text"] = "QQ";
                    break;
                case 3 :
                    $v["text"] = "支付宝";
                    break;
                case 4 :
                    $v["text"] = "字节";
                    break;
                case 5 :
                    $v["text"] = "百度";
                    break;
            }
            $platform[$k] = $v;
        }

        return json($platform);
    }

    public function fixPrint($id)
    {
        $where = [
            'uid' => $id,
            'print_status' => 1
        ];
        $cart = CartModel::destroy(function ($query) use ($where) {
            $query->where($where);
        });
        $return = [
            "code" => 0,
            "msg" => "操作成功{$cart}",
            "url" => (string)url('index')
        ];
        return json($return);
    }
}
