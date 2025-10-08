<?php
declare (strict_types=1);

namespace app\shop\controller;

use think\facade\Db;

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
            "index", [
            "lists" => $data,
            "pages" => $data->render()
        ]);
    }
}
