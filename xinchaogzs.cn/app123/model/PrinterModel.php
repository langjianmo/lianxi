<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * @mixin think\Model
 */
class PrinterModel extends Model
{
    protected $table = "do_printer";
    protected $json = ['business_hours', 'data', 'config'];
    protected $jsonAssoc = true;
}
