<?php
/**
 * Created by PhpStorm.
 * User: joan
 * Date: 2017/4/13
 * Time: 17:22
 */
namespace Goods\Controller;

use Common\Controller\AdminbaseController;

/**后台商品分类控制器
 * Class AdminCategoryController
 * @package Goods\Controller
 */
class AdminCategoryController extends AdminbaseController
{

    protected $catModel;

    function _initialize()
    {
        parent::_initialize();
        $this->catModel = M("category");
    }

    public function index()
    {
        $this->_lists(array("is_show" => array('in', array(1, 0))));
        $this->display();
    }


    /**
     * 系统信息列表处理方法,根据不同条件显示不同的列表
     * @param array $where 查询条件
     */
    private function _lists($where = array())
    {
        $data = $this->catModel->where($where)->field("cat_id,cat_name,parent_id,cat_desc,is_show,add_time,admin")->select();
        $mainCategory = array();
        $childCategory = array();
        foreach ($data as $key => $value) {
            $value['parent_id'] == 0 ? $mainCategory[] = $value : $childCategory[] = $value;
        }

        foreach ($mainCategory as $_key => $_value) {
            foreach ($childCategory as $_k => $_v) {
                if ($_value['cat_id'] == $_v['parent_id']) {
                    $mainCategory[$_key]['child'][] = $_v;

                }
            }

        }

        $newArr = array();
        foreach ($mainCategory as $k => $v) {
            if ($v['parent_id'] == '0') $newArr[] = array(
                "cat_id" => $v['cat_id'],
                "cat_name" => $v['cat_name'],
                "parent_id" => $v['parent_id'],
                "cat_desc" => $v['cat_desc'],
                "is_show" => $v['is_show'],
                "add_time" => $v['add_time'],
                "admin" => $v['admin']
            );
            foreach ($v['child'] as $k1 => $v1) {
                if ($v1['parent_id'] != '0') $v1['cat_name'] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;├─" . $v1['cat_name'];
                $newArr[] = array(
                    "cat_id" => $v1['cat_id'],
                    "cat_name" => $v1['cat_name'],
                    "parent_id" => $v1['parent_id'],
                    "cat_desc" => $v1['cat_desc'],
                    "is_show" => $v1['is_show'],
                    "add_time" => $v1['add_time'],
                    "admin" => $v1['admin']
                );
            }

        }

        $page = $this->page(count($newArr), 2000);
        $this->assign("page", $page->show('Admin'));
        $this->assign("count", array(count($mainCategory)+count($childCategory), count($mainCategory), count($childCategory)));
        $this->assign("data_lists", $newArr);
    }


    protected function _get_level($id, $array = array(), $i = 0)
    {

        if ($array[$id]['parent_id'] == 0 || empty($array[$array[$id]['parent_id']]) || $array[$id]['parent_id'] == $id) {
            return $i;
        } else {
            $i++;
            return $this->_get_level($array[$id]['parent_id'], $array, $i);
        }

    }

    public function add()
    {
        if (IS_POST) {
            $data = I("");
            $data['admin'] = $_SESSION['name'];
            if (!$data['cat_name']) $this->error("请输入分类名称!");
            if ($this->catModel->where(array("cat_name" => $data['cat_name']))->getField("cat_id")) $this->error("分类名称不能重复添加!");
            if ($this->catModel->add($data)) $this->success(L('添加成功'), U("AdminCategory/index"));
            else  $this->error("添加失败!");
        } else {
            $pid = I("pid", "0");
            $data_lists = $this->catModel->where(array("parent_id" => 0))->select();
            $this->assign("pid", $pid);
            $this->assign("cat_arr", $data_lists);
            $this->display();
        }

    }

    public function edit()
    {
        if (IS_POST) {
            $data = I("");
            $data['admin'] = $_SESSION['name'];

            if (!$data['cat_name']) $this->error("请输入分类名称!");
            //if (!$data['parent_id']) $this->error("分类id不能为空!");
            if ($this->catModel->where(array("cat_name" => $data['cat_name']))->getField("cat_id")) $this->error("分类名称不能重复添加!");
            $this->catModel->where(array("cat_id" => $data['cat_id']))->save($data);
            $this->success(L('修改成功'), U("AdminCategory/index"));
        } else {
            $cat_id = I("id", 0);
            $info = $this->catModel->where(array("cat_id" => $cat_id))->find();
            $data_lists = $this->catModel->where(array("parent_id" => 0))->select();
            $this->assign("cat_arr", $data_lists);
            $this->assign("info", $info);
            $this->display();
        }

    }

    //删除
    public function delete()
    {
        if (isset($_GET['id'])) {
            $id = I("get.id", 0, 'intval');
            if ($this->catModel->where(array('cat_id' => $id))->save(array('is_show' => '-1')) !== false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }

        if (isset($_POST['ids'])) {
            $ids = I('post.ids/a');

            if ($this->catModel->where(array('cat_id' => array('in', $ids)))->save(array('is_show' => '-1')) !== false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }
    }
}
