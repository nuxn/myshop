<?php

namespace Api\Controller;

use Common\Controller\ApibaseController;

class  H5Controller extends ApibaseController
{
    public function index()
    {
        //查看是否存在h5日志
        $this->display();
    }

    public function check()
    {
        //http://sy.youngport.com.cn/index.php?s=api/h6
         //succ('http://sy.youngport.com.cn/index.php?s=api/h6');
         succ('');

        /*if (M('merchants_users')->where(array('id'=>$this->userId))->getField('open_loan')) {
            succ('');
        } else {
            // succ('');

           // succ('http://sy.youngport.com.cn/index.php?s=api/h6');
        }*/

    }
}
