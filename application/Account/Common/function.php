<?php
    function data_change($date)
    {
        $y=substr($date,0,4);
        $m=substr($date,4,2);
        $d=substr($date,6,2);
        $day=$y."-".$m."-".$d;
        return $day;
    }



