<?php

namespace app\pad\controller;

use app\BaseController;
use think\facade\Db;

class index extends BaseController
{
    public function info(){
        if (request()->isPost()) {
            $post = input('post.');

            $print = Db::table("do_printer")
                ->fieldRaw('id,shop_id,name,page_a3,page_a4,page_photo')
                ->where('id',$post['print_id'])
                ->findOrEmpty();
            if (!empty($print)) {
                $print['phone'] = Db::table('do_shop')->where('id',$print['shop_id'])->value('phone');
                $shop_print = Db::table('do_printer')
                    ->fieldRaw('id,name,page_a4,page_photo')
                    ->where('shop_id',$print['shop_id'])
                    ->select()->toArray();
                $order = Db::table('do_order')
                    ->alias('a')
                    ->join('do_cart b','a.id = b.order_id')
                    ->where('a.printer_id',$post['print_id'])
                    ->whereNotIn('b.print_status' ,[0,2,6,8])
                    ->select()->toArray();
                $ad = Db::table('do_banner')
                    ->where('shop_id',$print['shop_id'])
                    ->select()->toArray();
                $return = [
                    'code'=>0,
                    'data'=>[
                        'print' => $print,
                        'qr_code' => getMiniQrcode('printer_id:'.$print['shop_id'],'pages/index/index'),
                        'shop_print' => $shop_print,
                        'order' => $order,
                        'ad' => $ad
                    ]
                ];
            } else {
                $return = [
                    'code'=>1,
                    'msg'=>'打印机不存在'
                ];
            }


            return json($return);
        }
    }
}