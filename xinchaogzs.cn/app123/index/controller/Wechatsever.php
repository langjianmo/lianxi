<?php


declare(strict_types=1);

namespace app\index\controller;

use app\common\controller\WeChatService;
use think\App;
use think\facade\Db;


class Wechatsever
{

    public function index()
    {
        $code = input();
        if (!empty($code['state'])) {
            $data = json_decode(base64_decode($code['state']), true);
            $wx = wechatAccount();
            $oauth = $wx->oauth;
            $user = $oauth->user()->toArray();
            //$openid = $wx->getCodeToOpenid($code['code']);
            $openid = $user['original']['openid'];
            $data['openid'] = $openid;
            $UserInfo = $wx->user->get($openid);
            if ($UserInfo['subscribe'] == 0) {
                return "尚未关注公众号，请关注公众号后重新绑定！";
            } else {
                $notice_data = Db::table('do_notice')
                    ->where('openid', $data['openid'])
                    ->find();
                if ($notice_data === null) {
                    Db::table('do_notice')
                        ->save($data);
                } else {
                    Db::table('do_notice')
                        ->where('openid', $data['openid'])
                        ->update($data);
                }

                return "绑定成功";
            }
        }
        $vars = [

        ];
        return view("", $vars);
    }
}
