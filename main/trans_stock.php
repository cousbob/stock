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
$arr_type  = array();
$arr_type[1] = '外資投信當天大量買入';
$arr_type[2] = '外資投信當天大量賣出';
$arr_type[3] = '外資多天大量買入';
$arr_type[4] = '投信多天大量買入';
$arr_type[5] = '外資多天大量賣出';
$arr_type[6] = '投信多天大量賣出';

$trans_data_path = './../data/stock/trans/';
$file_path   = $trans_data_path.'./txt_trans.txt';
if (!is_dir($trans_data_path)) mkdir($trans_data_path);

$last_idx    = 0; //紀錄最大筆資料的序號
$org_content = '';//存原始的資料
$_handle     = fopen($file_path, "r");
while (!feof($_handle)){
    $_str  = fgets($_handle);
    $org_content .= $_str;
    $_data = explode('`',$_str);
    $_idx  = $_data[0];
    if ($_idx > $last_idx){
        $last_idx = $_idx;
    }
}
fclose($_handle);

if (''!=$_POST['f_key']){
    switch ($f_key){
        //新增
        case '2':
            if (''==$s_sel_price){
                $_buy      = floor($s_buy_price*$s_buy_num*(0.001425));
                $_hand_fee = (20>$_buy?20:$_buy);
                $_tax      = 0;
                $_incom    = '';
            } else {
                $_buy      = floor($s_buy_price*$s_buy_num*(0.001425));
                $_sel      = floor($s_sel_price*$s_buy_num*(0.001425));
                $_hand_fee = (20>$_buy?20:$_buy)+(20>$_sel?20:$_sel);
                $_tax      = floor($s_sel_price*$s_buy_num*(0.003));
                $_incom    = bcsub($s_sel_price, $s_buy_price, 2)*$s_buy_num; 
            }
            
            $_str  = ($last_idx+1).'`'.$s_sno.'`'.$s_buy_num.'`'.$s_buy_price.'`'.$s_sel_price.'`'.$s_buy_date.'`'.$s_sel_date.'`'.$s_interval;
            $_str .= '`'.$s_buy_reason.'`'.$s_sel_reason.'`'.implode(',',(array)$s_type).'`'.$_incom.'`'.$_tax.'`'.$_hand_fee;
            if (''!=$org_content){
                $save_str = $org_content."\r\n".$_str;
            } else {
                $save_str = $_str;
            }
            $file = fopen($file_path,"w");
            fwrite($file,$save_str);
            fclose($file);
            $s_sno        = '';
            $s_buy_num    = '';
            $s_buy_price  = '';
            $s_sel_price  = '';
            $s_buy_date   = '';
            $s_sel_date   = '';
            $s_interval   = '';
            $s_buy_reason = '';
            $s_sel_reason = '';
            $s_type       = '';
            break;
        //修改
        case '3':
            $_handle  = fopen($file_path, "r");
            $save_str = '';
            while (!feof($_handle)){
                $content = fgets($_handle);
                $_data   = explode('`',$content);
                $_idx    = $_data[0];
                if ($_idx != $edit_idx) {
                    $save_str .= $content;
                } else {
                    if (''==$s_sel_price){
                        $_buy      = floor($s_buy_price*$s_buy_num*(0.001425));
                        $_hand_fee = (20>$_buy?20:$_buy);
                        $_tax      = 0;
                        $_incom    = '';
                    } else {
                        $_buy      = floor($s_buy_price*$s_buy_num*(0.001425));
                        $_sel      = floor($s_sel_price*$s_buy_num*(0.001425));
                        $_hand_fee = (20>$_buy?20:$_buy)+(20>$_sel?20:$_sel);
                        $_tax      = floor($s_sel_price*$s_buy_num*(0.003));
                        $_incom    = bcsub($s_sel_price, $s_buy_price, 2)*$s_buy_num;
                    }
                    $_str  = $edit_idx.'`'.$s_sno.'`'.$s_buy_num.'`'.$s_buy_price.'`'.$s_sel_price.'`'.$s_buy_date.'`'.$s_sel_date.'`'.$s_interval;
                    $_str .= '`'.$s_buy_reason.'`'.$s_sel_reason.'`'.implode(',',(array)$s_type).'`'.$_incom.'`'.$_tax.'`'.$_hand_fee;
                    $_rn   = ($last_idx==$edit_idx?'':"\r\n");
                    $save_str  = $save_str.$_str.$_rn;
                }
            }
            fclose($_handle);
            $file = fopen($file_path,"w");
            fwrite($file,$save_str);
            fclose($file);
            $s_sno        = '';
            $s_buy_num    = '';
            $s_buy_price  = '';
            $s_sel_price  = '';
            $s_buy_date   = '';
            $s_sel_date   = '';
            $s_interval   = '';
            $s_buy_reason = '';
            $s_sel_reason = '';
            $s_type       = '';
            break;
    }    
}

