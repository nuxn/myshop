<?php
/**
 * Created by PhpStorm.
 * User: joan
 * Date: 2017/2/22
 * Time: 18:06
 */
namespace App\Controller;

use Common\Controller\AdminbaseController;

/**手机短信
 * Class AdminSmsController
 * @package Message\Controller
 */
class AdminAppController extends AdminbaseController
{

    protected $appModel;

    function _initialize()
    {
        parent::_initialize();
        $this->appModel = M("app_version");
    }

    public function index()
    {
        $appModel = D('app_version p');
        $list = $appModel->field('p.id,p.version_name,p.change_log,p.client,p.version_code,p.app_name,p.apk_url,p.creat_time,p.update_time,p.admin')->order('id desc')->select();
        foreach ($list as &$v) {
            $v['change_log'] = htmlspecialchars_decode($v['change_log']);
        }
        $this->assign('data_lists', $list);
        $this->display();
    }

    public function add()
    {
        if ($_POST) {
            $data = I("post");
            $apk_url = $this->upload();
            if (!$apk_url) {
                $this->error('请上传app');
            }
            $data['creat_time'] = time();
            $data['change_log'] = htmlspecialchars_decode($data['change_log']);
            $data['update_time'] = time();
            $data['apk_url'] = $apk_url;
            $data['admin'] = $_SESSION['name'];

            if ($this->appModel->add($data)) $this->success('添加成功', U('AdminApp/index'));
            else $this->error('添加失败');
        } else {
            $this->display();
        }
    }

    public function upload($status = 1)
    {
        $upload = new \Think\Upload();
        $upload->maxSize = 0;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'txt', 'apk');// 设置附件上传类型
        $upload->rootPath = './data/upload/version/'; // 设置附件上传根目录
        $upload->saveName = ''; // 设置附件上传根目录

        $info = $upload->uploadOne($_FILES['file']);
        if (!$info) {
            if ($status) $this->error($upload->getError());
            return '';
        } else
            return 'http://' . $_SERVER['HTTP_HOST'] . $upload->rootPath . $info['savepath'] . $info['savename'];
    }

    function edit()
    {
        $appModel = M('app_version');
        if ($_POST) {
            $data = I("post");
            $apk_url = $this->upload(0);
            if ($apk_url) $data['apk_url'] = $apk_url;
            $data['change_log'] = htmlspecialchars_decode($data['change_log']);
            $data['update_time'] = time();
            $data['admin'] = $_SESSION['name'];

            $appModel->where(array("id" => $data['id']))->save($data);
            $this->success('修改成功', U('AdminApp/index'));
        } else {
            $id = I('id');
            $info = $appModel->where(array("id" => $id))->find();
            $this->assign('post', $info);
            $this->display();
        }
    }

    function delete()
    {
        $appModel = D('app_version');
        if ((!isset($_GET['id']) || empty($_GET['id'])) && (!isset($_POST['id']) || empty($_POST['id']))) {
            $this->error('请选择要删除的版本！');
        }
        if (isset($_POST['id']) && is_array($_POST['id'])) {
            $ids = implode(',', $_POST['id']);
            $result = $appModel->delete($ids);
        } else {
            $id = intval($_GET['id']);
            $result = $appModel->where('id=' . $id)->delete();
        }
        if ($result) $this->success('删除成功', U('AdminApp/index'));
        else $this->error('删除失败');
    }

}
