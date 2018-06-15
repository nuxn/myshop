<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/18
 * Time: 17:10
 */

namespace Api\Controller;

use Common\Controller\ApibaseController;

/**消息通知控制器
 * Class MessageController
 * @package Api\Controller
 */
class MessageController extends ApibaseController
{
    protected $msg_model, $msg_red_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->checkLogin();
        $this->msg_model = M("message");
        $this->msg_red_model = M("message_read");
    }

    /**
     * 消息列表
     */
    public function index()
    {
        $per_page = 20;//每页数量
        $page = I("get.p,0");//页码,第几页
        $del_arr = $this->msg_red_model->where(array("uid" => $this->userId, "status" => 1))->field("mid")->select();
        $del_arrs = array();
        foreach ($del_arr as &$v) {
            $del_arrs[] = $v['mid'];
        }

        $del_str = implode(",", $del_arrs);
        $condition['id'] = array('not IN', ($del_str));
        $condition['uid'] = array('IN', (array(-$this->userInfo['role_id'], $this->userId, '0')));
        $condition['m.status'] = '0';
        $this->_lists($condition, $page, $per_page);
    }

    /**
     * 系统信息列表处理方法,根据不同条件显示不同的列表
     * @param array $where 查询条件
     */
    private function _lists($where = array(), $page, $per_page)
    {

        $this->msg_model
            ->alias("m")
            ->where($where);

        $count = $this->msg_model->count();//总条数

        $total = ceil($count / $per_page);//总页数

        $this->msg_model
            ->alias("m")
            ->where($where)
            ->limit($page * $per_page, $per_page)
            ->order("m.id DESC");
        $data_lists = $this->msg_model->Field('id,title,create_time,uid')->select();

        foreach ($data_lists as $k => $v) {
            $res = $this->check_status($this->userId, $v['id']);
            if (!$res) {
                $data_lists[$k]['is_read'] = 0;
            } else {
                if ($res['status'] == '1') unset($data_lists[$k]);
                else  $data_lists[$k]['is_read'] = 1;
            }

        }
        $data_lists = array_values($data_lists);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("total" => $total, "data" => $data_lists)));

    }


    /**
     * 消息详情,查看后标记为已读
     */
    public function detail()
    {
        $id = I("id", 0);
        if (!$id) $this->ajaxReturn(array("code" => "error", "msg" => L('ID_EMPTY')));
        $result = $this->get_info($id);
        $arr = array("uid" => $this->userId, "mid" => $id);
        if (!$this->msg_red_model->where($arr)->getField('mid')) M("message_read")->add($arr);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("data" => array($result))));
    }

    /**获取详情
     * @param $id
     * @return mixed
     */
    protected function get_info($id)
    {
        $info = $this->msg_model->where(array("id" => $id))->find();
        $host = $_SERVER['HTTP_HOST'];
        $info['content'] = preg_replace('#src="/#is', 'src="http://' . $host . '/', $info['content']);
        return $info;
    }

    /**
     * 删除信息
     */
    public function del()
    {
        $id = I("id", 0);
        if (!$id) $this->ajaxReturn(array("code" => "error", "msg" => L('ID_EMPTY')));
        $this->msg_red_model->where(array("uid" => $this->userId, "mid" => $id))->save(array('status' => 1));
        $this->ajaxReturn(array("code" => "success", "msg" => '成功'));
    }

    /**检查消息是否已读
     * @param $uid
     * @param $mid
     * @return mixed
     */
    protected function check_status($uid, $mid)
    {
        $res = $this->msg_red_model->where(array("uid" => $uid, "mid" => $mid))->field('status')->find();
        return $res;
    }

    /**
     * 是否有未读消息
     */
    public function new_message()
    {
        $sql = "select id FROM " . C('DB_PREFIX') . "message m LEFT JOIN  (select mid as i from " . C('DB_PREFIX') . "message_read  where uid=" . $this->userId . " limit 100) as t1  ON m.id=t1.i where t1.i IS NULL  and `uid` IN (-" . $this->userInfo['role_id'] . "," . $this->userId . ",'0') AND m.status = '0' ORDER BY m.id desc  limit 100";
        $res = M()->query($sql);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("is_read" => intval(count($res)))));
    }
}