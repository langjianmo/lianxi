<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * @mixin think\Model
 */
class CartModel extends Model
{
    protected $table = "do_cart";

    public function order()
    {
        return $this->hasOne(OrderModel::class, 'id', 'order_id');
    }
}
