<?php
require_once './machi/cGainStockInfo.class.php';
require_once './machi/cGetStockInfo.class.php';
/**
 * 取得下一天日期
 *
 * @param string  $date   計算基準日期
 * @param integer $days   處理天數
 * @param string  $kind   B=往前,F=往後(預設)
 * @param string  $tp     傳入格式,0=yyyymmdd,1=yyyy/mm/dd)
 * @param string  $mode   回傳格式,0=yyyymmdd,1=yyyy/mm/dd)
 *
 * @return string 處理後日期
 */
Function nextday($date,$days=1,$kind='F',$tp=0,$mode=0){
    switch($tp){
        case 0:
            $yy = substr($date,0,4);
            $mm = substr($date,4,2);
            $dd = substr($date,6,2);
            break;
        case 1:
            $yy = substr($date,0,4);
            $mm = substr($date,5,2);
            $dd = substr($date,8,2);
            break;
    }
    
    switch($kind){
        case "F":
            $monthdays = endmonth(strflz($yy,4).strflz($mm,2));
            $dw = $dd + $days;
            while ($dw>$monthdays){
                    $dw = $dw - $monthdays;
                    $mm = $mm + 1;
                if ($mm>12){
                    $mm = $mm - 12;
                    $yy = $yy + 1;
                }
                $monthdays = endmonth(strflz($yy,4).strflz($mm,2));
            }
            break;
        case "B":
            $dw = $dd - $days;
            while ($dw<=0){
                $mm = $mm - 1;
                if ($mm<1){
                    $mm = 12;
                    $yy = $yy - 1;
                }
                $monthdays = endmonth(strflz($yy,4).strflz($mm,2));
                $dw = $dw + $monthdays;
            }
            break;
    }
    $dd = $dw;
    switch($mode){
        case 0:$st = strflz($yy,4)    .strflz($mm,2)    .strflz($dd,2);break;
        case 1:$st = strflz($yy,4).'/'.strflz($mm,2).'/'.strflz($dd,2);break;
    }
    return $st;
}

Function nextmont($DATE,$MONTHS=1,$KIND='F'){
    $YY = substr($DATE,0,4);
    $MM = substr($DATE,4,2);
    $DD = substr($DATE,6,2);
    if ('' == trim($MONTHS)) $MONTHS = 1;

    for ($i = 0 ; $i < $MONTHS ; $i++) {
        switch($KIND){
            CASE "F":
                $MM++;
                IF ($MM > 12){
                    $MM = $MM - 12;
                    $YY = $YY + 1;
                }
                break;
            CASE "B":
                $MM--;
                IF ($MM < 1){
                    $MM = 12;
                    $YY = $YY - 1;
                }
                break;
        }
    }
    $ST = strflz($YY,4).strflz($MM,2);
    if ($DD != '') {
        $ST.= strflz($DD,2);
    }
    return $ST;
}


Function endmonth($yymm){
   $leapyearsw = 0;
   $yw         = substr($yymm,0,4);
   $dec_num    = round($yw/4,0);
   if ($dec_num == $yw/4){
      $leapyearsw = $leapyearsw + 1;
   }
   $dec_num = round($yw/100,0);
   if ($dec_num = $yw/100){
      $leapyearsw = $leapyearsw - 1;
   }
   $dec_num = round($yw/400,0);
   if ($dec_num = $yw/400){
      $leapyearsw = $leapyearsw + 1;
   }
   $mm = (int)substr($yymm,4,2);
  
   if ($mm==2){
      if ($leapyearsw==1){
         $mdays = 29;
      }else{
         $mdays = 28;
      }
   } else {
      if ($mm==1 or $mm==3 or $mm==5 or $mm==7 or $mm==8 or $mm==10 or $mm==12){
         $mdays = 31;
      } else {
         $mdays = 30;
      }
   }
   return $mdays;
}

/**
* 回傳日期當月的開始日期
*
* @param  string $date 日期,可以為yyyymmdd或yyyymm
*
* @return string $monbegdate 當月第一天日期yyyymmdd
*/
function monbegdate($date){
    $yy = substr($date,0,4);
    $mm = substr($date,4,2);
    
    $monbegdate = date('Ym01',mktime(0,0,0,$mm,1,$yy));
    return $monbegdate;
}

/**
 * 回傳日期當月的結束日期
 *
 * @param  string $date 日期,可以為yyyymmdd或yyyymm
 *
 * @return string $monenddate 當月第一天日期yyyymmdd
 */
