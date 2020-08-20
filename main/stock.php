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
$arr_day = array(1);     // 抓外資買賣量資料的天數
$arr_sel_day     = array('1','7','10','15','30','60');//可以選擇要往前的天數
$sel_buy_percent = 0.15; // 當這檔股票, 買進超過當天總買進股票的百分比, 預設為0.15%
$sel_sel_percent = 0.03; // 當這檔股票, 賣出超過當天總買進股票的百分比, 預設為0.03%
$sel_sta_percent = 0.8;

$gain_stock    = new cGainStockInfo();
$get_stock     = new cGetStockInfo();
$all_gain_type = $gain_stock->mGetAllGainType();
$gain_type_lastdate = $gain_stock->mGetGainTypeLastdate();

if (''==$_POST['f_key']) $_POST['f_key'] = '2';

if (''!=$_POST['f_key']){
    switch ($_POST['f_key']){
        //查詢按鈕
        case '1':break;
        //預設要抓股票的資訊
        case '2':
            $f_bos_type       = array('mGetMorInfo','mOutHoldPrecent','mTwIndex','mThreebigBos');
            $sch_date         = $today;              // 查詢哪天開始的買賣情形, 預設為當天
            $sel_sta_days     = 7;                   // 查詢開始日期往前算多久以前的天數, 預設5天
            $bos_begdate      = nextday($today,-1);  // 抓股票資訊的結束日期, 預設為當天
            $bos_enddate      = $today;              // 抓股票資訊的開始日期, 預設為當天-1天
            break;
        //依日期抓取資料
        case '4':
            $gain_stock->mSetGetType(2);
            $gain_stock->mSetBegdate($bos_begdate);
            $gain_stock->mSetEnddate($bos_enddate);
        //預設抓2天內的資料
        case '3':
            $arr_type = array();
            foreach ((array)$f_bos_type as $_val){
                $arr_type[$_val] = true;
            }
            $gain_stock->mGainStock($arr_type);
            //重新計算摩跟最常投資的資料
            $gain_stock->mGetMorLoveSotck();
            break;
        //重新計算外資持股量
        case '5':
            $arr_type = array();
            $arr_type['mGetHoldStock'] = true;
            $gain_stock->mGainStock($arr_type);
            break;
    }    
}
$sel_sta_days_per = floor($sel_sta_days*$sel_sta_percent);

//持有的股票
$_file_path = "./../data/stock/keyin_stock.txt";
$keyin_data = array();
if (!isset($keyin_str)){
    $_handle   = fopen($_file_path, "r");
    $keyin_str = fgets($_handle);
} else {
    $_handle = fopen($_file_path, "w");
    fwrite($_handle, $keyin_str);
}   
fclose($_handle);
$keyin_data = explode(',',$keyin_str);
//持續追蹤的股票
$_file_path = "./../data/stock/track_stock.txt";
$track_data = array();
if (!isset($track_str)){
    $_handle   = fopen($_file_path, "r");
    $track_str = fgets($_handle);
} else {
    $_handle = fopen($_file_path, "w");
    fwrite($_handle, $track_str);
}
fclose($_handle);
$track_data   = explode(',',$track_str);
$arr_com      = $get_stock->mGetCom();
$arr_com_long = $get_stock->mGetComLong();
$_arr = array();
$_arr['last_date'] = $sch_date;
$_arr['preday']    = $sel_sta_days;
//塞資料
$get_stock->mSetScgDate($_arr);
$get_stock->mSetPreDayPer($sel_sta_percent);
$get_stock->mSetBuyPercent($sel_buy_percent);
$get_stock->mSetSelPercent($sel_sel_percent);
$get_stock->mSetMorDays($arr_day);
$get_stock->mSetKeyinData($keyin_data);
$get_stock->mSetTrackData($track_data);
$arr_stock    = $get_stock->mGetStockInfo();  //計算出外資買賣量
$first_wkdate = $get_stock->mGetFirstWkdate();//取得第一個工作日
$last_wkdate  = $get_stock->mGetLastWkdate(); //取得最後一個工作日
$arr_save_stock = $get_stock->mGetSaveStock();//取得要存股的股票

//下拉天數
$sel_day_str  = '<select name="sel_sta_days" id="sel_sta_days">';
foreach ($arr_sel_day as $_day){
    $selected     = ($_day==$sel_sta_days)?"selected":"";
    $sel_day_str .= '<option value="'.$_day.'" '.$selected.' >'.$_day.'天</option>';
}
$sel_day_str .= '</select> ';

