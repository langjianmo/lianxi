<?php
declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * @mixin think\Model
 */
class MiniUserModel extends Model
{
    protected $table = "do_mini_user";
}
