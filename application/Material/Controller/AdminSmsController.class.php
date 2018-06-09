<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/3
 * Time: 16:13
 */

namespace Material\Controller;

use Common\Controller\AdminbaseController;

/**物料管理
 * Class AdminMaterialController
 * @package Pay\Controller
 */
class AdminMaterialcateController extends AdminbaseController
{

    protected $material_model, $menu_model, $user_model;
    const brand = 'YPT';

    public function _initialize()
    {
        parent::_initialize();
        $this->material_model = M("material");
        $this->shopcates = M("merchants_cate");
    }

    public function index()
    {
        $this->_lists(array('status' => '0'));
        $this->display();
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
            $where['m.add_time'] = array(
                array('EGT', $start_time)
            );
        }

        $end_time = strtotime(I('request.end_time'));
        if (!empty($end_time)) {
            if (empty($where['m.add_time'])) {
                $where['m.add_time'] = array();
            }
            array_push($where['m.add_time'], array('ELT', $end_time));
        }

        $keyword = I('request.title');
        if (!empty($keyword)) {
            $where['title'] = array('like', "%$keyword%");
        }

        $this->material_model
            ->alias("m")
            ->where($where);

        $count = $this->material_model->count();

        $page = $this->page($count, 20);

        $this->material_model
            ->alias("m")
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->order("m.id DESC");
        $data_lists = $this->material_model->select();

        foreach ($data_lists as $k => $v) {
            $name = $v['name'];
            $map['no_number'] = array('like', "%$name%");
            $num = $this->shopcates->where($map)->count();
            $data_lists[$k]['num'] = $num;
        }

        $this->assign("page", $page->show('Admin'));
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("data_lists", $data_lists);
    }

    public function add()
    {
        if (IS_POST) {
            $data = I("post.post");
            if (empty($data['title'])) {
                $this->error("请输入分类名称！");
            }
            if (empty($data['name'])) {
                $this->error("请输入分类代号！");
            }
            if (preg_match('/^[a-z]+$/', $data['name'])) $data['name'] = strtoupper($data['name']);

            if (!preg_match('/^[A-Z]+$/', $data['name'])) $this->error("分类代号必须全部大写！");

            $data['add_time'] = time();
            $data['update_time'] = time();
            $data['admin_id'] = 1;

            if ($this->material_model->add($data)) {
                $this->success("添加成功！");
            } else {
                $this->error("添加失败！");
            }

        } else {
            $this->display();
        }
    }

    public function edit()
    {
        if (IS_POST) {
            $data = I("post.post");
            if (empty($data['title'])) {
                $this->error("请输入分类名称！");
            }
            if (empty($data['name'])) {
                $this->error("请输入分类代号！");
            }
            if (preg_match('/^[a-z]+$/', $data['name'])) $data['name'] = strtoupper($data['name']);

            if (!preg_match('/^[A-Z]+$/', $data['name'])) $this->error("分类代号必须全部大写！");

            $this->material_model->where(array("id" => $data['id']))->save($data);
            $this->success("保存成功！");
        } else {
            $id = I("get.id", 0, 'intval');
            $info = $this->material_model->where(array("id" => $id))->find();
            $this->assign("post", $info);
            $this->display();
        }
    }

    //删除
    public function delete()
    {
        if (isset($_GET['id'])) {
            $id = I("get.id", 0, 'intval');
            if ($this->material_model->where(array('id' => $id))->save(array('status' => '-1')) !== false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }

        if (isset($_POST['ids'])) {
            $ids = I('post.ids/a');

            if ($this->material_model->where(array('id' => array('in', $ids)))->save(array('status' => '-1')) !== false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }
    }
}
