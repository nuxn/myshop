<?php

namespace Message\Controller;

use Common\Controller\AdminbaseController;

/**
 *Author: Joan
 * 系统信息，包含系统推送的短消息、文章
 */
class AdminSystemController extends AdminbaseController
{
    protected $message_model;
    protected $type_arr;
    protected $title_arr;

    function _initialize()
    {
        parent::_initialize();
        $this->message_model = M("message", "ypt_");
        $this->type_arr = array('0' => '全部', '1' => '短消息', '2' => '文章');
    }

    /**
     * 后台系统信息列表
     */
    public function index()
    {
//        Vendor('Cache.MyRedis');
//        $redis = new \MyRedis();
//        if ($redis->set("foo", "洋仆淘") == false) {
//            die($redis->getLastError());
//        }
//        $value = $redis->get("foo");
//        echo $value;
//        var_dump(get_client_ip());
//        $Ip = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
//        $area = $Ip->getlocation('14.154.31.179'); // 获取某个IP地址所在的位置
//        var_dump($area);
        $this->_lists(array("m.status" => array('eq', 0)));
        $this->assign("type_arr", $this->type_arr);
        $this->display();

    }


    /**
     * 添加系统信息
     */
    public function add()
    {
        if (IS_POST) {
            $data = I("post.post");
            if (empty($_POST['type'])) {
                $this->error("请选择一个分类！");
            }
            if (empty($data['role_id'])) {
                $this->error("请选择目标用户");
            }
            if (empty($data['content']) || mb_strlen($data['content']) < 5) {
                $this->error("内容不能少于5个汉字");
            }

            if ($data['role_id'] == '-1') {
                if (empty($data['uid'])) $this->error("请输入用户ID");
            } elseif ($data['role_id'] == '-2') {
                $data['uid'] = 0;
            } else {
                $data['uid'] = -$data['role_id'];
            }

            if (!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])) {
                foreach ($_POST['photos_url'] as $key => $url) {
                    $photourl = sp_asset_relative_url($url);
                    $_POST['smeta']['photo'][] = array("url" => $photourl, "alt" => $_POST['photos_alt'][$key]);
                }
            }

            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['img'] = json_encode($_POST['smeta']);
            $data['content'] = htmlspecialchars_decode($data['content']);
            $data['type'] = I("post.type", "1", "intval");
            $data['sender'] = $_SESSION['name'];
            if ($this->message_model->add($data)) {
                $this->success("添加成功！");
            } else {
                $this->error("添加失败！");
            }

        } else {
            $role_arr = M("merchants_role", "ypt_")->select();
            $this->assign("role_arr", $role_arr);
            $this->display();
        }

    }

    /**
     * 系统信息列表处理方法,根据不同条件显示不同的列表
     * @param array $where 查询条件
     */
    private function _lists($where = array())
    {
        $type = I('request.type', 0, 'intval');
        if (!empty($type)) {
            $where['m.type'] = $type;
            $this->assign("type", $type);
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

        $this->message_model
            ->alias("m")
            ->where($where);

        $count = $this->message_model->count();

        $page = $this->page($count, 20);

        $this->message_model
            ->alias("m")
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->order("m.id DESC");
        $this->message_model->field('m.*,u.user_phone user_login');
        $this->message_model->join("LEFT JOIN __MERCHANTS_USERS__ u ON m.uid = u.id");
        $data_lists = $this->message_model->select();

        foreach ($data_lists as $k => $v) {
            if ($v['img']) {
                $img = json_decode($v['img'], true);
                if ($img['photo'][0]['url']) {

                    if (!preg_match("/^(http:\/\/|https:\/\/).*$/", $img['photo'][0]['url'])) {
                        if ($_SERVER['HTTP_HOST'] == '127.0.0.1') $img['photo'][0]['url'] = '/youngshop/data/upload/' . $img['photo'][0]['url'];
                        else $img['photo'][0]['url'] = '../../data/upload/' . $img['photo'][0]['url'];
                    }
                    $data_lists[$k]['img'] = $img['photo'][0]['url'];
                }
            }
            $v['content'] = strip_tags($v['content']);

            if (mb_strlen($v['content']) > 1000) $data_lists[$k]['content'] = mb_substr($v['content'], 0, 1000);
            if (!$v['user_login']) $data_lists[$k]['user_login'] = $this->_getRole(abs($v['uid']));
        }

        $this->assign("page", $page->show('Admin'));
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("data_lists", $data_lists);
    }

    protected function _getRole($role_id)
    {
        if ($role_id == 0) return '全体用户';
        else  return M("merchants_role")->where(array("id" => $role_id))->getField('role_name');
    }

    // 编辑
    public function edit()
    {
        if (IS_POST) {
            if (!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])) {
                foreach ($_POST['photos_url'] as $key => $url) {
                    $photourl = sp_asset_relative_url($url);
                    $_POST['img']['photo'][] = array("url" => $photourl, "alt" => $_POST['photos_alt'][$key]);
                }
            }
            unset($_POST['post']['post_author']);
            $_POST['post']['update_time'] = time();
            $article = I("post.post");
            $article['img'] = json_encode($_POST['img']);
            $article['content'] = htmlspecialchars_decode($article['content']);
            $result = $this->message_model->save($article);
            if ($result !== false) {
                $this->success("保存成功！");
            } else {
                $this->error("保存失败！");
            }
        } else {
            $id = I("get.id", 0, 'intval');
            $type = I("get.type", 0, 'intval');
            if (!empty($type)) {
                $this->assign("type", $type);
            }
            //unset($this->type_arr[0]);
            $info = $this->message_model->where("id=$id")->find();
            $this->assign("post", $info);
            $this->assign("type_arr", $this->type_arr);
            $this->assign("smeta", json_decode($info['img'], true));
            $this->display();
        }
    }

    //删除
    public function delete()
    {
        if (isset($_GET['id'])) {
            $id = I("get.id", 0, 'intval');
            if ($this->message_model->where(array('id' => $id))->save(array('status' => '-1')) !== false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }

        if (isset($_POST['ids'])) {
            $ids = I('post.ids/a');

            if ($this->message_model->where(array('id' => array('in', $ids)))->save(array('status' => '-1')) !== false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }
    }
}