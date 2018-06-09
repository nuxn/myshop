<?php
return array (
  'app' => 'Admin',
  'model' => 'Order',
  'action' => 'default',
  'data' => '',
  'type' => '1',
  'status' => '1',
  'name' => '订单管理',
  'icon' => 'credit-card',
  'remark' => '',
  'listorder' => '99',
  'children' => 
  array (
    array (
      'app' => 'Admin',
      'model' => 'Order',
      'action' => 'lists',
      'data' => '',
      'type' => '1',
      'status' => '1',
      'name' => '订单列表',
      'icon' => '',
      'remark' => '',
      'listorder' => '0',
    ),
  ),
);