$tick_datas  = array();
if (file_exists($file_path)){
    $_handle  = fopen($file_path, "r");
    while (!feof($_handle)){
        $_str  = fgets($_handle);
        $_data = explode('`',$_str);
        $_idx  = $_data[0];
        $tick_datas[$_idx]['sno']        = $_data[1];
        $tick_datas[$_idx]['buy_num']    = $_data[2];
        $tick_datas[$_idx]['buy_price']  = $_data[3];
        $tick_datas[$_idx]['sel_price']  = $_data[4];
        $tick_datas[$_idx]['buy_date']   = $_data[5];
        $tick_datas[$_idx]['sel_date']   = $_data[6];
        $tick_datas[$_idx]['interval']   = $_data[7];
        $tick_datas[$_idx]['buy_reason'] = $_data[8];
        $tick_datas[$_idx]['sel_reason'] = $_data[9];
        $tick_datas[$_idx]['type']       = explode(',',$_data[10]);
        $tick_datas[$_idx]['income']     = $_data[11];
        $tick_datas[$_idx]['tax']        = $_data[12];
        $tick_datas[$_idx]['hand_fee']   = $_data[13];
    }
    krsort($tick_datas);
    fclose($_handle);
}

$html  = '';
$html .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
$html .= '<html xmlns="http://www.w3.org/1999/xhtml">';
$html .= '<head>';
$html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
$html .= '<title>買賣紀錄</title>';
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
          function send_edit(key){
              $("#edit_idx").val($("#idx_"+key).val());
              $("#span_idx").val($("#idx_"+key).val());              
              $("#s_sno").val($("#sno_"+key).val());
              $("#s_buy_num").val($("#buy_num_"+key).val());
              $("#s_buy_price").val($("#buy_price_"+key).val());  
              $("#s_sel_price").val($("#sel_price_"+key).val());  
              $("#s_buy_date").val($("#buy_date_"+key).val());  
              $("#s_sel_date").val($("#sel_date_"+key).val());  
              $("#s_interval").val($("#interval_"+key).val());  
              $("#s_buy_reason").val($("#buy_reason_"+key).val());  
              $("#s_sel_reason").val($("#sel_reason_"+key).val());
              var arr_type = $("#type_"+key).val().split(",");
              console.log(arr_type);
              for (i=1;i<=6;i++){
                  if (-1!=arr_type.indexOf(i.toString())){
                      $("#s_type"+i).prop("checked",true);
                  } else {
                      $("#s_type"+i).prop("checked",false);
                  }
              }
          }
