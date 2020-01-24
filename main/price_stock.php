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
if (''!=$_POST['f_key']){
    switch ($f_key){
        case '1':
            $sdatas     = array();
            $com_type   = array('morganstanley');
            $sch_date['first_date'] = $f_firstdate;
            $sch_date['last_date']  = $f_lastdate;
            $arr_sno    = explode(',',$f_sno); 
            $mor_data   = $get_stock->mGetMorData($sch_date,$com_type,$arr_sno);
            $stock_info = $get_stock->mGetStockIndex($sch_date,$arr_sno);
            $tw_index   = $get_stock->mGetTWIndex($sch_date);
            
            foreach ($arr_sno as $_sno){
                $_mdatas = $mor_data[$_sno]['morganstanley'];
                $_sdatas = $stock_info[$_sno];
                foreach ($_sdatas as $_date=>$_sdata){
                    $_mdata = $_mdatas[$_date][1];
                    $sdatas[$_sno][$_date]['close_index'] = $_sdata['close_index'];
                    $sdatas[$_sno][$_date]['mor_bms']     = $_mdata['bms'];
                    $sdatas[$_sno][$_date]['tw_index']    = $tw_index[$_date]['tw_index'];
                    $sdatas[$_sno][$_date]['close_index'] = $_sdata['close_index'];
                }
            }
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
          }';
$html .= '</script>';
$html .= '</head>';
$html .= '<body>';
$html .= '<form name="form1" action="price_stock.php" method="post">';
$html .= '<input name="f_key" type="hidden" /> ';
$html .= '<table border="1">';
$html .= '<tr><th>股價查詢</th></tr>';
$html .= '<tr>';
$html .= '<td valign="top">';
$html .= '股票代號：<input type="text" name="f_sno" value="'.$f_sno.'" /><br />';
$html .= '起訖日期：<input type="text" name="f_firstdate" value="'.$f_firstdate.'" style="width:60px;" />~';
$html .= '<input type="text" name="f_lastdate" value="'.$f_lastdate.'" style="width:60px;" /><br />';
$html .= '<input type="button" name="f_btn" value="股價查詢" onclick="send_key(1);">';
$html .= '</td>';
$html .= '</tr>';
$html .= '</table>';
if (!empty($sdatas)){
    foreach ($sdatas as $_sno=>$_sdata){
        $html .= '<table border="1">';
        $html .= '<tr>';
        $html .= '<th colspan="4">股票代號：'.$_sno.'</th>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th>日期</th>';
        $html .= '<th>股價</th>';
        $html .= '<th>摩根買賣量</th>';
        $html .= '<th>台指</th>';
        $html .= '</tr>';
        foreach ($_sdata as $_date=>$_data){
            $html .= '<tr>';
            $html .= '<td>'.$_date.'</td>';
            $html .= '<td>'.$_data['close_index'].'</td>';
            $html .= '<td>'.$_data['mor_bms'].'</td>';
            $html .= '<td>'.$_data['tw_index'].'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
    }
}
$html .= '</form>';
$html .= '</body>';
$html .= '</html>';
print($html);

?>
