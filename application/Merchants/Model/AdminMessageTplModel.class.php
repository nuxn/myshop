<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/2/24
 * Time: 17:16
 */

namespace Merchants\Model;


use Think\Model;

class AdminMessageTplModel extends  Model
{
    protected $trueTableName="ypt_message_tpl";
    protected $_validate = array(
        array('tpl_name','require','模板名称必须！'),
        array('tpl_name','','模板名称已经存在！',0,'unique',1), // 在新增的时候验证name字段是否唯一
        array('msg_contents','require','模板内容必须！'),
    );

}