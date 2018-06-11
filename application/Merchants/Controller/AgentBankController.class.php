<?php
/**
 * 后台首页
 */
namespace Merchants\Controller;

use Common\Controller\AdminbaseController;
use Think\Page;

class AgentBankController extends AdminbaseController
{
    protected $agentbank;
    public function _initialize()
    {
        empty($_GET['upw']) ? "" : session("__SP_UPW__", $_GET['upw']);//设置后台登录加密码
        parent::_initialize();
        $this->agentbank = M("merchants_agentbank");
        $this->initMenu();
    }

    public function index()
    {
        $agentbanks = $this->agentbank->alias('b')
            ->join("left join __MERCHANTS_AGENT__ a on a.id=b.agent_id");
//          ->where($map);
//        echo $this->pay->getLastSql();exit;
        $count = $agentbanks->count();
        /*
         * 查询sql语句
         * echo $Pays->where($map)->_sql();
         * */
        $page = $this->page($count, 20);
        $this->assign("page", $page->show('Admin'));

//        join方法将数组进行变换了，得重新定义join
        $bank_selct = $this->agentbank->alias('b')
            ->join("left join __MERCHANTS_AGENT__ a on a.id=b.agent_id")
            ->join("left join __MERCHANTS_USERS__ u on u.id = a.uid")
            ->field("b.id,b.status,b.create_time,a.agent_name,u.user_phone")
            ->limit($page->firstRow, $page->listRows)
            ->order("b.create_time desc")
            ->select();
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("agentbank", $bank_selct);
        $this->display();
    }

    public function add()
    {
        if(IS_POST){
            $data = I("");
            if($data['agent_id'] == '' || $data['wx_mchid'] =='' ||$data['wx_key']=='')  $this->error("信息未补全");
            (!$this->agentbank->where(array('agent_id'=>$data['agent_id']))->find()) || $this->error("该代理已进件");
            $map = array(
                'create_time' =>time(),
                'status' =>1,
            );
            $this->agentbank->add(array_merge($data,$map));
            $this->success("进件信息添加成功",U('index'));
        }else{
            $this->display();
        }
    }

    public function edit()
    {
        if(IS_POST){
            $data = I("");
            if($data['agent_id'] == '' || $data['wx_mchid'] =='' ||$data['wx_key']=='')  $this->error("信息未补全");
            $map = array(
                'update_time' =>time(),
                'status' =>1,
            );
            unset($data['id']);
            $this->agentbank->where(array('id'=>I("id")))->save(array_merge($data,$map));
            $this->success("进件信息编辑成功",U('index'));
        }else{
            $agentbank=$this->agentbank->where(array('id'=>I("id")))->find();
            $this->assign('a',$agentbank);
            $this->display();
        }
    }

    public function detail()
    {
        $agentbank=$this->agentbank->where(array('id'=>I("id")))->find();
        $this->assign('a',$agentbank);
        $this->display();
    }


}

