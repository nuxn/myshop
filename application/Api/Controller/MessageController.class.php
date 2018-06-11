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
    protected $msg_model, $msg_red_model,$msg_dc_book;

    public function _initialize()
    {
        parent::_initialize();
        // $this->checkLogin();
        $this->msg_model = M("message");
        $this->msg_red_model = M("message_read");
        $this->msg_dc_model = M("message_dc");
        $this->msg_red_dc_model = M("message_read_dc");
        $this->msg_dc_book = M("dc_book");
    }

    /**
     * 消息列表
     */
    public function index()
    {
        $per_page = 20;//每页数量
        $page = I("page","0");//页码,第几页
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

    /**
     * 1.3.5版本系统消息列表
     */
    public function message_list()
    {
        // $this->userId =26;
        $per_page = 10;//每页数量
        $page = I("page","0");//页码,第几页
        // dump($this->userId);die;
        $del_arr = $this->msg_red_model->where(array("uid" => $this->userId, "status" => 1))->field("mid")->select();
        $del_arrs = array();
        foreach ($del_arr as &$v) {
            $del_arrs[] = $v['mid'];
        }

        $del_str = implode(",", $del_arrs);
        $where['id'] = array('not IN', ($del_str));
        $where['uid'] = array('IN', (array(-$this->userInfo['role_id'], $this->userId, '0')));
        $where['m.status'] = '0';
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
        $data_lists = $this->msg_model->Field('id,title,create_time,uid,content,description')->select();

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
        // $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("total" => $total, "data" => $data_lists)));
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("total" => $total, "data" => $data_lists)));
        
    }

    /**
     * 商家消息列表
     */
    public function message_list_dc1()
    {
        // $this->userId =26;
        $uid = $this->userId;
        $per_page = 10;//每页数量
        $page = I("page","0");//页码,第几页 
        $del_arr = $this->msg_red_dc_model->where(array("uid" => $uid, "status" => 1))->field("mid")->select();
        // dump($del_arr);die;
        $del_arrs = array();
        foreach ($del_arr as &$v) {
            $del_arrs[] = $v['mid'];
        }
        $del_str = implode(",", $del_arrs);
        // dump($del_str);die;
        $where['id'] = array('not IN', $del_str);
        $mid =  $this->_get_mch_id($uid); 
        $no_dc = M("dc_no")->where(array("mid" => $mid))->select();
        $arr = array();
        foreach ($no_dc as &$v) {
            $arr[] = $v['id'];
        }
        $no_id = implode(",", $arr);
        // dump($no_id);die;
        $where['no_id'] = array('IN', $no_id);
        $where['m.status'] = '0';
        $this->msg_dc_model
            ->alias("m")
            ->where($where);

        $count = $this->msg_dc_model->where($where)->count();//总条数
        // dump($count);die;
        $total = ceil($count / $per_page);//总页数

        $this->msg_dc_model
            ->alias("m")
            ->where($where)
            ->limit($page * $per_page, $per_page)
            ->order("m.create_time DESC");
        $data_lists = $this->msg_dc_model
        // ->join('join ypt_dc_no a on a.id=m.no_id')
        ->Field('m.id,m.no_id,m.update_time,m.uid,m.serve_mode')->select();
        foreach ($data_lists as $key => $value) {
            $no = M('dc_no')->where(array('id'=>$data_lists[$key]['no_id'],'mid'=>$mid))->field('no')->find();
            
            $data_lists[$key]['no'] =$no['no'];
            // dump($data_lists[$key]['no']);
        }
        // die;
        // dump($data_lists);die;
        // echo $this->msg_dc_model->getLastSql();die;
        foreach ($data_lists as $k => $v) {
            $res = $this->msg_red_dc_model->where(array("uid" => $uid, "mid" => $v['id']))->field('status')->find();
            if (!$res) {
                $data_lists[$k]['is_read'] = 0;
            } else {
                if ($res['status'] == '1') unset($data_lists[$k]);
                else  $data_lists[$k]['is_read'] = 1;
            }
        }
        $data_lists = array_values($data_lists);
        if($data_lists){
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("total" => $total, "data" => $data_lists)));
        }else{
            $this->ajaxReturn(array("code" => "success", "msg" => "没有消息","data"=>array("data"=> array())));     
        }
             
    }

    public function message_list_dc()
    {
        // $this->userId = 2442;
        // dump($this->userId);
        $pid = M('merchants_users')->where(array('id'=>$this->userId))->find();
        // echo M('merchants_users')->getLastSql();
        // dump($pid);
        $uid = $pid['pid'];
        $page = I("page","0");
        if ($pid['auth']) {
            $this->message_list_dcs($uid,$page);
        }else{
            $this->message_list_dcs($this->userId,$page);
        }
        
    }
    public function message_list_dcs($uid,$page)
    {
        // $this->userId =26;
        // dump($uid);
        // $uid = $this->userId;
        $per_page = 10;//每页数量
        // $page = I("page","0");//页码,第几页 
        $del_arr = $this->msg_red_dc_model->where(array("uid" => $uid, "status" => 1))->field("mid")->select();
        // dump($del_arr);die;
        $del_arrs = array();
        foreach ($del_arr as &$v) {
            $del_arrs[] = $v['mid'];
        }
        $del_str = implode(",", $del_arrs);
        // dump($del_str);die;
        $where['id'] = array('not IN', $del_str);
        $mid =  $this->_get_mch_id($uid); 
        $no_dc = M("dc_no")->where(array("mid" => $mid))->select();
        $arr = array();
        foreach ($no_dc as &$v) {
            $arr[] = $v['id'];
        }
        $no_id = implode(",", $arr);
        // dump($no_id);die;
        $where['no_id'] = array('IN', $no_id);
        $where['m.status'] = '0';
        $this->msg_dc_model
            ->alias("m")
            ->where($where);

        $count = $this->msg_dc_model->where($where)->count();//总条数
        // dump($count);die;
        $total = ceil($count / $per_page);//总页数

        $this->msg_dc_model
            ->alias("m")
            ->where($where)
            ->limit($page * $per_page, $per_page)
            ->order("m.create_time DESC");
        $data_lists = $this->msg_dc_model
        // ->join('join ypt_dc_no a on a.id=m.no_id')
        ->Field('m.id,m.no_id,m.update_time,m.uid,m.serve_mode')->select();
        foreach ($data_lists as $key => $value) {
            $no = M('dc_no')->where(array('id'=>$data_lists[$key]['no_id'],'mid'=>$mid))->field('no')->find();
            
            $data_lists[$key]['no'] =$no['no'];
            // dump($data_lists[$key]['no']);
        }
        // die;
        // dump($data_lists);die;
        // echo $this->msg_dc_model->getLastSql();die;
        foreach ($data_lists as $k => $v) {
            $res = $this->msg_red_dc_model->where(array("mid" => $v['id']))->field('status')->find();
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
     * 删除商家消息
     */
    public function del_dc()
    {
        $id = I("id");
        if (!$id) $this->ajaxReturn(array("code" => "error", "msg" => L('ID_EMPTY')));
        $this->msg_red_dc_model->where(array("uid" => $this->userId, "mid" => $id))->save(array('status' => 1));
        $this->msg_dc_model->where(array("id" => $id))->save(array('status' => 1));
        $this->ajaxReturn(array("code" => "success", "msg" => '成功'));
    }

    /**
     * 处理商家消息
     */
    public function deal_dc()
    {
        // $this->userId = 26;
        $id = I("id");
        if (!$id) $this->ajaxReturn(array("code" => "error", "msg" => L('ID_EMPTY')));
        $arr = array("mid" => $id,'status'=>0);
        // dump($arr);
        if (!$this->msg_red_dc_model->where($arr)->getField('id')){
            $arr['uid'] = $this->userId;
            $arr['create_time'] = time();
            $arr['update_time'] = time();
            // dump($arr);
            $res = M("message_read_dc")->add($arr);    
        }
        // dump($res);
        if ($res) {
            $this->ajaxReturn(array("code" => "success", "msg" => "处理完成"));
         } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "消息已处理"));
         }
        
    }


    /**
     * 系统消息全部已读
     */
    public function message_read()
    {
        $del_arr = $this->msg_red_model->where(array("uid" => $this->userId, "status" => 1))->field("mid")->select();
        $del_arrs = array();
        foreach ($del_arr as &$v) {
            $del_arrs[] = $v['mid'];
        }

        $del_str = implode(",", $del_arrs);
        $where['id'] = array('not IN', ($del_str));
        $where['uid'] = array('IN', (array(-$this->userInfo['role_id'], $this->userId, '0')));
        $where['m.status'] = '0';
        $this->msg_model
            ->alias("m")
            ->where($where);
            // ->limit($page * $per_page, $per_page)
            // ->order("m.id DESC");
        $data_lists = $this->msg_model->Field('id,title,create_time,uid,content')->select();

        foreach ($data_lists as $k => $v) {
            $res = $this->check_status($this->userId, $v['id']);
            if (!$res) {
                //未读
                $arr = array("uid" => $this->userId, "mid" => $data_lists[$k]['id']);
                if (!$this->msg_red_model->where($arr)->getField('mid')){
                    $result = M("message_read")->add($arr);
                    if(!$result){
                        $this->ajaxReturn(array("code" => "error", "msg" => "失败"));   
                    }
                }
                
            }
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("data" => array($result))));       
    }

    /**
     * 商家消息全部已读
     */
    public function message_dc_read()
    {
        // $this->userId =26;
        $uid = $this->userId;
        $del_arr = $this->msg_red_dc_model->where(array("uid" => $this->userId, "status" => 1))->field("mid")->select();
        // dump($del_arr);die;
        $del_arrs = array();
        foreach ($del_arr as &$v) {
            $del_arrs[] = $v['mid'];
        }
        $del_str = implode(",", $del_arrs);
        // dump($del_str);die;
        $where['id'] = array('not IN', $del_str);
        $mid =  $this->_get_mch_id($uid); 
        $no_dc = M("dc_no")->where(array("mid" => $mid))->select();
        $arr = array();
        foreach ($no_dc as &$v) {
            $arr[] = $v['id'];
        }
        $no_id = implode(",", $arr);
        // dump($no_id);die;
        $where['no_id'] = array('IN', $no_id);
        $where['m.status'] = '0';
        $this->msg_dc_model
            ->alias("m")
            ->where($where);
            // ->order("m.create_time DESC");
        $data_lists = $this->msg_dc_model
        ->Field('m.id,m.no_id,m.update_time,m.uid,m.serve_mode')->select();
        
        // echo $this->msg_dc_model->getLastSql();die;
        foreach ($data_lists as $k => $v) {
            $res = $this->msg_red_dc_model->where(array("uid" => $this->userId, "mid" => $v['id']))->find();
            if (!$res) {
                $arr = array("uid" => $this->userId, "mid" => $data_lists[$k]['id']);
                $arr['create_time'] = time();
                $arr['update_time'] = time();
                $result = M("message_read_dc")->add($arr);
                if(!$result){
                    $this->ajaxReturn(array("code" => "error", "msg" => "处理失败"));  
                }
            }
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "处理完成"));
    }

    /**
     * 获取商家ID
     * @Param uid 商家uid
     */
    public function _get_mch_id($uid)
    {
        $id = M("merchants")->where(array('uid'=>$uid))->getField('id');
        return $id;
    }


    /**
     * 查看预约记录
     */
    public function book_lists()
    {
        $page = I("page","0");
        $per_page = 5;
        // $this->userId = 21;
        $where['uid'] = $this->userId;
        $where['status'] = array('not IN', '3');
        $this->msg_dc_book->where($where);
        // $count = $this->msg_dc_book->count();//总条数

        //  $total = ceil($count / $per_page);//总页数
        $data = $this->msg_dc_book
            ->limit($page * $per_page, $per_page)
            ->order("create_time DESC")
            ->select();
        // dump($this->msg_dc_book->getLastSql());
        if ($data) {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功","data"=> $data));
        }else{
            $this->ajaxReturn(array("code" => "success", "msg" => "没有数据","data"=>array()));
        }
    }

    /**
     * 处理预约
     */
    public function deal_dc_yu()
    {
        $id = I("id");
        $status = I("status");
        if (!$id) $this->ajaxReturn(array("code" => "error", "msg" => L('ID_EMPTY')));
        if (!$status) $this->ajaxReturn(array("code" => "error", "msg" => "缺少status"));
        $data = $this->msg_dc_book->where(array("id" => $id))->find();
        if ($data) {
            if($data['status']==3){
               $this->ajaxReturn(array("code" => "error", "msg" => "消息已经删除"));
            }
            $arr['update_time'] = time();
            $arr['status'] = $status;
            if($this->msg_dc_book->where(array("id" => $id))->save($arr)){
                $this->ajaxReturn(array("code" => "success", "msg" => "处理完成"));
            }else{
                $this->ajaxReturn(array("code" => "error", "msg" => "未知错误"));
            }
        }else{
            $this->ajaxReturn(array("code" => "error", "msg" => "未找到消息")); 
        }
        
    }

    /**
     * 预约消息全部已读
     */
    public function yu_read(){
        // $uid = 21;
        $uid= $this->userId;
        $arr['update_time'] = time();
        $arr['status'] = 2;
        if($this->msg_dc_book->where(array("uid" => $uid,'status'=>1))->save($arr)){
            $this->ajaxReturn(array("code" => "success", "msg" => "处理完成"));
        }else{
            $this->ajaxReturn(array("code" => "error", "msg" => "未知错误"));
        }
        
    }

    /**
     * 消息分类列表页是否未读
     */
    public function message_class()
    {
        $message = $this->new_message1();
        $dc = $this->new_message_dc();
        $yu = $this->new_message_yu();
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("sys" => $message,'dc'=>$dc,'yu'=>$yu)));
    }

    /**
     * 系统是否有未读消息
     */
    public function new_message1() 
    {
        // $this->userId = 21;
        $sql = "select id FROM " . C('DB_PREFIX') . "message m LEFT JOIN  (select mid as i from " . C('DB_PREFIX') . "message_read  where uid=" . $this->userId . " limit 100) as t1  ON m.id=t1.i where t1.i IS NULL  and `uid` IN (-" . $this->userInfo['role_id'] . "," . $this->userId . ",'0') AND m.status = '0' ORDER BY m.id desc  limit 100";
        $res = M()->query($sql);
        return intval(count($res));
        // $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("is_read" => intval(count($res)))));
    }

    /**
     * 呼叫服务是否有未读消息
     */
    public function new_message_dc() 
    {
        // $this->userId = 21;
        $mid =  $this->_get_mch_id($this->userId); 
        $no_dc = M("dc_no")->where(array("mid" => $mid))->select();
        if ($no_dc) {
            $arr = array(); 
            foreach ($no_dc as &$v) {
                $arr[] = $v['id'];
            }
            $no_id = implode(",", $arr);
            $sql = "select id FROM " . C('DB_PREFIX') . "message_dc m LEFT JOIN  (select mid as i from " . C('DB_PREFIX') . "message_read_dc  where uid=" . $this->userId . " and status = '0' ) as t1  ON m.id=t1.i where t1.i IS NULL  and no_id IN (".$no_id.") AND m.status = '0' ORDER BY m.id desc";
            $res = M()->query($sql);
            return intval(count($res));
        }else{
            return 0;
        }
        
        // $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("is_read" => intval(count($res)))));
    }

    /**
     * 预约是否有未读消息
     */
    public function new_message_yu() 
    {
        // $this->userId = 21;
        $count = M('dc_book')->where(array('uid'=>$this->userId,'status'=>'1'))->count();
        return intval($count);
        // $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("is_read" => intval($count))));
    }

}