//日期選擇欄位
$sel_date_inp = '<input type="text" id="sch_date" name="sch_date" value="'.$sch_date.'" style="width:60px" />';
//持有的股票
$check_inp    = '<input type="text" name="keyin_str" value="'.$keyin_str.'" style="width:500px;" />';
//追蹤中的股票
$check1_inp   = '<input type="text" name="track_str" value="'.$track_str.'" style="width:500px;" />';
//查詢按鈕
$click        = 'onclick="send_key(1);"';
$search_btn   = '<input name="f_btn" type="button" value="查詢" '.$click.' />';
//初始值按鈕
$click        = ' onclick="document.forms[0].sch_date.value=\'\';document.forms[0].sel_sta_days.value=\'\';send_key(2);" ';
$ori_btn      = '<input name="f_btn" type="button" value="初始值" '.$click.' />';
//外資買賣量按鈕
//$click        = ' onclick="window.open(\'./machi/get_mor_info.php?close=true&get_type=1\');"';
$click             = 'onclick="send_key(3);"';
$get_bos_btn       = '<input name="f_btn" type="button" value="抓'.$today.'股票資訊" '.$click.' />';
$click             = 'onclick="javascript:(confirm(\'確定依區間抓取?\')?send_key(4):\'\')"';
$get_bosbydate_btn = '<input name="f_btn" type="button" value="抓取區間股票資訊" '.$click.' />';
$bos_type_chk      = '';
foreach ($all_gain_type as $_key=>$_val){
    if ('mGetHoldStock'==$_key) continue;
    $_lastdate = $gain_type_lastdate[$_key];
    $_date_str = ($_lastdate==$today)?'':'<font color="#4D8EA9">('.$_lastdate.')</font>';
    $_checked  = '';
    if (!empty($f_bos_type) && in_array($_key,$f_bos_type)){
        $_checked = 'checked';
    }
    $bos_type_chk .= '<input id="f_bos_type'.$_key.'" name="f_bos_type[]" type="checkbox" value="'.$_key.'" '.$_checked.' /> ';
    $bos_type_chk .= '<label for="f_bos_type'.$_key.'">'.$_val.$_date_str.'</label>';
    $bos_type_chk .= '<br />';
}
$bos_type_chk = substr($bos_type_chk,0,-6);
//以日期抓取股票資料的開始與結束日期
$bos_begdate_inp = '<input type="text" id="bos_begdate" name="bos_begdate" value="'.$bos_begdate.'" style="width:60px" />';
$bos_enddate_inp = '<input type="text" id="bos_enddate" name="bos_enddate" value="'.$bos_enddate.'" style="width:60px" />';

//重新計算外資持股按鈕
//$click        = ' onclick="window.open(\'./machi/get_hold_stock.php?close=true\');" ';
$click        = 'onclick="send_key(5);"';
$re_hold_btn  = '<input type="button" value="重新計算持股" '.$click.' ></input>';

