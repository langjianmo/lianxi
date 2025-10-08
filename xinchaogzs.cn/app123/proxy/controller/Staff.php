<?php

declare(strict_types=1);

namespace app\proxy\controller;

use app\common\controller\WeChatService;
use app\model\CartModel;
use think\facade\Db;
use think\facade\View;
use think\Request;

class Staff extends Common
{
    public static $table = "do_mini_user";

    public function initialize()
    {
        parent::initialize();
    }

    public function index()
    {
        $data = Db::table(self::$table)
            ->where("proxy_id", $this->proxy['id'])
            ->order("notice_type desc")
            ->order("id")
            ->paginate();

        return view(
            "index",
            [
                "lists" => $data,
                "pages" => $data->render()
            ]
        );
    }

    public function add()
    {
        if (request()->isPost()) {
            $post = input('post.');
            Db::table(self::$table)
                ->where('id', $post['id'])
                ->update(['platform' => $post['platform'], 'proxy_id' => $this->proxy['id'], 'notice_id' => $post['notice_id']]);
            $return = [
                'code' => 0,
                'msg' => '操作成功',
                'url' => (string)url('index'),
            ];

            return json($return);
        }

        $vars['proxy_id'] = $this->proxy['id'];
        $vars['shop_id'] = 0;
        do {
            $tempwxstate = md5(strval(time() + mt_rand(100000000, 999999999)));
            $wxstate = Db::table('do_notice')
                ->where('wxstate', $tempwxstate)
                ->find();
        } while ($wxstate !== null);
        $vars['wxstate'] = $tempwxstate;
        $vars['imgurl'] = getmakeQrCode($vars);
        return view('', $vars);
    }

    public function delect()
    {
        $id = input('get.id');
        Db::table(self::$table)
            ->where('id', $id)
            ->update(['platform' => 0, 'proxy_id' => 0]);
        $return = [
            'code' => 0,
            'msg' => '操作成功',
            'url' => (string)url('index'),
        ];

        return json($return);

    }


    public function notice()
    {
        $id = input('get.id');
        $notice_type = input('get.notice_type');
        Db::table(self::$table)
            ->where('id', $id)
            ->update(['notice_type' => $notice_type]);
        $return = [
            'code' => 0,
            'msg' => '操作成功',
            'url' => (string)url('index'),
        ];

        return json($return);

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

    public function sendNotice()
    {
        $openid = input('get.openid');
        $wx = new WeChatService();
        return $wx->send_refund_notice($openid);
    }

    public function postbind()
    {
        $wechat = system_config('wechat');
        $APPID = $wechat['AppID'];
        $state = base64_encode(json_encode(input('get.')));
        $res['state'] = $state;
        $redirect_url = 'https://wx.scweichuang.com/index/Wechatsever.html';
        $res['code'] = 0;
        $res['url'] = makeQrCode("https://open.weixin.qq.com/connect/oauth2/authorize?appid=$APPID&redirect_uri=" . urlencode($redirect_url) . "&response_type=code&scope=snsapi_base&state=$state#wechat_redirect");
        return json($res);
    }

    public function queryOpenid()
    {
        $wxstate = input('get.wxstate');
        $wxstate = Db::table('do_notice')
            ->where('wxstate', $wxstate)
            ->find();

        if ($wxstate === null) {
            $res['code'] = 1;
        } else {
            $res['code'] = 0;
            $res['data'] = $wxstate;
        }
        return json($res);
    }
}