';
$html .= '</script>';
$html .= '</head>';
$html .= '<body>';
$html .= '<form name="form1" action="trans_stock.php" method="post">';
$html .= '<input name="f_key" type="hidden" /> ';
$html .= '<table border="1">';
$html .= '<tr><th>交易明細</th></tr>';
$html .= '<tr>';
$html .= '<td>';
$html .= '股票代號：<input type="text" id="s_sno"        name="s_sno"        value="'.$s_sno.'"        style="width:60px;"  />&nbsp;&nbsp;';
$html .= '買入張數：<input type="text" id="s_buy_num"    name="s_buy_num"    value="'.$s_buy_num.'"    style="width:60px;"  /><br />';
$html .= '買時股價：<input type="text" id="s_buy_price"  name="s_buy_price"  value="'.$s_buy_price.'"  style="width:60px;"  />&nbsp;&nbsp;';
$html .= '賣時股價：<input type="text" id="s_sel_price"  name="s_sel_price"  value="'.$s_sel_price.'"  style="width:60px;"  /><br />';
$html .= '買時日期：<input type="text" id="s_buy_date"   name="s_buy_date"   value="'.$s_buy_date.'"   style="width:60px;" id="s_buy_date" />&nbsp;&nbsp;';
$html .= '賣時日期：<input type="text" id="s_sel_date"   name="s_sel_date"   value="'.$s_sel_date.'"   style="width:60px;" id="s_sel_date" /><br />';
$html .= '預測區間：<input type="text" id="s_interval"   name="s_interval"   value="'.$s_interval.'"   style="width:60px;"  /><br />';
$html .= '買時原因：<input type="text" id="s_buy_reason" name="s_buy_reason" value="'.$s_buy_reason.'" style="width:200px;" /><br />';
$html .= '賣時原因：<input type="text" id="s_sel_reason" name="s_sel_reason" value="'.$s_sel_reason.'" style="width:200px;" /><br />';
$html .= '修改序號：<span id="span_idx"></span><input type="hidden" id="edit_idx" name="edit_idx" /><br />';
$html_chk = '';
foreach ($arr_type as $_key=>$_str){
    $_checked = '';
    if (!empty($s_type) && in_array($_key,$s_type)){
        $_checked = 'checked';
    }
    $html_chk .= '<input id="s_type'.$_key.'" name="s_type[]" type="checkbox" value="'.$_key.'" '.$_checked.' /> ';
    $html_chk .= '<label for="s_type'.$_key.'">'.$_str.'</label>';
    $html_chk .= '<br />';
}
$html .= $html_chk;
$html .= '<input name="s_idx" type="hidden" /> ';
$html .= '<input type="button" name="f_btn" value="查詢" onclick="send_key(1);">';
$html .= '<input type="button" name="f_btn" value="新增" onclick="send_key(2);">';
$html .= '<input type="button" name="f_btn" value="修改" onclick="send_key(3);">';
$html .= '</td>';
$html .= '</tr>';
$html .= '</table>';
if (!empty($tick_datas)){
    $html .= '<table border="1">';
    $html .= '<tr>';
    $html .= '<th>編號</th>';
    $html .= '<th>股票代號</th>';
    $html .= '<th>買時股價</th>';
    $html .= '<th>賣時股價</th>';
    $html .= '<th>買時日期</th>';
    $html .= '<th>賣時日期</th>';
    $html .= '<th>張數</th>';
    $html .= '<th>損益</th>';
    $html .= '<th>交易稅</th>';
    $html .= '<th>手續費</th>';
    $html .= '<th>合理範圍</th>';
    $html .= '<th>額外狀態</th>';
    $html .= '<th>買時原因</th>';
    $html .= '<th>賣時原因</th>';
    $html .= '</tr>';
    foreach ($tick_datas as $_idx=>$_tdata){ 
        $_sno        = $_tdata['sno'];
        $_buy_num    = $_tdata['buy_num'];
        $_buy_price  = $_tdata['buy_price'];
        $_sel_price  = $_tdata['sel_price'];
        $_buy_date   = $_tdata['buy_date'];
        $_sel_date   = $_tdata['sel_date'];
        $_interval   = $_tdata['interval'];
        $_buy_reason = $_tdata['buy_reason'];
        $_sel_reason = $_tdata['sel_reason'];
        $_type       = $_tdata['type'];
        $_income     = $_tdata['income'];
        $_tax        = $_tdata['tax'];
        $_hand_fee   = $_tdata['hand_fee'];
        $_type_str   = '';
        $_tit_str    = '';
        $_u7_price   = round($_buy_price*1.07,2);
        $_d7_price   = round($_buy_price*0.93,2);
        $_buy_str    = $_buy_price.'<br />'.$_u7_price.'(+7)<br />'.$_d7_price.'(-7)';
        foreach ($arr_type as $_tidx=>$_str){
            if (in_array($_tidx,$_type)){
                $_type_str  = '●';
                $_tit_str  .= '&#10;'.$_str;
            }
        }
        $_tit_str  = substr($_tit_str,5);
        $_type_str = '<span title="'.$_tit_str.'">'.$_type_str.'</span>';
        $_idx_str  = '<span onclick="send_edit(\''.$_idx.'\')" style="cursor:pointer;">'.$_idx.'</span>';
        $html .= '<tr>';
        $html .= '<td>'.$_idx_str   .'<input type="hidden" id="idx_'.$_idx.'"       value="'.$_idx.'"></td>';
        $html .= '<td>'.$_sno       .'<input type="hidden" id="sno_'.$_idx.'"       value="'.$_sno.'"></td>';
        $html .= '<td>'.$_buy_str   .'<input type="hidden" id="buy_price_'.$_idx.'" value="'.$_buy_price.'"></td>';
        $html .= '<td>'.$_sel_price .'<input type="hidden" id="sel_price_'.$_idx.'" value="'.$_sel_price.'"></td>';
        $html .= '<td>'.$_buy_date  .'<input type="hidden" id="buy_date_'.$_idx.'"  value="'.$_buy_date.'"></td>';
        $html .= '<td>'.$_sel_date  .'<input type="hidden" id="sel_date_'.$_idx.'"  value="'.$_sel_date.'"></td>';
        $html .= '<td>'.$_buy_num   .'<input type="hidden" id="buy_num_'.$_idx.'"   value="'.$_buy_num.'"></td>';
        $html .= '<td>'.$_income    .'</td>';
        $html .= '<td>'.$_tax       .'</td>';
        $html .= '<td>'.$_hand_fee  .'</td>';
        $html .= '<td>'.$_interval  .'<input type="hidden" id="interval_'.$_idx.'"   value="'.$_interval.'"></td>';
        $html .= '<td>'.$_type_str  .'<input type="hidden" id="type_'.$_idx.'"       value="'.implode(',',$_type).'"></td>';
        $html .= '<td>'.$_buy_reason.'<input type="hidden" id="buy_reason_'.$_idx.'" value="'.$_buy_reason.'"></td>';
        $html .= '<td>'.$_sel_reason.'<input type="hidden" id="sel_reason_'.$_idx.'" value="'.$_sel_reason.'"></td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
}
$html .= '</form>';
$html .= '</body>';
$html .= '</html>';
print($html);

?>
