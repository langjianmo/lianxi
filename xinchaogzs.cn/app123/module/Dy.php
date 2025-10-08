<?php

namespace app\module;

use app\common\controller\LianKePrinter;
use app\model\CartModel;
use think\facade\Db;
use think\facade\Log;
use think\queue\Job;


class Dy
{
    public function fire(Job $job, $id)
    {
        $where[] = ['id', '=', $id];
        $where[] = ['print_status', 'in', '1,3,4,5,6'];
        $file_list = Db::table('do_cart')
            ->where($where)
            ->select()
            ->toArray();

        if (!empty($file_list[0]["print_task_id"])) {
            return $file_list[0]['print_task_id'];
        }

        $data = CartModel::with(['order' => function ($query) {
            $query->with('printer');
        }])->findOrEmpty($id);

        if (!$data->isEmpty()) {
            $data = $data->toArray();
            $printer = $data['order']['printer'];

            $lianKe = new LianKePrinter($printer['device_id'], $printer['device_key']);
            $params = get_dy_params($data);
            $file = $data['file'];
            Log::write("[dy]" . var_export($params, true), "debug");
            $res = $lianKe->addJob($printer['data']['printer_info']['drivce_name'], $file, $params, $id);
            Db::table("do_cart")
                ->where('id', $id)
                ->update(['print_task_id' => $res['data']['task_id'], 'print_status' => 3]);
            return $res['data']['task_id'];
        }
        $job->delete();
        return "";
    }

    public function failed($data)
    {
        // ...任务达到最大重试次数后，失败了
    }
}