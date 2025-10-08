<?php
declare (strict_types=1);

namespace app\shop\controller;

use app\BaseController;
use think\facade\Db;

class Login extends BaseController
{

    public static $table = "do_shop";

    public function index()
    {
        if (session("shop")) {
            return redirect((string)url("index/index"));
        }
        if (request()->isPost()) {
            $data = input("post.");
            $username = $data["username"];
            $password = $data["password"];
//			$phone    = $data["phone"];

            $where = [
                "account" => $username,
                "password" => $password,
//				"phone"    => $phone
            ];
            $shop = Db::table(self::$table)
                ->where($where)
                ->find();

            if (!empty($shop)) {

                session("shop", $shop);
                $user = Db::table("do_mini_user")->where(["id" => $shop["uid"]])->find();
                session("user", $user);
                $return = [
                    "code" => 0,
                    "msg" => "登录成功",
                    "url" => (string)url("index/index")
                ];
            } else {
                $return = [
                    "code" => 1,
                    "msg" => "账号或密码错误"
                ];
            }

            return json($return);
        }
        return view();
    }

    public function logout()
    {
        session("shop", null);

        return redirect((string)url("login/index"));
    }
}
