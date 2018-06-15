<?php
/**
 * Created by PhpStorm.
 * User: joan
 * Date: 2017/2/22
 * Time: 18:06
 */
namespace Message\Controller;

use Common\Controller\AdminbaseController;

/**手机短信
 * Class AdminSmsController
 * @package Message\Controller
 */
class AdminSmsController extends AdminbaseController
{

    protected $user_model;
    protected $sms_model;
    protected $type_arr;
    protected $title_arr;

    function _initialize()
    {
        parent::_initialize();
        $this->user_model = M("merchants_users u", "ypt_");
        $this->sms_model = M("sms", "ypt_");
        $this->title_arr = array("短信管理", "添加", "修改");
        $this->type_arr = array(0 => "全部", 1 => "消息", 2 => "文章");
        $this->assign("title_arr", $this->title_arr);
    }

    /**
     * 短信列表
     */
    function index()
    {
        $this->_lists();
        $this->assign("type_arr", $this->type_arr);
        $this->display();

    }

    /**
     * 发送短信
     */
    function add()
    {
        if (IS_POST) {
            //加载短信类
            Vendor("SMS.CCPRestSmsSDK");
            $config_arr = C('SMS_CONFIG'); // 读取短信配置
            $s = new \REST($config_arr['serverIP'], $config_arr['serverPort'], $config_arr['softVersion']);
            $s->setAccount($config_arr['accountSid'], $config_arr['accountToken']);
            $s->setAppId($config_arr['M_appId']);

            $data = I("post.post", '', 'trim');
            if (empty($data['title'])) {
                $this->error("请选择模板标题");
            }
            if (empty($data['sms_id'])) {
                $this->error("请选择模板ID");
            }
            if (empty($data['role_id'])) {
                $this->error("请选择目标用户");
            }

            if ($data['role_id'] == '-1') {
                if (empty($data['uid'])) $this->error("请输入用户ID或手机号");
            }

            $data['status'] = 1;//1发送成功
            $phone = 0;
            //如果uid不为空，则发送单个用户，否则发送角色组用户
            if ($data['uid'] && $data['role_id'] == '-1') {
                if (isMobile($data['uid'])) {//判断是否是手机号
                    $phone = $data['uid'];
                    $info = $this->user_model->where(array("user_phone" => floatval($data['uid'])))->find();
                    if (!$info) $this->error("该手机号数据库不存在！");
                    $data['uid'] = $info['id'];
                } else {//根据uid查询手机号
                    $where['id'] = intval($data['uid']);
                    $where['user_phone'] = array('neq', '');
                    $info = $this->user_model->where($where)->find();
                    if ($info['user_phone']) $phone = $info['user_phone'];
                    else $this->error("该用户ID手机号不存在！");
                }

                $data['type'] = -1;
                $data['phone'] = $phone;
                $result = $s->sendTemplateSMS($phone, array(), intval($data['sms_id']));//手机号码，内容数组，模板ID

                //入库
                $data['create_time'] = time();
                $data['update_time'] = time();
                if ($result->statusCode != 0) {
                    echo $result->statusMsg;
                } else {
                    if ($this->sms_model->add($data)) {
                        $this->success("添加成功！");
                    } else {
                        $this->error("添加失败！");
                    }

                }

            } else {//群发
                $data['type'] = ($data['role_id'] == '-2') ? 0 : intval($data['role_id']);
                $where['user_phone'] = array('neq', '');
                $data['create_time'] = time();
                $data['update_time'] = time();
                if ($data['type'] == 0) {//全体用户
                    $list = $this->user_model->where($where)->field("id,user_phone")->select();
                    $phones_arr = array();//存储手机号
                    foreach ($list as $v) {
                        $phones_arr[$v['id']] = $v['user_phone'];
                    }
                    $phones_arr = array_unique($phones_arr);//去重
                    foreach ($phones_arr as $k => $tel) {
                        $data['phone'] = $tel;
                        $data['uid'] = $k;
                        $result = $s->sendTemplateSMS($tel, array(), $data['sms_id']);
                        if ($result->statusCode != 0) {
                            echo $result->statusMsg;
                        } else {
                            $this->sms_model->add($data);
                        }
                    }

                } else {//角色用户
                    $where['r.role_id'] = $data['type'];
                    $list = $this->user_model->join("JOIN __MERCHANTS_ROLE_USERS__ r ON u.id = r.uid")->where($where)->field("u.id,u.user_phone")->select();
                    $phones_arr = array();//存储手机号
                    foreach ($list as $v) {
                        $phones_arr[$v['id']] = $v['user_phone'];
                    }
                    $phones_arr = array_unique($phones_arr);//去重
                    foreach ($phones_arr as $k => $tel) {
                        $data['phone'] = $tel;
                        $data['uid'] = $k;
                        $result = $s->sendTemplateSMS($tel, array(), $data['sms_id']);
                        if ($result->statusCode != 0) {
                            echo $result->statusMsg;
                        } else {
                            $this->sms_model->add($data);
                        }
                    }

                }
                $this->success('发送完成!');

            }


        } else {
            $role_arr = M("merchants_role", "ypt_")->select();
            $this->assign("role_arr", $role_arr);
            $this->display();
        }
    }

    /**
     * 短信列表处理方法,根据不同条件显示不同的列表
     * @param array $where 查询条件
     */
    private function _lists($where = array())
    {
        $type = I('request.type', 0, 'intval');

        if (!empty($type)) {
            $where['m.type'] = $type;
            $this->assign("term", $type);
        }

        $start_time = strtotime(I('request.start_time'));
        if (!empty($start_time)) {
            $where['m.create_time'] = array(
                array('EGT', $start_time)
            );
        }

        $end_time = strtotime(I('request.end_time'));
        if (!empty($end_time)) {
            if (empty($where['m.create_time'])) {
                $where['m.create_time'] = array();
            }
            array_push($where['m.create_time'], array('ELT', $end_time));
        }

        $keyword = I('request.title');
        if (!empty($keyword)) {
            $where['title'] = array('like', "%$keyword%");
        }

        $this->sms_model
            ->alias("m")
            ->where($where);

        $count = $this->sms_model->count();

        $page = $this->page($count, 20);

        $this->sms_model
            ->alias("m")
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->order("m.id DESC");
        $this->sms_model->field('m.*,u.user_phone user_login');
        $this->sms_model->join("LEFT JOIN __MERCHANTS_USERS__ u ON m.uid = u.id");
        $data_lists = $this->sms_model->select();
        $role_arr = M("merchants_role", "ypt_")->field("id,role_name")->select();
        $this->assign("role_arr", $role_arr);
        $this->assign("page", $page->show('Admin'));
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("data_lists", $data_lists);
    }
}
