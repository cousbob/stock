<?php
ini_set("memory_limit","512M"); 
set_time_limit(0);
require_once 'stock.inc.php';
foreach($_GET  as $key=>$val){$$key=$val;}
foreach($_POST as $key=>$val){$$key=$val;}

//預設值
$yy     = date('Y');
$mm     = date('m');
$dd     = date('d');
$today  = $yy.$mm.$dd;
$get_stock = new cGetStockInfo();
$sch_date  = array();
$com_cal_data = $get_stock->mGetComCalData($sch_date,100,array(1,2,3,4,5,6));
foreach ($com_cal_data['sno'] as $_sno=>$_data2){
    foreach ($_data2 as $_combo_days=>$_data){
        
        foreach ((array)$_data['data'] as $_idx=>$_data1){
            echo '買入日期='.format_date($_data1['buy_date'][$_combo_days-1],2).',買入股價='.$_data1['bd_index'].'('.$_data1['bd_tw_index'].')';
            echo ',賣出日期='.format_date($_data1['sell_date'],2).',賣出股價='.$_data1['sd_index'].'('.$_data1['sd_tw_index'].')';
            echo ',台指漲跌='.$_data1['ud_tw_index'];
            echo ',漲跌百分比='.$_data1['ud_per'].'%,漲跌金額=$'.($_data1['ud']*1000);
            echo ',持股漲跌='.$_data1['diff_percent'].'<br />';
        }
        
        echo ($_data['rank']+1).'名('.$_combo_days.'天),股票代號='.$_sno.',漲跌總和='.$_data['total_per'].'%<br />';
        echo '共'.$_data['sum'].'筆,上漲'.$_data['win'].'筆,下跌'.$_data['lose'].'筆<br />';
        echo '成功率='.$_data['succ_per'].'%<br />';
        echo '漲跌金額=$'.$_data['total_money'].'<br />';
        echo '<br />';
    }
}

if (''!=$_POST['f_key']){
    switch ($f_key){
        //新增
        case '2':
            break;
    }    
}

$html  = '';
$html .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
$html .= '<html xmlns="http://www.w3.org/1999/xhtml">';
$html .= '<head>';
$html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
$html .= '<title>股票演算</title>';
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
              $("#s_buy_date").datepicker({dateFormat:\'yymmdd\'});
              $("#s_sel_date").datepicker({dateFormat:\'yymmdd\'});
              $(".div_BKTop").click(function(){
                  var $body = (window.opera) ? (document.compatMode == "CSS1Compat" ? $(\'html\') : $(\'body\')) : $(\'html,body\');
                  $body.animate({scrollTop: 0}, 600);
                  return false;
              });
          })
          function send_key(key){
              document.form1.f_key.value = key; 
              document.form1.submit();
          }
';
$html .= '</script>';
$html .= '</head>';
$html .= '<body>';
$html .= '<form name="form1" action="cal_stock.php" method="post">';
$html .= '<input name="f_key" type="hidden" /> ';


$html .= '</form>';
$html .= '</body>';
$html .= '</html>';
print($html);

?>