function monenddate($date){
    $yy = substr($date,0,4);
    $mm = substr($date,4,2);
    
    $monenddate = date('Ymt',mktime(0,0,0,$mm,1,$yy));
    return $monenddate;
}

/**
 * 判斷是否為假日
 *
 * @param string $w_date 判斷日期yyyymmdd
 * @return boolean
 */
function is_holday($w_date) {
    $yy = substr($w_date,0,4);
    $mm = substr($w_date,4,2);
    $dd = substr($w_date,6,2);
    $ww = date("w", mktime(0,0,0,$mm,$dd,$yy));
    if ($ww==0 || $ww==6){
        return true;
    } else {
        return false;
    }
}

/**
 * 日期格式化
 *
 * @param string $date 判斷日期yyyymmdd
 *                           或yyyy/mm/dd或yyyy-mm-dd
 *                           或yyymmdd
 *                           或yyy/mm/dd或yyy-mm-dd
 * @param int $type  格式化成哪種形式回傳1=yyyymmdd(預設),2=yyyy/mm/dd,3=yyymmdd,4=yyy/mm/dd,5yyyy-mm-dd                             
 * @return string $date 回傳新日期 
 */
function format_date($date,$type=1) {
    $rtn       = '';
    $arr_split = array('/','-');
    $str_split = ''; 
    foreach ($arr_split as $_str){
        if (false!==strpos($date, $_str)){
            $str_split = $_str;
            break;
        }
    }
    if (''!=$str_split){
        $_arr = explode($date,$str_split);
        $yy   = (1000>$_arr[0]?$_arr[0]+1911:$_arr[0]);
        $mm   = $_arr[1];
        $dd   = $_arr[2];
    } else {
        $yy = substr($date,0,4);
        $mm = substr($date,4,2);
        $dd = substr($date,6,2);
    }
    
    switch ($type){
        default:
        case 1:$rtn = date("Ymd"  , mktime(0,0,0,$mm,$dd,$yy));break;
        case 2:$rtn = date("Y/m/d", mktime(0,0,0,$mm,$dd,$yy));break;
        case 5:$rtn = date("Y-m-d", mktime(0,0,0,$mm,$dd,$yy));break;
        case 3:$rtn = ($yy-1911).$mm.$dd;break;
        case 4:$rtn = ($yy-1911).'/'.$mm.'/'.$dd;break;
    }
    return $rtn;
}


/* Function: 格式化輸出字串, 左邊補零 
    @param $num  : 轉換的數字
    @param $digit: 輸出格式的長度
    @param $chr  : 取代的字元 ,預設為 0
    
    example: echo strflz(93,3);
    結果為 093
    */
function strflz($num,$digit,$chr=0){
    return sprintf("%'".$chr.$digit."s",$num);    
}
/* Function: 格式化輸出字串,回傳顏色<font>標籤
   @param  string $str   要轉換的字串
   @param  string $style 要附加的style
   @param  string $title 要顯示的提示訊息
   @return string $rtn   加上font標籤以及顏色的字串
 */
function font_tag($str, $style='', $title=''){
    return '<font title="'.$title.'" style="'.$style.'" >'.$str.'</font>';
}

/* Function: 格式化日期的sup標籤
   @param  string $mmdd  要顯示的月日
   @return string $rtn   上標月日
 */
function mmdd_sup_tag($mmdd){
    return '<sup style="font-size:9px">'.$mmdd.'</sup>';    
}

/* Function: 格式化底線功能
   @param  string $str  要加底線的字串
   @return string $rtn  加上底線的字串
 */
function u_tag($str){
    return '<u>'.$str.'</u>';    
}

/* Function: 回傳title共同樣式字串
   @param  string $str   要轉換的字串
 */
function title_tag($str){
    return '<div style="background-color:#09508F;font-size:36px;text-align: center;color:#DDDDDD;">'.$str.'</div>';    

}
/**
 * ehco出字串的function
 * 
 * @param array $str 要被放在陣列前的註解字串
 *
 * @return void
 *
 */
function s_str($show,$str=''){
    $show_str = 'str';
    if (''!=$str) $show_str = $str;
    $show_str .= '='.$show.'<br />';
    echo $show_str;
}
/**
 * ehco出陣列的資料的function
 * 
 * @param array $arr 要被顯示的陣列
 * @param array $str 要被放在陣列前的註解字串
 * 
 * @return void 
 * 
 */
