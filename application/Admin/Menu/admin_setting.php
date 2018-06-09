<?php
return array (
  'app' => 'Admin',
  'model' => 'Setting',
  'action' => 'default',
  'data' => '',
  'type' => '0',
  'status' => '1',
  'name' => '设置',
  'icon' => 'cogs',
  'remark' => '',
  'listorder' => '99',
  'children' => 
  array (
    array (
      'app' => 'Admin',
      'model' => 'Setting',
      'action' => 'userdefault',
      'data' => '',
      'type' => '0',
      'status' => '1',
      'name' => '个人信息',
      'icon' => '',
      'remark' => '',
      'listorder' => '0',
      'children' => 
      array (
        array (
          'app' => 'Admin',
          'model' => 'User',
          'action' => 'userinfo',
          'data' => '',
          'type' => '1',
          'status' => '1',
          'name' => '修改信息',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
          'children' => 
          array (
            array (
              'app' => 'Admin',
              'model' => 'User',
              'action' => 'userinfo_post',
              'data' => '',
              'type' => '1',
              'status' => '0',
              'name' => '修改信息提交',
              'icon' => '',
              'remark' => '',
              'listorder' => '0',
            ),
          ),
        ),
        array (
          'app' => 'Admin',
          'model' => 'Setting',
          'action' => 'password',
          'data' => '',
          'type' => '1',
          'status' => '1',
          'name' => '修改密码',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
          'children' => 
          array (
            array (
              'app' => 'Admin',
              'model' => 'Setting',
              'action' => 'password_post',
              'data' => '',
              'type' => '1',
              'status' => '0',
              'name' => '提交修改',
              'icon' => '',
              'remark' => '',
              'listorder' => '0',
            ),
          ),
        ),
      ),
    ),
    array (
      'app' => 'Admin',
      'model' => 'Setting',
      'action' => 'site',
      'data' => '',
      'type' => '1',
      'status' => '1',
      'name' => '网站信息',
      'icon' => '',
      'remark' => '',
      'listorder' => '0',
      'children' => 
      array (
        array (
          'app' => 'Admin',
          'model' => 'Setting',
          'action' => 'site_post',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '提交修改',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
        array (
          'app' => 'Admin',
          'model' => 'Route',
          'action' => 'index',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '路由列表',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
        array (
          'app' => 'Admin',
          'model' => 'Route',
          'action' => 'add',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '路由添加',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
          'children' => 
          array (
            array (
              'app' => 'Admin',
              'model' => 'Route',
              'action' => 'add_post',
              'data' => '',
              'type' => '1',
              'status' => '0',
              'name' => '路由添加提交',
              'icon' => '',
              'remark' => '',
              'listorder' => '0',
            ),
          ),
        ),
        array (
          'app' => 'Admin',
          'model' => 'Route',
          'action' => 'edit',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '路由编辑',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
          'children' => 
          array (
            array (
              'app' => 'Admin',
              'model' => 'Route',
              'action' => 'edit_post',
              'data' => '',
              'type' => '1',
              'status' => '0',
              'name' => '路由编辑提交',
              'icon' => '',
              'remark' => '',
              'listorder' => '0',
            ),
          ),
        ),
        array (
          'app' => 'Admin',
          'model' => 'Route',
          'action' => 'delete',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '路由删除',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
        array (
          'app' => 'Admin',
          'model' => 'Route',
          'action' => 'ban',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '路由禁止',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
        array (
          'app' => 'Admin',
          'model' => 'Route',
          'action' => 'open',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '路由启用',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
        array (
          'app' => 'Admin',
          'model' => 'Route',
          'action' => 'listorders',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '路由排序',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
      ),
    ),
    array (
      'app' => 'Admin',
      'model' => 'Mailer',
      'action' => 'default',
      'data' => '',
      'type' => '1',
      'status' => '0',
      'name' => '邮箱配置',
      'icon' => '',
      'remark' => '',
      'listorder' => '0',
      'children' => 
      array (
        array (
          'app' => 'Admin',
          'model' => 'Mailer',
          'action' => 'index',
          'data' => '',
          'type' => '1',
          'status' => '1',
          'name' => 'SMTP配置',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
          'children' => 
          array (
            array (
              'app' => 'Admin',
              'model' => 'Mailer',
              'action' => 'index_post',
              'data' => '',
              'type' => '1',
              'status' => '0',
              'name' => '提交配置',
              'icon' => '',
              'remark' => '',
              'listorder' => '0',
            ),
            array (
              'app' => 'Admin',
              'model' => 'Mailer',
              'action' => 'test',
              'data' => '',
              'type' => '1',
              'status' => '0',
              'name' => '测试邮件',
              'icon' => '',
              'remark' => '',
              'listorder' => '0',
            ),
          ),
        ),
        array (
          'app' => 'Admin',
          'model' => 'Mailer',
          'action' => 'active',
          'data' => '',
          'type' => '1',
          'status' => '1',
          'name' => '注册邮件模板',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
          'children' => 
          array (
            array (
              'app' => 'Admin',
              'model' => 'Mailer',
              'action' => 'active_post',
              'data' => '',
              'type' => '1',
              'status' => '0',
              'name' => '提交模板',
              'icon' => '',
              'remark' => '',
              'listorder' => '0',
            ),
          ),
        ),
      ),
    ),
    array (
      'app' => 'Admin',
      'model' => 'Storage',
      'action' => 'index',
      'data' => '',
      'type' => '1',
      'status' => '0',
      'name' => '文件存储',
      'icon' => '',
      'remark' => '',
      'listorder' => '0',
      'children' => 
      array (
        array (
          'app' => 'Admin',
          'model' => 'Storage',
          'action' => 'setting_post',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '文件存储设置提交',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
      ),
    ),
    array (
      'app' => 'Admin',
      'model' => 'Setting',
      'action' => 'upload',
      'data' => '',
      'type' => '1',
      'status' => '1',
      'name' => '上传设置',
      'icon' => '',
      'remark' => '',
      'listorder' => '0',
      'children' => 
      array (
        array (
          'app' => 'Admin',
          'model' => 'Setting',
          'action' => 'upload_post',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '上传设置提交',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
      ),
    ),
    array (
      'app' => 'Admin',
      'model' => 'Setting',
      'action' => 'clearcache',
      'data' => '',
      'type' => '1',
      'status' => '0',
      'name' => '清除缓存',
      'icon' => '',
      'remark' => '',
      'listorder' => '1',
    ),
  ),
);