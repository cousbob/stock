<?php
ini_set("memory_limit","512M"); 
set_time_limit(0);
require_once 'stock.inc.php';
foreach($_GET  as $key=>$val){$$key=$val;}
foreach($_POST as $key=>$val){$$key=$val;}

//預設值
$yy      = date('Y');
$mm      = date('m');
$dd      = date('d');
$today   = $yy.$mm.$dd;

$get_stock = new cGetStockInfo();
$sch_date['first_date'] = '20180101';
$sch_date['last_date']  = '20190619';
$stock_number   = 600; 
$com_stock_info = $get_stock->mGetComStockInfo(array(),$stock_number);
$lv_type        = $get_stock->mGetLvType();
$arr_combo      = array(1,2,3,4,5,6);
$com_cal_data   = $get_stock->mGetComCalData($sch_date,$stock_number,$arr_combo);
if (''!=$_POST['f_key']){
    switch ($f_key){
        case '1':break;
        case '2':
            $sch_date     = $today;
            $sel_sta_days = 5;
            break;
    }    
}

$html  = '';
$html .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
$html .= '<html xmlns="http://www.w3.org/1999/xhtml">';
$html .= '<head>';
$html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
$html .= '<title>股票查詢</title>';
$html .= '<link href="css/smoothness/jquery-ui-1.10.3.custom.min.css" type="text/css" rel="stylesheet"  />';
$html .= '<link rel="stylesheet" type="text/css" href="js/anythingSlider1.9.2/css/anythingslider.css" />';
$html .= '<link rel="stylesheet" type="text/css" href="js/anythingSlider1.9.2/css/theme-metallic.css" />';
$html .= '<link rel="stylesheet" type="text/css" href="js/anythingSlider1.9.2/css/theme-minimalist-round.css" />';
$html .= '<link rel="stylesheet" type="text/css" href="js/anythingSlider1.9.2/css/theme-minimalist-square.css" />';
$html .= '<link rel="stylesheet" type="text/css" href="js/anythingSlider1.9.2/css/theme-construction.css" />';
$html .= '<link rel="stylesheet" type="text/css" href="js/anythingSlider1.9.2/css/theme-cs-portfolio.css" />';
$html .= '<script type="text/javascript" src="js/jquery-1.9.1.min.js"></script>';
$html .= '<script type="text/javascript" src="js/main.js"></script>';
$html .= '<script type="text/javascript" src="js/js/jquery-ui-1.10.3.custom.min.js" ></script>';
$html .= '<script type="text/javascript" src="js/anythingSlider1.9.2/js/jquery.anythingslider.min.js"></script>';
$html .= '<script type="text/javascript" src="js/ajax_speech.js"></script>';
//$html .= '<script type="text/javascript" src="js/Speech.js"></script>';
$html .= '<style type="text/css">';
$html .= 'body {font-family:"微軟正黑體";background-color:#E9E6E6;}';
$html .= 'h3 {font-size:24px;font-weight:blod;line-height:0px;}';
$html .= '.div_BKTop {';
$html .= '    position: fixed;';
$html .= '    width: 30px;';
$html .= '    height: 30px;';
$html .= '    bottom: 100px;';
$html .= '    right: 20px;';
$html .= '    font-size: 14px;';
$html .= '    font-weight: bold;';
$html .= '    color: #999;';
$html .= '    text-align: center;';
$html .= '    line-height: 30px;';
$html .= '    border: 2px dashed #CCC;';
$html .= '    cursor: pointer;';
$html .= '}';
$html .= '</style>';
$html .= '<script type="text/javascript">';
$html .= '$(function(){
            $("#sch_date").datepicker({dateFormat:\'yymmdd\'});
            $("#bos_begdate").datepicker({dateFormat:\'yymmdd\'});
            $("#bos_enddate").datepicker({dateFormat:\'yymmdd\'});
            $(".div_BKTop").click(function(){
                var $body = (window.opera) ? (document.compatMode == "CSS1Compat" ? $(\'html\') : $(\'body\')) : $(\'html,body\');
                $body.animate({scrollTop: 0}, 600);
                return false;
            });
          })
          function send_key(key){
            document.form1.f_key.value = key; 
            document.form1.submit();
          }';
$html .= '</script>';
$html .= '</head>';
$html .= '<body>';
$html .= '<form name="form1" action="stock.php" method="post">';
$html .= '<input name="f_key" type="hidden" /> ';
$_sno_str  = '<table width="1024" border="1">';
$chk_combo = array();//記錄6種連續買的情況的投資報酬率的名次
foreach ($com_stock_info as $_sno=>$_data){
    $_cdatas = $com_cal_data['sno'][$_sno];
    $_name   = $_data['name'];
    $_cnt    = $_data['cnt'];
    $_rank   = $_data['rank'];
    $_stock_type = $_data['stock_type'];
    $_all_stock  = $_data['all_stock'];
    
    switch ($_stock_type){
        case 'L':$_type_str = '大';break;
        case 'M':$_type_str = '中';break;
        case 'S':$_type_str = '小';break;
    }
    $_num = $_all_stock*10;
    if ($_num > 100000000){
        $_all_stock_str = floor($_num/100000000).'億股';
    } else {
        $_all_stock_str = floor($_num/10000).'萬股';
    }
    
    $_sno_str .= '<tr>';
    $_sno_str .= '<td colspan="3"><font style="font-weight:bold;font-size:14pt;">';
    $_sno_str .= '排名：'.($_rank+1);
    $_sno_str .= '，股票代號：'.$_sno.'('.$_type_str.'型股)';
    $_sno_str .= '，股票名稱：'.$_name;
    $_sno_str .= '，股本：'.$_all_stock_str;
    $_sno_str .= '，操作次數：'.$_cnt;
    $_sno_str .= '</font></td>';
    $_sno_str .= '</tr>';
    if (!empty($_cdatas)) {
        $_cnt  = 0;
        $_sno_str .= '<tr>';
        //抓出第一二名的資料
        $_combo_lv   = array();
        $_i = 0;
        foreach ($_cdatas as $_combo_days=>$_cdata){
            $_combo_lv[$_i]['unit_money'] = $_cdata['unit_money'];
            $_combo_lv[$_i]['combo_days'] = $_combo_days;
            $_i++;
        }
        //排序
        for ($_i=0;$_i<count($_combo_lv);$_i++){
            for ($_j=0;$_j<count($_combo_lv);$_j++){
                if ($_i!=$_j && $_combo_lv[$_i]['unit_money']>$_combo_lv[$_j]['unit_money']){
                    $_arr = $_combo_lv[$_i];
                    $_combo_lv[$_i] = $_combo_lv[$_j];
                    $_combo_lv[$_j] = $_arr;
                }
            }
        }
        foreach ($_combo_lv as $_lv=>$_combo_data){
            $chk_combo[$_combo_data['combo_days']][$_lv]++;
        }
        foreach ($_cdatas as $_combo_days=>$_cdata){
            $_succ_per = $_cdata['succ_per'];
            if ($_cnt == 3) $_sno_str .= '</tr><tr>';
            $_combo_str = '('.$_combo_days.'天)漲跌總和='.$_cdata['total_per'].'%,成功率='.$_succ_per.'%';
            $_color = '';
            
            if ($_combo_lv[0]['combo_days']==$_combo_days){
                $_color = 'red';
            } else if ($_combo_lv[1]['combo_days']==$_combo_days){
                $_color = 'blue';
            }
            $_combo_str = '<font color="'.$_color.'" styoe="font-weight:bold">'.$_combo_str.'</font>';
            $_sno_str .= '<td valign="top">';
            $_sno_str .= $_combo_str.'<br />';
            $_sno_str .= '共'.$_cdata['sum'].'筆,上漲'.$_cdata['win'].'筆,下跌'.$_cdata['lose'].'筆<br />';
            $_sno_str .= '漲跌金額=$'.$_cdata['total_money'].'<br />';
            $_sno_str .= '投資報酬率=$'.$_cdata['unit_money'].'($/次數)<br />';
            $_sno_str .= '</td>';
            $_cnt++;
        }
        $_sno_str .= '</tr>';    
    }
    /*
    $_sno_str .= '<tr>';
    $_sno_str .= '<th>買減賣</th>';
    $_sno_str .= '<th>買入</th>';
    $_sno_str .= '<th>賣出</th>';
    $_sno_str .= '</tr>';
    $_sno_str .= '<tr>';
    foreach (array('bms','buy','sel') as $_bos_type){
        $_sno_str .= '<td valign="top">';
        $_sno_str .= '單次最多買入張數：'.$_data['max_'.$_bos_type].'<br />';
        $_sno_str .= '全部買入張數：'.$_data['sum_'.$_bos_type].'<br />';
        $_sno_str .= '平均買入張數：'.$_data['avg_'.$_bos_type].'<br />';
        foreach ($_data[$_bos_type.'_lv'] as $_key=>$_val){
            $_sno_str .= $lv_type[$_key].'：'.$_val.'<br />';
        }
        $_sno_str .= '</td>';
    }
    $_sno_str .= '</tr>';
    */
}
$_sno_str .= '</table>';
$_total_str  = '<table border="1" width="1024">';
ksort($chk_combo);
$_total_str .= '<tr><th>1天</th><th>2天</th><th>3天</th></tr>';
for ($i=1;$i<=6;$i++) ksort($chk_combo[$i]);
$_total_str .= '<tr>';
$first_wkdate = '';
$last_wkdate  = '';
$_cnt         = 0;
foreach ($chk_combo as $_combo_days=>$_data){
    $_cdata = $com_cal_data['all'][$_combo_days];
    $first_wkdate = $_cdata['first_wkdate'];
    $last_wkdate  = $_cdata['last_wkdate'];
    if ($_cnt == 3) $_total_str .= '</tr><tr><th>4天</th><th>5天</th><th>6天</th></tr><tr>';
    $_combo_sum_cnt    = $_cdata['combo_sum_cnt'];
    $_sum_succ_per     = $_cdata['sum_succ_per'];
    $_sum_succ_cnt     = $_cdata['sum_succ_cnt'];
    $_sum_succ_money   = $_cdata['sum_succ_money'];
    $_total_money      = $_cdata['total_money'];
    $_avg_money        = floor((0==$_combo_sum_cnt?0:$_total_money/$_combo_sum_cnt));
    $_combo_succ_per   = floor((0==$_sum_succ_cnt?0:$_sum_succ_per/$_sum_succ_cnt));
    $_combo_succ_money = floor((0==$_sum_succ_cnt?0:$_sum_succ_money/$_sum_succ_cnt));
    $_total_str .= '<td>';
    $_total_str .= '總共'.$_cdata['all_cnt'].'筆,上漲'.$_cdata['up_cnt'].'筆,下跌'.$_cdata['dn_cnt'].'筆<br />';
    $_total_str .= '成功率大於'.$_cdata['win_per'].'%有'.$_cdata['succ_cnt'].'筆($'.$_cdata['succ_money'].')<br />';
    $_total_str .= '成功率大於'.$_cdata['win_per'].'%且上漲有'.$_cdata['succ_up_cnt'].'筆$('.$_cdata['succ_up_money'].')<br />';
    $_total_str .= '總連續天數操作次數='.$_combo_sum_cnt.'<br />';
    $_total_str .= '總金額=$'.$_total_money.'<br />';
    $_total_str .= '平均報酬率='.$_avg_money.'$/次數<br />';
    $_total_str .= '總上漲的金額=$'.$_cdata['up_money'].'<br />';
    $_total_str .= '總下跌的金額=$'.$_cdata['dn_money'].'<br />';
    $_total_str .= '與其它連續天數報酬率名次比例：<br />';
    foreach ($_data as $_lv=>$_val){
        $_lv_per     = floor($_val/$_cdata['all_cnt']*100);
        $_lv_per_str = ($_lv_per<10?'&nbsp;&nbsp;'.$_lv_per:$_lv_per);
        $_total_str .= '第'.($_lv+1).'名='.$_lv_per_str.'%,';
        if (2==$_lv || 5==$_lv) $_total_str .= '<br />';
    }
    $_total_str .= '平均成功率='.$_combo_succ_per.'%<br />';
    $_total_str .= '平均成功的報酬率='.$_combo_succ_money.'$/次數<br />';
    $_total_str .= '</td>';
    $_cnt++;
}
$_total_str .= '</tr>';
$_total_str .= '</table>';
$html  .= '<font>資料日期：'.$first_wkdate.'-'.$last_wkdate.'</font>';
$html  .= $_total_str;
$html  .= '<hr>';
$html  .= $_sno_str;
$html  .= '</form>';
$html  .= '</body>';
$html  .= '</html>';
print($html);

?>
