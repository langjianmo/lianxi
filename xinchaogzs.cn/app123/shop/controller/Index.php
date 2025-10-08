<?php
declare (strict_types=1);

namespace app\shop\controller;

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
            "cash_out" => $this->cashOutLog(),
            'page' => Db::table('do_printer')->fieldRaw('sum(page_a3) a3,sum(page_a4) a4,sum(page_photo) photo')->where("shop_id", $this->shop["id"])->find()
        ];

        return view("", $vars);
    }

    /**
     * 总收入
     */
    private function incomes($type = false)
    {
        $where = [
            ["shop_id", "=", $this->shop["id"]],
            ["status", "=", 1],
            ["out_refund_status", "in", "null,0,2"]
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
            ->sum("commission");

        return $data;
    }

    /**
     * 账户余额
     */
    private function commission($type = false)
    {
        $incomes = $this->incomes($type);
        $cash_out_total = Db::table("do_commission_cash_out")
            ->where("obj_id", $this->shop["id"])
            ->where('obj_type', 0)
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
                "shop_id",
                "=",
                $this->shop["id"]
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
                    ->sum("commission");
                break;
            case "week":
                $data = Db::table("do_order")
                    ->where($where)
                    ->whereWeek("inserttime")
                    ->sum("commission");
                break;
            case "month":
                $data = Db::table("do_order")
                    ->where($where)
                    ->whereMonth("inserttime")
                    ->sum("commission");
                break;
            case "refund_week":
                $data = Db::table("do_order")
                    ->where($where)
                    ->whereWeek("inserttime")
                    ->sum("commission");
                break;
            case "refund_month":
                $data = Db::table("do_order")
                    ->where($where)
                    ->whereMonth("inserttime")
                    ->sum("commission");
                break;
            case "all":
                $data = Db::table("do_order")
                    ->field("sum(commission) commission,FROM_UNIXTIME(inserttime,'%Y-%m-%d') inserttime")
                    ->where($where)
                    ->whereYear('inserttime')
                    ->group("FROM_UNIXTIME(inserttime,'%Y-%m-%d')")
                    ->select();

                if (!empty($data)) {
                    foreach ($data as $k => $v) {
                        $v["commission"] = bcdiv((string)$v["commission"], "100", 2);
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
            "obj_id" => $this->shop["id"],
            'obj_type' => 0
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

        $commission = $this->commission(true);
        //提现至少1元钱起
        if ($commission >= 30) {
            /*$payment = wechat(false);
			$params  = [
				'partner_trade_no' => $this->makeOutTradeNo("CA"),
				'openid'           => $this->user["openid"],
				'check_name'       => 'NO_CHECK',
				're_user_name'     => $this->shop["name"],
				'amount'           => $commission,
				'desc'             => '收益提现',
			];
			$res     = $payment->transfer->toBalance($params);
			Log::write($res);
			if ($res["result_code"] == "SUCCESS") {
*/
            $data = [
                "price" => $commission,
                "inserttime" => time(),
                "status" => 0,
                "obj_type" => 0,
                "obj_id" => $this->shop["id"]
            ];
            $this->insertCashOutLog($data);

            $return = [
                "code" => 0,
                "msg" => "申请成功"
            ];
            /*} else {
                $return = [
                    "code" => 1,
                    "msg"  => $res["err_code_des"]
                ];
            }*/
        } else {
            $return = [
                "code" => 1,
                "msg" => "24小时前收益余额不足0.3元,无法提现"
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
