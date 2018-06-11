<?php
namespace Pay\Model;

use Think\Model\RelationModel;

class ShopModel extends RelationModel {
	
	//自动验证
	protected $_validate = array(
		//array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
		array('customer_id', 'require', '必须要有用户对应的ID！', 1, 'regex', 3),
	);

    protected $_link = array(
        'pay'=>array(
            'mapping_type'      => self::HAS_MANY,
            'class_name'        => 'pay',
            'foreign_key'   => 'shop_id',
            'mapping_fields' => 'price,paytime,status,paystyle_id',
            'condition' => 'paystyle_id =2 and paytime >123456'
            // 定义更多的关联属性
            ),
        );


	protected function _before_write(&$data) {
		parent::_before_write($data);
	}
	

}



