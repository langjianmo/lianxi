<?php
declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * @mixin think\Model
 */
class SystemModel extends Model
{
    protected $table = "do_system";
    protected $json = ['data'];
    protected $jsonAssoc = true;
}
