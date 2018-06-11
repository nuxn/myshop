<?php

namespace Pay\Model;

use Common\Model\CommonModel;

class PayModel extends CommonModel
{
    protected $parameters = array();

    //自动验证
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('customer_id', 'require', '必须要有用户对应的ID！', 1, 'regex', 3),
    );

    protected function _before_write(&$data)
    {
        parent::_before_write($data);
    }

    protected function setParameters($key, $val)
    {
        $this->parameters[$key] = $val;
    }

    protected function add_pay()
    {
        $this->parameters['add_time'] = time();
        $this->parameters['bill_date'] = date('Ymd');
        $this->checkParameters();
    }

    protected function checkParameters()
    {

    }

}