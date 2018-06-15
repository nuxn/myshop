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
class AdminMaterialController extends AdminbaseController
{

    protected $material_model, $shopcates, $material_cate_model;
    const brand = 'YPT';

    public function _initialize()
    {
        parent::_initialize();
        $this->material_model = M("material");
        $this->material_cate_model = M("material_cate");
        $this->shopcates = M("merchants_cate");
    }

    public function index()
    {
        $this->_lists(array('m.status' => '0'));
        $cate_lists = $this->material_cate_model->where(array("status" => 0))->field("id,title,name")->select();
        $this->assign("cate_lists", $cate_lists);
        $this->display();
    }

    /**
     * 系统信息列表处理方法,根据不同条件显示不同的列表
     * @param array $where 查询条件
     */
    private function _lists($where = array())
    {
        $cate_id = I('request.cate_id', 0, 'intval');
        if (!empty($cate_id)) {
            $where['m.cate_id'] = $cate_id;
            $this->assign("cate_id", $cate_id);
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
            ->alias("m")//material_cate
            ->field("m.id,m.no_number,m.barcode_img,m.add_time,m.update_time,m.status,c.title")
            ->join("__MATERIAL_CATE__ c on m.cate_id=c.id")
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->order("m.id DESC");
        $data_lists = $this->material_model->select();
        foreach ($data_lists as $k => $v) {
            if (preg_match('/^((?:(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(?:25[0-5]|2[0-4]\d|((1\d{2})|([1 -9]?\d))))$/', $_SERVER['HTTP_HOST'])) $data_lists[$k]['barcode_img'] = 'youngshop/' . $v['barcode_img'];
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
            vendor("phpqrcode.phpqrcode");
            $data = I("post.post");
            if (empty($data['cate_id'])) {
                $this->error("请选择分类！");
            }
            if (empty($data['num'])) {
                $this->error("请输入二维码生成数量！");
            }

            $data['add_time'] = time();
            $data['update_time'] = time();
            $data['admin_id'] = 1;
            //更新分类下面二维码数量
            $this->material_cate_model->where(array("id" => $data['cate_id']))->save(array("num" => $data['num']));
            $cate_name = $this->material_cate_model->where(array("id" => $data['cate_id']))->getField('name');
            for ($i = 0; $i < $data['num']; $i++) {
                $no_number = $this->material_model->where(array("cate_id" => $data['cate_id']))->order("id desc")->getField('no_number');
                $result = $this->material_model->add($data);
                $no_number = substr($no_number, -7) + 1;
                $this->_create_barcode_img($result, $cate_name, $no_number);
            }
            $this->success("添加成功！");

        } else {
            $cate_lists = $this->material_cate_model->where(array("status" => 0))->field("id,title,name")->select();
            $this->assign("cate_lists", $cate_lists);
            $this->display();
        }
    }

    /**生成二维码图片
     * @param $result
     * @param $cate_name
     */
    private function _create_barcode_img($result, $cate_name, $no_number)
    {
        $seven = "000000" . $no_number;
        $no_number = self::brand . $cate_name . substr($seven, -7);

        $value = "http://139.224.74.153/youngshop/index.php?g=Pay&m=Barcode&a=qrcode&id=" . $result;
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 10;//生成图片大小
        //生成二维码图片
        $path_url = "data/upload/material/" . $no_number . ".png";
        // 生成二位码的函数
        $av = new \QRcode();
        ob_clean();
        $av->png($value, $path_url, $errorCorrectionLevel, $matrixPointSize, 2);
        $this->material_model->where('id=' . $result)->setField(array('barcode_img' => $path_url, 'no_number' => $no_number));
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
            $cate = $this->material_cate_model->field("id,title,name")->select();
            $this->assign("cate_lists", $cate);
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
