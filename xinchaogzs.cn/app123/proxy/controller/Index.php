<?php
declare (strict_types=1);

namespace app\proxy\controller;

use EasyWeChat\Factory;
use think\facade\Db;
use think\facade\Log;

class Index extends Common
{

    public function initialize()
    {
        parent::initialize();
    }

    public function index()
    {

        $vars = [
            "today" => $this->statistics("today"),
            "week" => $this->statistics("week"),
            "month" => $this->statistics("month"),
            "refund_week" => $this->statistics("refund_week"),
            "refund_month" => $this->statistics("refund_month"),
            "commission" => $this->commission(),
            "all" => $this->statistics("all"),
            "cash_out" => $this->cashOutLog()
        ];
        //print_r($vars);
        return view("", $vars);
    }

    /**
     * 总收入
     */
    private function incomes($type)
    {
        $where = [
            [
                "proxy_id",
                "=",
                $this->proxy["id"]
            ],
            [
                "status",
                "=",
                1
            ],
            [
                "out_refund_status",
                "in",
                "null,0,2"
            ]
        ];
        if ($type == true) {
            $where [] = [
                'inserttime',
                '<',
                time() - 86400
            ];
        }
        $data = Db::table("do_order")
            ->where($where)
            ->sum("proxy_commission");

        return $data;
    }

    /**
     * 账户余额
     */
    private function commission($type = false)
    {
        $incomes = $this->incomes($type);
        $cash_out_total = Db::table("do_commission_cash_out")
            ->where("obj_id", $this->proxy["id"])
            ->where('obj_type', 1)
            ->where('status', 'in', '0,1')
            ->sum("price");

        return bcdiv((string)($incomes - $cash_out_total), "1", 0);
    }

    /**
     * 时间范围内收益统计
     * @param string $date
     * @return float
     */
    private function statistics($date = "today")
    {

        $where = [
            [
                "proxy_id",
                "=",
                $this->proxy["id"]
            ],
            [
                "status",
                "=",
                1
            ]
        ];
        if ($date == "refund_month" || $date == "refund_week") {
            $where[] =
                [
                    "out_refund_status",
                    "in",
                    "1,3,4,5"
                ];
        } else {
            $where[] =
                [
                    "out_refund_status",
                    "in",
                    "null,0,2"
                ];

        }
        switch ($date) {
            case "today":
                $data = Db::table("do_order")
                    ->where($where)
                    ->whereDay("inserttime")
                    ->sum("proxy_commission");
                break;
            case "week":
                $data = Db::table("do_order")
                    ->where($where)
                    ->whereWeek("inserttime")
                    ->sum("proxy_commission");
                break;
            case "month":
                $data = Db::table("do_order")
                    ->where($where)
                    ->whereMonth("inserttime")
                    ->sum("proxy_commission");
                break;
            case "refund_week":
                $data = Db::table("do_order")
                    ->where($where)
                    ->whereWeek("inserttime")
                    ->sum("proxy_commission");
                break;
            case "refund_month":
                $data = Db::table("do_order")
                    ->where($where)
                    ->whereMonth("inserttime")
                    ->sum("proxy_commission");
                break;
            case "all":
                $data = Db::table("do_order")
                    ->field("sum(proxy_commission) proxy_commission,FROM_UNIXTIME(inserttime,'%Y-%m-%d') inserttime")
                    ->where($where)
                    ->whereYear('inserttime')
                    ->group("FROM_UNIXTIME(inserttime,'%Y-%m-%d')")
                    ->select();

                if (!empty($data)) {
                    foreach ($data as $k => $v) {
                        $v["proxy_commission"] = bcdiv((string)$v["proxy_commission"], "100", 2);
                        $data[$k] = $v;
                    }
                }
                $data = json_encode($data);
                break;
            default:
                $data = [];
        }
        return $data;
    }

    private function cashOutLog()
    {
        $where = [
            "obj_id" => $this->proxy["id"],
            'obj_type' => 1
        ];
        $data = Db::table("do_commission_cash_out")
            ->where($where)
            ->order("id desc")
            ->select();

        return $data;
    }

    /**
     * 提现
     */
    public function cashOut()
    {

        $commission = $this->commission();
        //提现至少1元钱起

        if ($commission >= 30) {

            $payment = wechat(false);
            $params = [
                'partner_trade_no' => $this->makeOutTradeNo("CA"),
                'openid' => $this->user["openid"],
                'check_name' => 'NO_CHECK',
                're_user_name' => $this->proxy["name"],
                'amount' => $commission,
                'desc' => '收益提现',
            ];
            //array (
            //  'return_code' => 'SUCCESS',
            //  'return_msg' => NULL,
            //  'mch_appid' => 'wx2069e516b6eab071',
            //  'mchid' => '1601230759',
            //  'nonce_str' => '5f2e38233ebdd',
            //  'result_code' => 'SUCCESS',
            //  'partner_trade_no' => 'CA202008081329072500072554',
            //  'payment_no' => '10101223653702008085026180867735',
            //  'payment_time' => '2020-08-08 13:29:07',
            //)
            $res = $payment->transfer->toBalance($params);
            Log::write($res);
            if ($res["result_code"] == "SUCCESS") {

                $data = [
                    "price" => $commission,
                    "inserttime" => time(),
                    "status" => 1,
                    "obj_type" => 1,
                    "obj_id" => $this->proxy["id"]
                ];
                $this->insertCashOutLog($data);

                $return = [
                    "code" => 0,
                    "data" => $res,
                    "msg" => "提现成功"
                ];
            } else {
                $return = [
                    "code" => 1,
                    "msg" => $res["err_code_des"]
                ];
            }
        } else {
            $return = [
                "code" => 1,
                "msg" => "余额不足0.3元,无法提现"
            ];
        }

        return json($return);

    }

    /**
     * 插入提现记录
     * @param $data
     * @return int|string
     */
    private function insertCashOutLog($data)
    {
        return Db::table("do_commission_cash_out")
            ->insert($data);
    }

    /**
     * @param string $order_type
     *  EP:电子照订单
     *  PR:printer 打印订单
     *  CA:cash out 提现订单
     * @return string
     * @throws Exception
     */
    private function makeOutTradeNo($order_type = ""): string
    {
        return strtoupper($order_type) . date("YmdHis") . str_pad((string)$this->user["id"], 5, "0") . random_int(
                11111, 99999);
    }
}