function s_ary($arr,$str=''){
    if ('' != $str) echo '<h2>'.$str.'</h2>';  
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
}

/* Function: 判斷大小要回傳什麼顏色的字回來
   @param  string $num   要判斷的數字
   @param  string $title 要轉換的顏色
   @return string $rtn   大於0回傳紅色,小於0回傳綠色,等於0回傳黑色
 */
function cal_num_color($num,$style='',$title=''){
    if ($num < 0){
        return font_tag(abs($num),$style.'color:green;',$title);
    } else if ($num > 0){
        return font_tag(abs($num),$style.'color:red;'  ,$title);
    } else {
        return font_tag(abs($num),$style.'color:black;',$title);
    }
}

/**
 * 去除td標籤
 *
 * @param  string $str 要被去除td標籤的字串
 * @return string $rtn 去除後的字串
 */
function leave_tag($str,$tag){
    $chk_str = explode('</'.((false !== strpos($str,'</'.strtolower($tag).'>')) ? strtolower($tag) : strtoupper($tag)).'>', $str);
    
    if (false !== strpos($chk_str[0],'>')){
        $_arr = explode('>', $chk_str[0]);
        return trim($_arr[count($_arr)-1]);
    } else {
        return trim($chk_str[0]);
    }
}

/**
 * 取得券商資訊
 *
 * @param  array  $arr     從html拆解出來的陣列
 * @param  int    $col_num 每筆要抓的欄位的數量
 * @param  string $now_ym  帶入資料年月,格式yyyymm
 *
 * @return array $rtn 要新增成txt檔的陣列
 */
function get_tw_info($arr,$col_num,$now_ym=''){
    $rtn     = array();
    $now_yy  = substr($now_ym,0,4);
    $now_mm  = substr($now_ym,4,2);
    $this_ym = ($now_yy-1911).'/'.$now_mm;
    $cal_col_num = 0;
    
    foreach ($arr as $_str){
        if (''==trim($_str)) continue;
        
        $_str1 = ''; 
        // 取得公司的代號
        if ($cal_col_num > 0){            
            $_str1 = leave_tag($_str,'td');
            $_str1 = str_replace(',','',$_str1);
            $rtn[$_date][$cal_col_num] = $_str1;
            $cal_col_num++;
            
            if ($cal_col_num > ($col_num-1)){
                $cal_col_num = 0;
            }
        } else if ($cal_col_num==0 && false!==strpos($_str,'<td>'.$this_ym)){
            $_str1 = leave_tag($_str,'td');
            $_arr  = explode('/',$_str1);
            $_date = ($_arr[0]+1911).'/'.$_arr[1].'/'.$_arr[2];
            
            $rtn[$_date][$cal_col_num] = $_date;
            $cal_col_num = 1;
        }
    }
    return $rtn;
}

/**
 * 取得券商資訊
 *
 * @param  array  $arr     從html拆解出來的陣列
 * @param  string $now_ym  帶入資料年月日,格式yyyymmdd
 *
 * @return array $rtn 要新增成txt檔的陣列
 */
function get_tw_info_d($arr){
    $rtn     = array();
    $cal_col_num = 0;
    
    $chk_tr_b = false;
    $chk_tr_e = false;
    
    $row_str = '';
    foreach ($arr as $_str){
        if (''==trim($_str)) continue;
        
        if (false!==strpos($_str,'<tbody>'))                  $chk_tbody = 'Y';
        if (false!==strpos($_str,'</tbody>'))                 $chk_tbody = 'N';
        if ('Y'==$chk_tbody && false!==strpos($_str,'<tr>'))  $chk_tr    = 'Y';
        if ('Y'==$chk_tbody && false!==strpos($_str,'</tr>')) $chk_tr    = 'N';

        // 如果已經到檔案結尾的話，就跳出了，後面沒有資訊要存
        if ('N' == $chk_tbody){
            break;
        }
        
        $_str1 = '';
        // 取得公司的代號
        if ('Y' == $chk_tr && false!==strpos($_str,'<td>')){
            $_str1 = leave_tag($_str,'td');
            $_str1 = trim(str_replace(',','',$_str1));
            if (''==$row_str){
                $row_str = $_str1;
            } else {
                $row_str = $row_str.','.$_str1;
            }
        } else if ('N' == $chk_tr && false!==strpos($_str,'</tr>')){
            $rtn[$cal_col_num] = $row_str;
            $row_str = '';
            $cal_col_num++;
        }
    }
    return $rtn;
}

?>