<?php

declare(strict_types=1);

namespace app\index\controller;

use think\App;
use think\facade\Db;
use GuzzleHttp\Client;
use think\facade\View;

class Index
{

    public function index()
    {
        $vars = [

        ];
        return view("", $vars);
    }
}