$html  = '';
$html .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
$html .= '<html xmlns="http://www.w3.org/1999/xhtml">';
$html .= '<head>';
$html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
$html .= '<title>股票&nbsp;'.$last_wkdate.'</title>';
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
$html .= '.div_tool {';
$html .= '    position: fixed;';
$html .= '    width: 30px;';
$html .= '    height: 30px;';
$html .= '    bottom: 70px;';
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
            $(".div_tool").click(function(){
                window.open("https://tw.stock.yahoo.com/us/");
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
$html .= '<table width="1280">';
$html .= '<tr>';
$html .= '<th width="320" colspan="2">查詢條件</th>';
$html .= '<th width="420" colspan="2">抓取資料</th>';
$html .= '<th width="540" colspan="2">存股資訊</th>';
$html .= '</tr>';
$html .= '<tr>';
$html .= '<td width="180" align="right">查詢起始日期:</td>';
$html .= '<td width="140" >'.$sel_date_inp.'</td>';
$html .= '<td width="120" align="right">每天執行:</td>';
$html .= '<td width="300" >';
$html .= $bos_type_chk.'<br />';
$html .= $get_bos_btn;
$html .= '</td>';
$html .= '<td width="540" rowspan="7" valign="top" >';
foreach ($arr_save_stock as $_sno => $_name){
    $html .= $_name.'('.$_sno.')&nbsp;&nbsp;';
    $html .= '<a href="https://www.google.com/search?q='.$_sno.'&oq='.$_sno.'" target="_blank">G</a>&nbsp;&nbsp;';
    $html .= '<a href="https://www.cmoney.tw/finance/f00029.aspx?s='.$_sno.'" target="_blank">CM</a>&nbsp;&nbsp;';
    $html .= '<a href="https://goodinfo.tw/StockInfo/StockDetail.asp?STOCK_ID='.$_sno.'" target="_blank">GInfo</a>&nbsp;&nbsp;';
    $html .= '<a href="https://goodinfo.tw/StockInfo/StockDividendSchedule.asp?STOCK_ID='.$_sno.'" target="_blank">除權息</a>&nbsp;&nbsp;';
    $html .= '<a href="https://goodinfo.tw/StockInfo/StockBzPerformance.asp?STOCK_ID='.$_sno.'" target="_blank">經營績效</a>&nbsp;&nbsp;';
    $html .= '<br />';
}
$html .= '※看官股持股,除息日程,ROE大於10%且ROA大於10%,EPS大於15倍,殖利率大於5%';
$html .= '</td>';
$html .= '</tr>';
$html .= '<tr>';
$html .= '<td align="right">查詢天數:</td>';
$html .= '<td >'.$sel_day_str.'</td>';
$html .= '<td width="120" align="right">重抓資料:</td>';
$html .= '<td width="350">';
$html .= $get_bosbydate_btn.'&nbsp;&nbsp;'.$bos_begdate_inp.'~'.$bos_enddate_inp.'<br />';
$html .= '</td>';
$html .= '</tr>';
$html .= '<tr><td align="right">查詢條件:</td>';
$html .= '<td>'.$search_btn.'&nbsp;'.'&nbsp;'.$ori_btn.'</td>';
$html .= '<td align="right">重新計算:</td>';
$html .= '<td>'.$re_hold_btn.'</td>';
$html .= '</tr>';
$html .= '<tr><td align="right">持有的股票:</td>';
$html .= '<td colspan="3">'.$check_inp.'</td>';
$html .= '</tr>';
$html .= '<tr><td align="right">追蹤的股票:</td>';
$html .= '<td colspan="3">'.$check1_inp.'</td>';
$html .= '</tr>';
$html .= '<tr>';
$html .= '</tr>';
$html .= '<tr><td align="right">連結:</td>';
$html .= '<td colspan="3">';
$html .= '<a href="./sch_stock.php"   target="_blank">股票資訊查詢</a>&nbsp;&nbsp;';
$html .= '<a href="./price_stock.php" target="_blank">查詢股價</a>&nbsp;&nbsp;';
$html .= '<a href="./trans_stock.php" target="_blank">買賣紀錄</a>&nbsp;&nbsp;';
$html .= '<a href="./cal_stock.php"   target="_blank">股票演算</a>&nbsp;&nbsp;';
$html .= '<a href="https://www.google.com/search?q=台股指數&oq=台股指數" target="_blank">台股</a>&nbsp;&nbsp;';
$html .= '<a href="https://tw.stock.yahoo.com/us/" target="_blank">美股</a>&nbsp;&nbsp;';
$html .= '<br />';
$html .= '<a href="https://histock.tw/stock/dividend.aspx" target="_blank">股利發放(HiStock)</a>&nbsp;&nbsp;';
$html .= '<a href="https://goodinfo.tw/StockInfo/StockDividendScheduleList.asp?MARKET_CAT=%E5%85%A8%E9%83%A8&INDUSTRY_CAT=%E5%85%A8%E9%83%A8&YEAR=%E5%8D%B3%E5%B0%87%E9%99%A4%E6%AC%8A%E6%81%AF" target="_blank">股利發放(Goodinfo)</a>&nbsp;&nbsp;';
$html .= '</td>';
$html .= '</tr>';
$html .= '</table>';
$html .= '起始日期:'.format_date($first_wkdate,2).'~結束日期:'.format_date($last_wkdate,2).',';
$html .= $sel_sta_days.'天內有超過'.$sel_sta_days_per.'天買入'.$sel_buy_percent.'%以上,賣出超過'.$sel_sel_percent.'%以上';
$html .= '<br />';
$all_show_type = $get_stock->mGetAllShowType();
$arr_type      = array();
foreach ($all_show_type as $_key){
    if ('low_stock_out'==$_key || 'low_stock_tou'==$_key || 'top_stock_out'==$_key || 'top_stock_tou'==$_key) continue;
    $arr_type[$_key] = true;
}
$_str  = $get_stock->mShowStockInfo($arr_type);
$html .= $_str;
$html .= '</form>';
$html .= '<div class="div_BKTop" title="回頂端">top</div>';
$html .= '<div class="div_tool">美股</div>';
$html .= '</body>';
$html .= '</html>';
print($html);
?>
