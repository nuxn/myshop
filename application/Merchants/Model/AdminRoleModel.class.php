<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/2/24
 * Time: 17:16
 */

namespace Merchants\Model;


use Think\Model;

class AdminRoleModel extends  Model
{
    protected $trueTableName="ypt_merchants_role";
    protected $_validate = array(
        array('role_name','require','角色必须！'), //默认情况下用正则进行验证
        array('role_name','','角色名称已经存在！',0,'unique',1), // 在新增的时候验证name字段是否唯一
    );

}