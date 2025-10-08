<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * @mixin think\Model
 */
class OrderModel extends Model
{
    protected $table = "do_order";

    public function printer()
    {
        return $this->hasOne(PrinterModel::class, 'id', 'printer_id');
    }
}
