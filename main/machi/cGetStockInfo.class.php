<?php
// 載入需要的函式
if (!function_exists('nextday')) {
    require_once dirname(dirname(__FILE__)) . './stock.inc.php';
}
require_once dirname(__FILE__) . './MorInfo.php';
class cGetStockInfo
{
    private $nPreDayPer   = 0.8;        //要往前的天數要計算的百分比
    private $nBuyPercent  = 0.15;       //當這檔股票, 買進超過當天總買進股票的百分比, 預設為15%
    private $nSelPercent  = 0.03;       //當這檔股票, 賣出超過當天總買進股票的百分比, 預設為3%
    private $sYY          = '';         //目前的年度(yyyy)
    private $sMM          = '';         //目前的月份(mm)
    private $sDD          = '';         //目前的日期(dd)
    private $sToday       = '';         //當天的日期(yyyymmmdd)
    private $sOutStr      = '';         //外資的統一字眼
    private $sTouStr      = '';         //投信的統一字眼
    private $sRootPath    = '';         //主目錄路徑
    private $sDataPath    = '';         //資料的路徑
    private $sLastWkdate  = '';         //最後實際資料的日期(yyyymmdd)
    private $sFirstWkdate = '';         //最早實際抓資料的日期(yyyymmdd)
    private $aCom         = [];    //外資的代號跟中文名稱陣列
    private $aComInfo     = [];    //觀察中的外資的買賣量資料
    private $aComLong     = [];    //長線的外資代號
    private $aMorDays     = [1];   //抓外資買賣量資料的天數,預設1天
    private $aComHold     = [];    //外資可能的持股量
    private $aDayMost     = [];    //記錄當天有大量買進的股票
    private $aComShort    = [];    //短線的外資代號
    private $aTopStock    = [];    //外資跟投信是否有多天買入股票
    private $aLowStock    = [];    //外資跟投信是否有多天賣出股票
    private $aAllStock    = [];    //所有股票的代號以及資訊
    private $aArrWkdate   = [];    //實際有資料的日期的陣列
    private $aKeyinData   = [];    //持有的股票陣列
    private $aTrackData   = [];    //追蹤中的股票陣列
    private $aOutPerData  = [];    //所有外資的持股比例
    private $aOutTouStock = [];    //外資跟投信當天有大量買入或賣出的股票
    private $aSchDate     = [
        'last_date',  //查詢的最後日期
        'first_date', //查詢的開始日期
        'preday',     //查詢的最後日期往前多少天
        'date_range'  //陣列裡面存日期,只查詢陣列列出的日期
    ];
    //所有的股票資訊計算後的代號
    private $aAllShowType = [
        'keyin_data', //持有的股票
        'track_data', //追蹤中的股票
        'day_most', //外資當天買進
        'out_tou_stock_top', //投信跟外資大量買進
        'out_tou_stock_low', //投信跟外資大量賣出
        'top_stock_out', //外資多天大量買進
        'top_stock_tou', //投信多天大量買進
        'low_stock_out', //外資多天大量賣出
        'low_stock_tou', //投信多天大量賣出
    ];

    //所有買賣量的級距
    private $aLvType = [
        1  => '-20001~',
        2  => '-20000~-10001',
        3  => '-10000~-5001',
        4  => '-5000~-2001',
        5  => '-2000~-1001',
        6  => '-1000~-501',
        7  => '-500~-101',
        8  => '-100~-1',
        9  => '0~99',
        10 => '100~499',
        11 => '500~999',
        12 => '1000~1999',
        13 => '2000~4999',
        14 => '5000~9999',
        15 => '10000~19999',
        16 => '20000~',
    ];
    //存股股票
    private $aSaveStock = [
        2886 => '兆豐金',
        2892 => '第一金',
        5880 => '合庫金',
        2880 => '華南金',
        2412 => '中華電',
        2412 => '中華電',
        3045 => '台灣大',
        4904 => '遠傳　',
        5321 => '友銓　',
    ];
    //所有買賣量的級距
    private $aBosType  = ['buy', 'sel', 'bms'];
    //已經運算過的日期
    private $aSaveWkDate = [];
    //不顯示出來的股票代號,有抓不到外資持股量,也有金融業的股票
    /* 
     * 1101B-台泥乙特
     * 2801-彰銀
     * 2812-台中銀
     * 2834-臺企銀
     * 2836-高雄銀
     * 2838-聯邦銀
     * 2838A-聯邦銀甲特
     * 2880-華南金
     * 2881-富邦金
     * 2881A-富邦特
     * 2881B-富邦金乙特
     * 2882-國泰金
     * 2882A-國泰特
     * 2882B-國泰金乙特
     * 2883-開發金
     * 2884-玉山金
     * 2885-元大金
     * 2886-兆豐金
     * 2887-台新金
     * 2887E-台新戊特
     * 2887F-台新戊特二
     * 2888-新光金
     * 2889-國票金
     * 2890-永豐金
     * 2891-中信金
     * 2891B-中信金乙特
     * 2891C-中信金丙特
     * 2892-第一金
     * 5876-上海商銀
     * 5880-合庫金
     */
    private $aNoShowSno = ['1101B', '2801', '2812', '2834', '2836', '2838', '2880', '2881', '2881A', '2881B', '2882', '2882A', '2882B', '2883', '2838A', '2884', '2885', '2886', '2887', '2887E', '2887F', '2888', '2889', '2890', '2891', '2891B', '2891C', '2892', '5876', '5880'];

    public function __construct()
    {
        $this->sRootPath = dirname(dirname(__FILE__)) . '/';
        $this->sDataPath = $this->sRootPath . './../data/';
        $this->aCom      = MorInfo::getMorInfo();
        $this->aComLong  = MorInfo::getComLong();
        $this->aComShort = MorInfo::getComShort();
        $this->sYY       = date('Y');
        $this->sMM       = date('m');
        $this->sDD       = date('d');
        $this->sToday    = $this->sYY . $this->sMM . $this->sDD;
        $this->sOutStr   = '<span style="color:#109BD7;font-weight: bold;">外資</span>';
        $this->sTouStr   = '<span style="color:#CE9503;font-weight: bold;">投信</span>';
    }
    public function mSetPreDayPer($val)
    {
        $this->nPreDayPer = $val;
    }

    public function mGetPreDayPer()
    {
        $chk_pre_day_per = $this->nPreDayPer;
        if ('' == $chk_pre_day_per || !is_numeric($chk_pre_day_per)) {
            $this->mSetPreDayPer(0.8);
        }
        return $this->nPreDayPer;
    }

    public function mSetBuyPercent($val)
    {
        $this->nBuyPercent = $val;
    }

    public function mGetBuyPercent()
    {
        $chk_buy_percent = $this->nBuyPercent;
        if ('' == $chk_buy_percent || !is_numeric($chk_buy_percent)) {
            $this->mSetBuyPercent(0.15);
        }
        return $this->nBuyPercent;
    }

    public function mSetSelPercent($val)
    {
        $this->nSelPercent = $val;
    }

    public function mGetSelPercent()
    {
        $chk_sel_percent = $this->nSelPercent;
        if ('' == $chk_sel_percent || !is_numeric($chk_sel_percent)) {
            $this->mSetSelPercent(0.03);
        }
        return $this->nSelPercent;
    }

    public function mSetMorDays($arr)
    {
        $this->aMorDays = $arr;
    }

    public function mGetMorDays()
    {
        $chk_mor_days = $this->aMorDays;
        if (empty($chk_mor_days) || !is_array($chk_mor_days)) {
            $this->mSetMorDays(array(1));
        }
        return $this->aMorDays;
    }

    public function mSetScgDate($arr)
    {
        $this->aSchDate = (empty($arr) || !is_array($arr)) ? [] : $arr;
    }

    public function mGetSchDate()
    {
        return $this->aSchDate;
    }

    public function mGetCom()
    {
        return $this->aCom;
    }

    public function mGetComLong()
    {
        return $this->aComLong;
    }

    public function mGetComShort()
    {
        return $this->aComShort;
    }
    public function mGetFirstWkdate()
    {
        return $this->sFirstWkdate;
    }
    public function mGetLastWkdate()
    {
        return $this->sLastWkdate;
    }

    public function mGetArrWkdate()
    {
        return $this->aArrWkdate;
    }

    public function mGetOutStr()
    {
        return $this->sOutStr;
    }

    public function mGetTouStr()
    {
        return $this->sTouStr;
    }

    public function mSetKeyinData($arr)
    {
        $this->aKeyinData = $arr;
    }
    public function mGetKeyinData()
    {
        return (!is_array($this->aKeyinData)) ? [] : $this->aKeyinData;
    }
    public function mSetTrackData($arr)
    {
        $this->aTrackData = $arr;
    }

    public function mGetTrackData()
    {
        return (!is_array($this->aTrackData)) ? [] : $this->aTrackData;
    }

    public function mGetAllShowType()
    {
        return $this->aAllShowType;
    }

    public function mGetLvType()
    {
        return $this->aLvType;
    }

    public function mGetSaveStock()
    {
        return $this->aSaveStock;
    }

    /**
     * 取得從網站上抓取的股票的資料
     *
     * @return array $rtn['all_stock']     所有股票的代號以及資訊
     *               $rtn['out_per_data']  所有外資的持股比例
     *               $rtn['com_info']      觀察中的外資的買賣量資料
     *               $rtn['top_stock']     外資跟投信是否有多天買入股票
     *               $rtn['low_stock']     外資跟投信是否有多天賣出股票
     *               $rtn['com_hold']      外資可能的持股量
     *               $rtn['out_tou_stock'] 外資跟投信當天有大量買入或賣出的股票
     *               $rtn['first_wkdate']  最早的工作天的日期
     *               $rtn['last_wkdate']   最晚的工作天的日期
     */
    public function mGetStockInfo()
    {
        $rtn        = [];
        /**********************************************************
         * 處理三大法人資料
         **********************************************************/
        $top_stock  = [];
        $arr_allday = [];
        $all_stock  = [];
        $sch_date         = $this->mGetSchDate();
        $pre_day          = $sch_date['preday'];
        $pre_day_per      = $this->mGetPreDayPer();
        $sel_sta_days_per = floor($pre_day * $pre_day_per);
        $sel_buy_percent  = $this->mGetBuyPercent();
        $sel_sel_percent  = $this->mGetSelPercent();

        //取得開始日期與結束日期        
        $_arr = $this->mCalStockDates($sch_date);
        $first_wkdate = $_arr['first_wkdate'];
        $last_wkdate  = $_arr['last_wkdate'];
        $this->sFirstWkdate = $first_wkdate;
        $this->sLastWkdate  = $last_wkdate;

        //外資持股比例資料
        $_arr = $this->mGetOutPercent($sch_date);
        $all_stock    = $_arr['info'];
        $out_per_data = $_arr['hold'];

        //抓外資投信幾天內的買賣量,以及每天的總買賣量
        $tb_bos = $this->mGetThreeBigBOS($sch_date);
        foreach ($tb_bos as $_sno => $_data) {
            foreach ($_data as $_date => $_data1) {
                foreach ($_data1 as $_tb_str => $_data2) {
                    if ('zen' == $_tb_str) continue;
                    $_buy = $_data2['buy'];
                    $_sel = $_data2['sel'];
                    $arr_allday[$_date][$_tb_str]['buy'] += $_buy; //當天總買入股票量
                    $arr_allday[$_date][$_tb_str]['sel'] += $_sel; //當天總賣出股票量
                    $arr_allday[$_date][$_tb_str]['bps'] += $_buy + $_sel; //當天買進+賣出股票量
                }
            }
        }

        //計算當天買入的股票數,佔當日的百分比
        $out_tou_stock = array();
        $out_tou_stock['top']['down_stock'] = array();
        $out_tou_stock['low']['down_stock'] = array();
        foreach ($tb_bos as $_sno => $_data) {
            foreach ($_data as $_date => $_data1) {
                foreach ($_data1 as $_tb_str => $_data2) {
                    if ('zen' == $_tb_str) continue;
                    if (empty($top_stock[$_tb_str]['date'][$_date])) $top_stock[$_tb_str]['date'][$_date] = array();
                    if (empty($low_stock[$_tb_str]['date'][$_date])) $low_stock[$_tb_str]['date'][$_date] = array();
                    if (empty($top_stock[$_tb_str]['down_stock']))   $top_stock[$_tb_str]['down_stock']   = array();
                    if (empty($low_stock[$_tb_str]['down_stock']))   $low_stock[$_tb_str]['down_stock']   = array();

                    $_buy = $_data2['buy'];
                    $_sel = $_data2['sel'];
                    $_bms = $_data2['bms'];

                    $_buy_sum = $arr_allday[$_date][$_tb_str]['buy'];
                    $_sel_sum = $arr_allday[$_date][$_tb_str]['sel'];
                    $_bps_sum = $arr_allday[$_date][$_tb_str]['bps'];

                    $_all_buy_per = (0 == (int)$_buy_sum) ? 0 : round($_buy / $_buy_sum * 100, 2);
                    $_all_sel_per = (0 == (int)$_sel_sum) ? 0 : round($_sel / $_sel_sum * 100, 2);
                    $_all_bms_per = (0 == (int)$_bps_sum) ? 0 : round($_bms / $_bps_sum * 100, 2);

                    //這檔股票當天買進比例,超過設定的百分比
                    if ($_all_bms_per > $sel_buy_percent) {
                        $top_stock[$_tb_str]['date'][$_date][] = $_sno;
                        $top_stock[$_tb_str]['count'][$_sno]++;
                        //多天大量買進的股票
                        if ($top_stock[$_tb_str]['count'][$_sno] >= $sel_sta_days_per && !in_array($_sno, $top_stock[$_tb_str]['down_stock'])) {
                            $top_stock[$_tb_str]['down_stock'][] = $_sno;
                        }
                    }
                    //這檔股票當天賣出比例,超過設定的百分比
                    if (0 > $_all_bms_per && $sel_sel_percent < abs($_all_bms_per)) {
                        $low_stock[$_tb_str]['date'][$_date][] = $_sno;
                        $low_stock[$_tb_str]['count'][$_sno]++;
                        //多天大量賣出的股票
                        if ($low_stock[$_tb_str]['count'][$_sno] >= $sel_sta_days_per && !in_array($_sno, $low_stock[$_tb_str]['down_stock'])) {
                            $low_stock[$_tb_str]['down_stock'][] = $_sno;
                        }
                    }
                }
                if (empty($out_tou_stock['top']['date'][$_date])) $out_tou_stock['top']['date'][$_date]   = array();
                if (empty($out_tou_stock['low']['date'][$_date])) $out_tou_stock['low']['date'][$_date]   = array();

                //同一檔股票,外資跟投信都有大量買入的,存在$out_tou_stock
                $chk_out_top = (is_array($top_stock['out']['date'][$_date]) && in_array($_sno, $top_stock['out']['date'][$_date]));
                $chk_tou_top = (is_array($top_stock['tou']['date'][$_date]) && in_array($_sno, $top_stock['tou']['date'][$_date]));
                if ($chk_out_top && $chk_tou_top) {
                    $out_tou_stock['top']['date'][$_date][] = $_sno;
                    $out_tou_stock['top']['count'][$_sno]++;
                    //紀錄有多少天大量買進的股票
                    if ($out_tou_stock['top']['count'][$_sno] >= $sel_sta_days_per && !in_array($_sno, $out_tou_stock['top']['down_stock'])) {
                        $out_tou_stock['top']['down_stock'][] = $_sno;
                    }
                }
                // 同一檔股票,外資跟投信都有大量賣出,存在$out_tou_stock
                $chk_out_low = (is_array($low_stock['out']['date'][$_date]) && in_array($_sno, $low_stock['out']['date'][$_date]));
                $chk_tou_low = (is_array($low_stock['tou']['date'][$_date]) && in_array($_sno, $low_stock['tou']['date'][$_date]));
                if ($chk_out_low && $chk_tou_low) {
                    $out_tou_stock['low']['date'][$_date][] = $_sno;
                    $out_tou_stock['low']['count'][$_sno]++;
                    //紀錄有多少天大量賣出的股票
                    if ($out_tou_stock['low']['count'][$_sno] >= $sel_sta_days_per && !in_array($_sno, $out_tou_stock['low']['down_stock'])) {
                        $out_tou_stock['low']['down_stock'][] = $_sno;
                    }
                }
            }
        }

        $day_most = array();  //目前以摩根士丹利為基準,看那些是當天有大買的資料
        //取得連續3天有買入的股票代號
        $day_most = $this->mGetComComboData($last_wkdate, 1);
        //抓摩根買賣量
        $com_info = array();  //記錄所有摩根的買賣量
        $com_info = $this->mGetMorData($sch_date);

        //取得外資的可能的股票持有量
        $com_hold = array();
        $com_hold = $this->mGetComHoldData($sch_date);

        $this->aAllStock    = $all_stock;
        $this->aOutPerData  = $out_per_data;
        $this->aComInfo     = $com_info;
        $this->aTopStock    = $top_stock;
        $this->aLowStock    = $low_stock;
        $this->aComHold     = $com_hold;
        $this->aOutTouStock = $out_tou_stock;
        $this->aDayMost     = $day_most;
    }

    /**
     * 計算開始日期與結束日期以及中間的日期
     *
     * @param array  $arr_date['last_date']  查詢的最後日期(必填,為空則為當天)
     *               $arr_date['first_date'] 查詢的最早日期(預設為空)
     *               $arr_date['preday']     要往前查詢的天數(預設0天)
     *               $arr_date['date_range'] 查詢的最早日期
     *
     * @return array $rtn['first_wkdate'] 最早有股票資料的日期
     *               $rtn['last_wkdate']  最晚有股票資料的日期
     *               $rtn['arr_wkdate']   最早與最晚之間所有有資料的日期(日期從早排到晚) 
     */
    public function mCalStockDates($arr_date)
    {
        $rtn        = [];
        $_cnt       = 1;
        $arr_wkdate = [];
        $date_range = $arr_date['date_range'] ?? [];

        //如果有代入日期區間則優先使用日期區間
        if (!empty($date_range)) {
            $chg_wkdate = false;
            rsort($date_range);
            foreach ($date_range as $_date) {
                if (in_array($_date, $this->aSaveWkDate) || file_exists($this->sDataPath . "./stock/threebig_bos/txt_" . $_date . ".txt")) {
                    $last_wkdate  = $last_wkdate ?? $_date;
                    $first_wkdate = $_date;
                    $arr_wkdate[] = $_date;
                    if (!in_array($_date, $this->aSaveWkDate)) {
                        $this->aSaveWkDate[] = $_date;
                        $chg_wkdate = true;
                    }
                }
            }
            //確認有新的工作日期加入,把他加到變數裡面
            if ($chg_wkdate) {
                sort($this->aSaveWkDate);
            }
        } else {
            $chg_wkdate = false;
            //從最後一個工作日開始往前找
            for ($_date = ($arr_date['last_date'] ?? $this->sToday); ('' != $arr_date['first_date'] ? $_date >= $arr_date['first_date'] : $_cnt <= ($arr_date['preday'] ?? 1)); $_date = $this->mCalNextWkDate($_date, -1)) {
                if (in_array($_date, $this->aSaveWkDate) || file_exists($this->sDataPath . "./stock/threebig_bos/txt_" . $_date . ".txt")) {
                    $last_wkdate  = $last_wkdate ?? $_date;
                    $first_wkdate = $_date;
                    $arr_wkdate[] = $_date;
                    $_cnt++;
                    if (!in_array($_date, $this->aSaveWkDate)) {
                        $this->aSaveWkDate[] = $_date;
                        $chg_wkdate = true;
                    }
                }
            }
            //確認有新的工作日期加入,把他加到變數裡面
            if ($chg_wkdate) {
                sort($this->aSaveWkDate);
            }
        }
        sort($arr_wkdate);
        $rtn['first_wkdate'] = $first_wkdate;
        $rtn['last_wkdate']  = $last_wkdate;
        $rtn['arr_wkdate']   = $arr_wkdate;
        return $rtn;
    }
    /**
     * 計算往前或往後的工作天數
     *
     * @param  string $date 任意日期
     * @param  string $day  正數是往後的日期,負數是往前的日期,0則回傳今天日期 
     *
     * @return string $rtn  下一個工作天(有資料的日期)
     */
    public function mCalNextWkDate($date, $day = 1)
    {
        $rtn = $this->sToday;
        $data_path = $this->sDataPath;
        if ('' == $date || 0 == $day) return $rtn;

        $_cnt = 1;
        $_str = ($day > 0 ? 'F' : 'B');
        $new_date   = nextday($date, 1, $_str);
        $chg_wkdate = false;
        for ($_date = $new_date; $_cnt <= abs($day); $_date = nextday($_date, 1, $_str)) {
            $_file_path  = $data_path . "./stock/threebig_bos/txt_" . $_date . ".txt";
            $_chk_wkdate = in_array($_date, $this->aSaveWkDate);
            if ($_chk_wkdate || file_exists($_file_path)) {
                $rtn = $_date;
                $_cnt++;
                if (!$_chk_wkdate) {
                    $this->aSaveWkDate[] = $_date;
                    $chg_wkdate = true;
                }
            }
        }
        //確認有新的工作日期加入,把他加到變數裡面
        if ($chg_wkdate) sort($this->aSaveWkDate);

        return $rtn;
    }

    /**
     * Function: 判斷這隻股票有沒有多少天內大量買入或賣出的情形
     * @param  string $sno            要判斷的股票代號
     * 
     * @return string $rtn_str        回傳這個股票有多少種狀態的字串
     */
    public function mUpDownStock($sno)
    {
        $rtn_str = '';
        // 外資跟投信當天有沒有大量買入 
        if (in_array($sno, $this->aOutTouStock['top']['date'][$this->mGetLastWkdate()])) {
            $rtn_str .= $this->mGetOutStr() . $this->mGetTouStr() . font_tag('當天大量買入', 'color:red;') . '<br />';
        }
        // 外資跟投信當天有沒有大量賣出
        if (in_array($sno, $this->aOutTouStock['low']['date'][$this->mGetLastWkdate()])) {
            $rtn_str .= $this->mGetOutStr() . $this->mGetTouStr() . font_tag('當天大量賣出', 'color:green;') . '<br />';
        }
        // 外資有沒有大量買入
        if (in_array($sno, $this->aTopStock['out']['down_stock'])) {
            $rtn_str .= $this->mGetOutStr() . font_tag('多天大量買入', 'color:red;') . '<br />';
        }
        // 外資有沒有大量賣出
        if (in_array($sno, $this->aLowStock['out']['down_stock'])) {
            $rtn_str .= $this->mGetOutStr() . font_tag('多天大量賣出', 'color:green;') . '<br />';
        }
        // 投信有沒有大量買入
        if (in_array($sno, $this->aTopStock['tou']['down_stock'])) {
            $rtn_str .= $this->mGetTouStr() . font_tag('多天大量買入', 'color:red;') . '<br />';
        }
        // 投信有沒有大量賣出
        if (in_array($sno, $this->aLowStock['tou']['down_stock'])) {
            $rtn_str .= $this->mGetTouStr() . font_tag('多天大量賣出', 'color:green;') . '<br />';
        }
        return substr($rtn_str, 0, strlen($rtn_str) - 6);
    }


    /** 
     * 取得外資的買賣量
     *
     * @param  int    $_sno    股票代號
     * 
     * @return string $rtn_str 外資的買賣量字串
     */
    public function mGetOutAmount($sno)
    {
        $rtn_str    = '';
        $_long_str  = '';
        $_short_str = '';
        $_long_num  = 0;
        $_short_num = 0;
        $_first_long_num  = 0;
        $_first_short_num = 0;

        //依照每個外資抓出來
        foreach ($this->mGetCom() as $com_name => $_data1) {
            if (!is_array($this->aComInfo[$sno][$com_name])) {
                continue;
            }
            $_com_str = '';
            $_total   = 0;
            // 抓出外資每天的買賣量
            foreach ($this->aComInfo[$sno][$com_name] as $_date => $_data2) {
                $_buy    = $_data2[$this->aMorDays[0]]['buy'];
                $_sel    = $_data2[$this->aMorDays[0]]['sel'];
                $_bms    = $_data2[$this->aMorDays[0]]['bms'];
                $_total += ($_buy - $_sel);
                if (!empty($_buy) || !empty($_sel) || !empty($_bms)) {
                    $_str = '';
                    $_str = font_tag($_buy, 'color:red;') . ',' . font_tag($_sel, 'color:green;') . '(' . ($_bms > 0 ? font_tag($_bms, 'color:red;') : font_tag(abs($_bms), 'color:green;')) . ')';
                    $_str = ($this->mGetLastWkdate() == $_date) ? u_tag($_str) : $_str;
                    $_str = mmdd_sup_tag(substr($_date, 4)) . $_str . '&nbsp;';
                    $_com_str .= $_str;
                }
            }
            //計算每個外資的買賣量
            $_com_str = $_data1['name'] . '：' . $_com_str . ',共' . cal_num_color($_total, 'font-weight:bold;');
            if ('' != $this->aComHold[$sno][$com_name][$this->mGetLastWkdate()]['bos']) {
                $_com_str = $_com_str . ',持有量=' . cal_num_color($this->aComHold[$sno][$com_name][$this->mGetLastWkdate()]['bos'], 'font-weight:bold;');
            }

            //長線的就放長線那邊,短線的就放短線
            if (in_array($com_name, $this->aComLong)) {
                $_long_str       .= $_com_str . '<br />';
                $_long_num       += $_total;
                $_first_long_num += ($_buy - $_sel);
            } else {
                $_short_str       .= $_com_str . '<br />';
                $_short_num       += $_total;
                $_first_short_num += ($_buy - $_sel);
            }
        }

        if ($_long_str  != '') {
            // 計算全部的長線的買賣量
            $rtn_str .= '長線:' . ($_long_num != 0 ? '區間=' . cal_num_color($_long_num, 'font-weight:bold;') : '');
            $rtn_str .= ' ' . ($_first_long_num != 0 ? '當天=' . cal_num_color($_first_long_num, 'font-weight:bold;') : '') . '<br />' . $_long_str;
        }
        if ($_short_str != '') {
            // 計算全部的短線的買賣量
            $rtn_str .= '短線:' . ($_short_num != 0 ? '區間=' . cal_num_color($_short_num, 'font-weight:bold;') : '');
            $rtn_str .= ' ' . ($_first_short_num != 0 ? '當天=' . cal_num_color($_first_short_num, 'font-weight:bold;') : '' ) . '<br />' . $_short_str;
        }

        return $rtn_str ?: '沒有外資買賣量';
    }

    /** 
     * 取得百分比字串
     * 
     * @param  int    $_sno    股票代號
     * 
     * @return string $rtn_str 百分比字串
     */
    public function mGetOutPercentStr($sno)
    {
        $tmp_html      = '';
        $tmp_per       = 0;
        $chk_cnt       = 0;
        $first_per     = 0;
        $final_per     = 0;
        $final_per_per = 0;
        $bigest        = 0;
        $smallest      = 0;
        if (!is_array($this->aOutPerData[$sno])) {
            return '沒有外資持股量';
        }
        //取得最後一天持股量
        foreach ($this->aOutPerData[$sno] as $_date => $_data) {
            $final_per = $_data['hold_percent'];
        }
        foreach ($this->aOutPerData[$sno] as $_date => $_data) {
            $tmp_color = 'color:black;';
            if ($chk_cnt == 0) {
                $tmp_color = 'color:black;';
                $first_per = $_data['hold_percent'];
            } else if ($_data['hold_percent'] > $tmp_per) {
                $tmp_color = 'color:red;';
                $bigest    = $_data['hold_percent'];
            } else if ($_data['hold_percent'] < $tmp_per) {
                $tmp_color = 'color:green;';
                $smallest  = $_data['hold_percent'];
            }

            $tmp_per   = $_data['hold_percent'];
            $tmp_str   = font_tag($_data['hold_percent'], $tmp_color);
            $tmp_str   = ($this->mGetLastWkdate() == $_date) ? u_tag($tmp_str) : $tmp_str;
            $tmp_str   = mmdd_sup_tag(substr($_date, 4, strlen($_date))) . $tmp_str;
            $tmp_html .= '→' . $tmp_str;
            if ($this->mGetLastWkdate() != $_date) {
                $final_per_per = $_data['hold_percent'];
                $tmp_html .= '[' . bcsub($final_per, $_data['hold_percent'], 2) . ']';
            }
            $chk_cnt++;
        }

        $smallest     = ($smallest == 0) ? $first_per : $smallest;
        $chg_interval = $final_per - $final_per_per;
        $style = 'color:green;';
        if ($chg_interval >= 0.7) {
            $style = 'color:red;font-weight:bold;border:2px solid #F00;';
        } else if ($chg_interval > 0) {
            $style = 'color:red;';
        }
        // 最大的數字畫重點
        $tmp_html = ($bigest > 0) ? str_replace($bigest, '<span style="font-weight:bold">' . $bigest  . '</span>', $tmp_html) : $tmp_html;
        // 最小的數字畫重點
        $tmp_html = ($smallest > 0) ? str_replace($smallest, '<span style="font-weight:bold">' . $smallest . '</span>', $tmp_html) : $tmp_html;

        return '外資持股=' . substr($tmp_html, 3) . '<br />' . '變化=' . font_tag($chg_interval, $style, '') . ', 極距=' . ($bigest - $smallest);
    }

    /**
     * 取得百分比字串
     *
     * @param  array  $arr_type['keyin_data']        持有的股票
     *                $arr_type['track_data']        追蹤中的股票
     *                $arr_type['day_most']          外資大買量
     *                $arr_type['out_tou_stock_top'] 當日投信跟外資大量買進
     *                $arr_type['out_tou_stock_low'] 當日投信跟外資大量賣出
     *                $arr_type['top_stock_out']     外資多天買入
     *                $arr_type['top_stock_tou']     投信多天買入
     *                $arr_type['low_stock_out']     外資多天賣出
     *                $arr_type['low_stock_tou']     投信多天賣出
     *
     * @return string $rtn_str 資料整理後的結果寫入<table>回傳
     */
    public function mShowStockInfo($arr_type)
    {
        $rtn        = '';
        $sch_date   = $this->mGetSchDate();
        $count_days = floor($sch_date['preday'] * $this->mGetPreDayPer());

        $this->aAllShowType['keyin_data']        = title_tag('持有的股票');
        $this->aAllShowType['track_data']        = title_tag('追蹤中的股票');
        $this->aAllShowType['day_most']          = title_tag($this->mGetLastWkdate() . $this->mGetOutStr() . '大量買進');;
        $this->aAllShowType['out_tou_stock_top'] = title_tag($this->mGetLastWkdate() . $this->mGetTouStr() . '跟' . $this->mGetOutStr() . '大量買進');
        $this->aAllShowType['out_tou_stock_low'] = title_tag($this->mGetLastWkdate() . $this->mGetTouStr() . '跟' . $this->mGetOutStr() . '大量賣出');
        $this->aAllShowType['top_stock_out']     = title_tag($sch_date['preday'] . '天內' . $this->mGetOutStr() . '有' . $count_days . '天大量買入');
        $this->aAllShowType['top_stock_tou']     = title_tag($sch_date['preday'] . '天內' . $this->mGetTouStr() . '有' . $count_days . '天大量買入');
        $this->aAllShowType['low_stock_out']     = title_tag($sch_date['preday'] . '天內' . $this->mGetOutStr() . '有' . $count_days . '天大量賣出');
        $this->aAllShowType['low_stock_tou']     = title_tag($sch_date['preday'] . '天內' . $this->mGetTouStr() . '有' . $count_days . '天大量賣出');
        foreach ($this->mGetAllShowType() as $_key) {
            if (true != $arr_type[$_key]) {
                continue;
            }
            switch ($_key) {
                case 'keyin_data':
                    $_datas = $this->mGetKeyinData();
                    $_title = title_tag('持有的股票');
                    break;
                case 'track_data':
                    $_datas = $this->mGetTrackData();
                    $_title = title_tag('追蹤中的股票');
                    break;
                case 'day_most':
                    $_datas = $this->aDayMost;
                    $_title = title_tag($this->mGetLastWkdate() . $this->mGetOutStr() . '當天買進');
                    break;
                case 'out_tou_stock_top':
                    $_datas = $this->aOutTouStock['top']['date'][$this->mGetLastWkdate()];
                    $_title = title_tag($this->mGetLastWkdate() . $this->mGetTouStr() . '跟' . $this->mGetOutStr() . '大量買進');
                    break;
                case 'out_tou_stock_low':
                    $_datas = $this->aOutTouStock['low']['date'][$this->mGetLastWkdate()];
                    $_title = title_tag($this->mGetLastWkdate() . $this->mGetTouStr() . '跟' . $this->mGetOutStr() . '大量賣出');
                    break;
                case 'top_stock_out':
                    $_datas = $this->aTopStock['out']['down_stock'];
                    $_title = title_tag($sch_date['preday'] . '天內,' . $this->mGetOutStr() . '有' . $count_days . '天大量買入');
                    break;
                case 'top_stock_tou':
                    $_datas = $this->aTopStock['tou']['down_stock'];
                    $_title = title_tag($sch_date['preday'] . '天內,' . $this->mGetTouStr() . '有' . $count_days . '天大量買入');
                    break;
                case 'low_stock_out':
                    $_datas = $this->aTopStock['out']['down_stock'];
                    $_title = title_tag($sch_date['preday'] . '天內,' . $this->mGetOutStr() . '有' . $count_days . '天大量賣出');
                    break;
                case 'low_stock_tou':
                    $_datas = $this->aTopStock['tou']['down_stock'];
                    $_title = title_tag($sch_date['preday'] . '天內,' . $this->mGetTouStr() . '有' . $count_days . '天大量賣出');
                    break;
                default:
                    continue;
                    break;
            }
            $rtn .= $_title.'<table border="1">';
            $nsp_data       = $this->mGetStockTransaction($_datas);
            $com_data       = $this->mGetComStockInfo($_datas);
            $amount_average = $this->mGetStockAverageAmount($_datas);

            foreach ($_datas as $_sno) {
                $_per_str = $this->mGetOutPercentStr($_sno);
                if (in_array($_sno, $this->aNoShowSno) || $_per_str == '沒有外資持股量') {
                    continue;
                }
                $ok_count      = $nsp_data[$_sno][$this->sLastWkdate]['v'];
                $average_count = $amount_average[$_sno]['average_count'];
                
                $rtn .= '<tr>';
                $rtn .= '<td width="200">';
                $rtn .= $_sno.'-'.$this->aAllStock[$_sno]['name'].'('.number_format($nsp_data[$_sno][$this->sLastWkdate]['z'], 2).','.cal_num_color($nsp_data[$_sno][$this->sLastWkdate]['uod']).')'.'<br/ >';
                $rtn .= '成交量:' . font_tag(number_format($ok_count, 0, '.', ','), ($ok_count > $average_count ? 'color:red' : 'color:green')) . '<br />';
                $rtn .= '平均成交量:'.number_format($average_count, 0, '.', ',').'<br />';
                $rtn .= '股本:' . $com_data[$_sno]['stock_type'] . '<br />';
                $rtn .= '<a href="https://www.google.com/search?q=' . $_sno . '&oq=' . $_sno . '" target="_blank">G</a>&nbsp;';
                $rtn .= '<a href="https://www.cmoney.tw/finance/f00025.aspx?s=' . $_sno . '" target="_blank">CM</a>&nbsp;';
                $rtn .= '<a href="https://goodinfo.tw/StockInfo/StockDetail.asp?STOCK_ID=' . $_sno . '" target="_blank">GInfo</a>&nbsp;';
                $rtn .= '<br />';
                $rtn .= $this->mUpDownStock($_sno);
                $rtn .= '</td>';
                $rtn .= '<td>'.$this->mGetOutAmount($_sno).'<hr>'.$_per_str.'</td>';
                $rtn .= '</tr>';
            }
            $rtn .= '</table>';
        }

        return $rtn;
    }


    /**
     * 抓個別外資的買賣量
     *
     * @param  array  $sch_date 參閱$this->aSchDate
     * @param  array  $com_type 要查詢的外資的代號,預設全部(以get_mot_info.inc.php的英文為主,放在value值)
     * @param  string $arr_sno  要抓的股票代號,空則不限制
     * 
     * @return array  $rtn[股票代號][外資代號][日期][幾天的買賣資料]['buy']  外資買入的張數
     *                                                        ['sell'] 外資賣出的張數
     *                                                        ['bos']  外資買入減賣出的張數 (可能為負)
     */
    public function mGetMorData($sch_date, $com_type = array(), $arr_sno = array())
    {
        $rtn        = [];
        $com_type   = $com_type ?: array_merge($this->mGetComLong(), $this->mGetComShort());
        $work_dates = $this->mCalStockDates($sch_date);
        if (empty($work_dates['arr_wkdate'])) {
            return [];
        }

        foreach ($this->aMorDays as $_day) {
            foreach ($this->aCom as $_com_name => $_data) {
                if (!in_array($_com_name, $com_type)) {
                    continue;
                }
                foreach ($work_dates['arr_wkdate'] as $_date) {
                    $_file_path = $this->sDataPath . "./stock/out_com_per/" . $_com_name . "/txt_" . $_day . "/" . $_com_name . "_" . $_day . "_" . $_date . ".txt";
                    if (!file_exists($_file_path)) {
                        continue;
                    }
                    $_handle    = fopen($_file_path, "r");
                    $_contents  = fread($_handle, filesize($_file_path));
                    $_arr       = explode('@', $_contents);
                    foreach ($_arr as $_stock_str) {
                        $_com_arr1 = explode('#', $_stock_str);
                        $_sno      = $_com_arr1[0];
                        if (('' == $_sno) || (!empty($arr_sno) && !in_array($_sno, $arr_sno))) {
                            continue;
                        }

                        $_arr_bos = explode(',', $_com_arr1[1]);
                        $_buy     = $_arr_bos[0];
                        $_sel     = $_arr_bos[1];
                        $_bms     = $_arr_bos[2];
                        $rtn[$_sno][$_com_name][$_date][$_day]['buy'] = $_buy;
                        $rtn[$_sno][$_com_name][$_date][$_day]['sel'] = $_sel;
                        $rtn[$_sno][$_com_name][$_date][$_day]['bms'] = $_bms;
                    }
                }
            }
        }

        return $rtn;
    }


    /**
     * 抓外資可能的持股量
     *
     * @param  array  $sch_date 參閱$this->aSchDate
     * @param  array  $com_type 要查詢的外資的代號,預設全部(以get_mot_info.inc.php的英文為主,放在value值)
     * @param  string $arr_sno  要抓的股票代號,空則不限制
     *
     * @return string $rtn[股票代號][外資代號][日期]['buy']  外資可能的買入的總數量
     *                                             ['sell'] 外資可能的賣出的總數量
     *                                             ['bos']  外資可能的持股量
     */
    public function mGetComHoldData($sch_date, $com_type = array(), $arr_sno = array())
    {
        $rtn       = array();
        $arr_com   = $this->aCom;
        $com_long  = $this->mGetComLong();
        $com_short = $this->mGetComShort();
        $data_path = $this->sDataPath;
        if (empty($com_type)) $com_type = array_merge($com_long, $com_short);

        $_arr = $this->mCalStockDates($sch_date);
        $arr_wkdate = $_arr['arr_wkdate'];
        if (empty($arr_wkdate)) return $rtn;

        foreach ($arr_com as $_com_name => $_data) {
            foreach ($arr_wkdate as $_date) {
                $_file_path = $data_path . "./stock/out_com_per/" . $_com_name . "/hold_stock/hold_stock_" . $_date . ".txt";
                if (!file_exists($_file_path)) continue;
                $_handle    = fopen($_file_path, "r");
                $_contents  = fread($_handle, filesize($_file_path));
                $_arr       = explode('@', $_contents);
                foreach ($_arr as $_stock_str) {
                    $_com_arr1 = explode('#', $_stock_str);
                    $_sno      = $_com_arr1[0];
                    if (!empty($arr_sno) && !in_array($_sno, $arr_sno)) continue;
                    $_arr_bos  = explode(',', $_com_arr1[1]);
                    $_buy      = $_arr_bos[0];
                    $_sell     = $_arr_bos[1];
                    $_bos      = $_arr_bos[2];
                    $rtn[$_sno][$_com_name][$_date]['buy']  = $_buy;
                    $rtn[$_sno][$_com_name][$_date]['sell'] = $_sell;
                    $rtn[$_sno][$_com_name][$_date]['bos']  = $_bos;
                }
            }
        }

        return $rtn;
    }


    /**
     * 抓外資持股百分比資料
     *
     * @param  array  $sch_date 參閱$this->aSchDate
     * @param  string $arr_sno  要抓的股票代號,空則不限制
     *
     * @return string $rtn['hold'][股票代號][日期]['all_stock']    發行股數
     *                                           ['else_stock']   外資及陸資尚可投資股數
     *                                           ['hold_stock']   全體外資及陸資持有股數
     *                                           ['else_percent'] 外資及陸資尚可投資比率
     *                                           ['hold_percent'] 全體外資及陸資持股比率
     *                    ['info'][股票代號]['name'] 股票名稱
     */
    public function mGetOutPercent($sch_date, $arr_sno = array())
    {
        $rtn  = array();
        $_arr = $this->mCalStockDates($sch_date);
        $arr_wkdate = $_arr['arr_wkdate'];
        $data_path  = $this->sDataPath;
        if (empty($arr_wkdate)) return $rtn;

        // 外資持股比例
        foreach ($arr_wkdate as $_date) {
            $_file_path = $data_path . "./stock/out_hold_percent/txt_" . $_date . ".txt";
            if (!file_exists($_file_path)) continue;
            $_handle    = fopen($_file_path, "r");
            $_contents  = fgets($_handle);
            $_data      = explode('@', $_contents);
            foreach ($_data as $_str) {
                $_arr     = explode('#', $_str);
                $_sno     = $_arr[0];
                if (!empty($arr_sno) && !in_array($_sno, $arr_sno)) continue;
                $_arr1    = explode(',', $_arr[1]);
                $_name    = mb_convert_encoding($_arr1[0], "UTF-8", "auto");

                $rtn['info'][$_sno]['name'] = $_name;
                $rtn['hold'][$_sno][$_date]['all_stock']    = $_arr1[2];
                $rtn['hold'][$_sno][$_date]['else_stock']   = $_arr1[3];
                $rtn['hold'][$_sno][$_date]['hold_stock']   = $_arr1[4];
                $rtn['hold'][$_sno][$_date]['else_percent'] = $_arr1[5];
                $rtn['hold'][$_sno][$_date]['hold_percent'] = $_arr1[6];
            }
        }

        return $rtn;
    }

    /**
     * 抓外資持股百分比資料
     *
     * @param  array  $sch_date 參閱$this->aSchDate
     * @param  string $arr_sno  要抓的股票代號,空則不限制
     *
     * @return array $rtn[股票代號][日期]['out','tou','zen']['buy'] 三大法人當天的買入量
     *                                                      ['sel'] 三大法人當天的賣出量
     *                                                      ['bms'] 三大法人當天的買入減賣出量
     */
    public function mGetThreeBigBOS($sch_date, $arr_sno = array())
    {
        $rtn  = [];
        $_arr = $this->mCalStockDates($sch_date);
        $data_path  = $this->sDataPath;
        $arr_wkdate = $_arr['arr_wkdate'];
        if (empty($arr_wkdate)) {
            return $rtn;
        }

        foreach ($arr_wkdate as $_date) {
            $_file_path = $data_path . "./stock/threebig_bos/txt_" . $_date . ".txt";
            if (!file_exists($_file_path)) continue;
            $_handle    = fopen($_file_path, "r");
            $_contents  = fgets($_handle);
            $_data      = explode('@', $_contents); //拆解每個股票資訊
            foreach ($_data as $_str) {
                $_arr   = explode('#', $_str); //拆解股票代碼以及買賣量
                $_sno   = $_arr[0];
                $_arr1  = explode(',', $_arr[1]); //拆解每個買賣量

                $rtn[$_sno][$_date]['out']['buy'] = (int)$_arr1[1];
                $rtn[$_sno][$_date]['out']['sel'] = (int)$_arr1[2];
                $rtn[$_sno][$_date]['out']['bms'] = (int)$_arr1[3];

                $rtn[$_sno][$_date]['tou']['buy'] = (int)$_arr1[4 + 3];
                $rtn[$_sno][$_date]['tou']['sel'] = (int)$_arr1[5 + 3];
                $rtn[$_sno][$_date]['tou']['bms'] = (int)$_arr1[6 + 3];

                $rtn[$_sno][$_date]['zen']['buy'] = (int)$_arr1[4 + 6];
                $rtn[$_sno][$_date]['zen']['sel'] = (int)$_arr1[5 + 6];
                $rtn[$_sno][$_date]['zen']['bms'] = (int)$_arr1[6 + 6];
            }
            fclose($_handle);
        }

        return $rtn;
    }

    /**
     * 抓股票每天的價位
     *
     * @param  array  $sch_date 參閱$this->aSchDate
     * @param  string $arr_sno  要抓的股票代號,空則不限制
     *
     * @return array $rtn[股票代號][日期]['ok_stock']    成交股數
     *                                   ['ok_money']    成交金額
     *                                   ['open_index']  開盤價
     *                                   ['top_index']   最高價 
     *                                   ['low_index']   最低價
     *                                   ['close_index'] 收盤價
     *                                   ['uod']         漲跌價差
     *                                   ['ok_count']    成交筆數
     */
    public function mGetStockIndex($sch_date, $arr_sno = [])
    {
        $stock_dates = $this->mCalStockDates($sch_date);
        if (empty($stock_dates['arr_wkdate'])) {
            return [];
        }

        $all_data = [];
        $rtn      = [];
        //股票代號全抓或只抓一天的資料,用stock_price_d的資料來抓
        if (empty($arr_sno) || 1 == count($stock_dates['arr_wkdate'])) {
            foreach ($stock_dates['arr_wkdate'] as $_date) {
                if (!file_exists($this->sDataPath . "./stock/stock_price_d/txt_" . $_date . ".txt")) {
                    continue;
                }
                $_handle    = fopen($this->sDataPath . "./stock/stock_price_d/txt_" . $_date . ".txt", "r");
                $_contents  = fgets($_handle);
                fclose($_handle);
                $_data      = explode('@', $_contents);
                foreach ($_data as $_str) {
                    $_arr = explode('#', $_str);
                    if (!empty($arr_sno) && !in_array($_arr[0], $arr_sno)) {
                        continue;
                    }
                    $all_data[$_arr[0]][$_date] = $_arr[1];
                }
            }
        } else {
            //只抓出需要的年度就好
            $arr_yy = [];
            foreach ($stock_dates['arr_wkdate'] as $_date) {
                if (!in_array(substr($_date, 0, 4), $arr_yy)) {
                    $arr_yy[] = substr($_date, 0, 4);
                }
            }
            foreach ($arr_sno as $_sno) {
                $_date_data = [];
                foreach ($arr_yy as $_yy) {
                    if (!file_exists($this->sDataPath . "./stock/stock_price_s/" . $_sno . "/txt_" . $_yy . ".txt")) {
                        continue;
                    }
                    $_handle    = fopen($this->sDataPath . "./stock/stock_price_s/" . $_sno . "/txt_" . $_yy . ".txt", "r");
                    $_contents  = fgets($_handle);
                    fclose($_handle);
                    $_data      = explode('@', $_contents);
                    foreach ($_data as $_str) {
                        $_arr  = explode('=', $_str);
                        $_date = $_arr[0];
                        $_str1 = $_arr[1];
                        if (!in_array($_date, $stock_dates['arr_wkdate'])) {
                            continue;
                        }
                        $_arr1 = explode('#', $_str1);
                        $_date_data[$_date] = $_arr1[1];
                    }
                }
                ksort($_date_data);
                $all_data[$_sno] = $_date_data;
            }
        }
        
        //將上面兩種資料都存到$all_data後最後整理成輸出陣列
        foreach ($all_data as $_sno => $_data) {
            foreach ($_data as $_date => $_str) {
                $_arr = explode(',', $_str);
                $rtn[$_sno][$_date]['ok_stock']    = $_arr[0];
                $rtn[$_sno][$_date]['ok_money']    = $_arr[1];
                $rtn[$_sno][$_date]['open_index']  = $_arr[2];
                $rtn[$_sno][$_date]['top_index']   = $_arr[3];
                $rtn[$_sno][$_date]['low_index']   = $_arr[4];
                $rtn[$_sno][$_date]['close_index'] = $_arr[5];
                $rtn[$_sno][$_date]['uod']         = $_arr[6];
                $rtn[$_sno][$_date]['ok_count']    = $_arr[7];
            }
        }

        return $rtn;
    }

    /**
     * 抓每天台股的指數
     * @param  array  $sch_date 參閱$this->aSchDate
     * @return string $rtn[日期]['ok_stock'] 成交股數
     *                          ['ok_money'] 成交金額
     *                          ['ok_count'] 成交筆數
     *                          ['tw_index'] 發行量加權股價指數(台股指數)
     *                          ['uod']      漲跌點數
     */
    public function mGetTWIndex($sch_date = [])
    {        
        $_arr = $this->mCalStockDates($sch_date ?: $this->mGetSchDate());
        if (empty($_arr['arr_wkdate'])) {
            return [];
        }
        $rtn = [];

        //只抓出需要的年度就好
        $arr_yy = [];
        foreach ($_arr['arr_wkdate'] as $_date) {
            if (!in_array(substr($_date, 0, 4), $arr_yy)) {
                $arr_yy[] = substr($_date, 0, 4);
            }
        }

        foreach ($arr_yy as $_yy) {
            if (!file_exists($this->sDataPath . "./stock/tw_index/txt_" . $_yy . ".txt")) {
                continue;
            }
            $_handle    = fopen($this->sDataPath . "./stock/tw_index/txt_" . $_yy . ".txt", "r");
            $_contents  = fgets($_handle);
            $_data      = explode('@', $_contents);
            fclose($_handle);
            foreach ($_data as $_str) {
                $_arr   = explode('=', $_str);
                $_arr1  = explode(',', $_arr[1]);

                $rtn[$_arr[0]]['ok_stock'] = $_arr1[0];
                $rtn[$_arr[0]]['ok_money'] = $_arr1[1];
                $rtn[$_arr[0]]['ok_count'] = $_arr1[2];
                $rtn[$_arr[0]]['tw_index'] = $_arr1[3];
                $rtn[$_arr[0]]['uod']      = $_arr1[4];
            }
        }

        return $rtn;
    }



    /**
     * 抓取外資最常操作的股票
     * @param  array $arr_sno 傳入要抓的股票代號
     * @param  int   $count   判斷要抓外資操作前幾名,預設不限制
     * @return array $rtn[sno]['rank']            序號(從0開始)
     *                        ['cnt']             上榜次數
     *                        ['stock_type']      L=大,M=中,S=小 型股
     *                        ['all_stock']       股數
     *                        ['max_buy/sel/bms'] 單次最多買入張數
     *                        ['sum_buy/sel/bms'] 全部買入張數
     *                        ['avg_buy/sel/bms'] 平均買入張數(全部買入張數/上榜次數)
     *                        ['buy/sel/bms_lv']  股票買賣量的級距佔的百分比
     */
    public function mGetComStockInfo($arr_sno = array(), $count = 0)
    {
        if (!file_exists($this->sDataPath . "./stock/out_com_per/morganstanley/stock_info.txt")) {
            return [];
        }
        $rtn       = [];
        $_handle   = fopen($this->sDataPath . "./stock/out_com_per/morganstanley/stock_info.txt", "r");
        $_contents = fgets($_handle);
        fclose($_handle);
        $_data = explode('@', $_contents);
        foreach ($_data as $_rank => $_str) {
            if (0 != $count && ($_rank + 1) > $count) {
                break;
            }
            $_arr  = explode('#', $_str);
            $_sno  = $_arr[0];
            if (!empty($arr_sno) && !in_array($_sno, $arr_sno)) {
                continue;
            }
            $_arr1 = explode(',', $_arr[1]);
            $rtn[$_sno]['rank'] = $_rank;
            foreach ($_arr1 as $_str1) {
                $_arr2 = explode('=', $_str1);
                $_key2 = $_arr2[0];
                $_val2 = $_arr2[1];
                if (false !== strpos($_key2, '_lv')) {
                    $_arr3  = explode(';', $_val2);
                    $lv_arr = array();
                    foreach ($_arr3 as $_str3) {
                        $_arr4 = explode('!', $_str3);
                        $lv_arr[$_arr4[0]] = $_arr4[1];
                    }
                    $rtn[$_sno][$_key2] = $lv_arr;
                } else {
                    $rtn[$_sno][$_key2] = $_val2;
                }
            }
        }
        return $rtn;
    }

    /**
     * 抓取摩根買賣方式的結果
     * @param  array $sch_date     要計算的日期
     * @param  int   $stock_number 判斷要抓外資操作前幾名,預設100名
     * @param  array $arr_combo    傳入要抓的連續購買幾天的資料要抓出來,預設2~4
     * @return array $rtn['sno'][股票代號][連續買入天數]['win']          上漲筆數
     *                                               ['lose']        下跌筆數
     *                                               ['sum']         總筆數
     *                                               ['total_per']   總漲跌百分比
     *                                               ['total_money'] 總漲跌金額
     *                                               ['succ_per']    成功率
     *                                               ['rank']        操作排名
     *                                               ['unit_money']  每次的投資報酬率
     *                                               ['data'][序號]['buy_qua']          買入的加總張數
     *                                                            ['bd_index']        買入的股價
     *                                                            ['sd_index']        賣出的股價
     *                                                            ['ud_per']          漲跌百分比
     *                                                            ['ud']              漲跌股價
     *                                                            ['bd_tw_index']     買入日期的台指
     *                                                            ['sd_tw_index']     賣出日期的台指
     *                                                            ['ud_tw_index']     買入-賣出的台指
     *                                                            ['sell_date']       賣出日期
     *                                                            ['buy_date'][序號] 買入日期
     *               $rtn['all'][連續買入天數]['up_cnt']         上漲筆數
     *                                       ['up_money']      上漲資料加總金額
     *                                       ['dn_cnt']        下跌筆數
     *                                       ['dn_money']      下跌資料加總金額
     *                                       ['succ_cnt']      成功筆數
     *                                       ['succ_sno']      成功股票代號
     *                                       ['succ_money']    成功資料加總金額
     *                                       ['succ_up_cnt']   成功且上漲筆數
     *                                       ['succ_up_sno']   成功且上漲股票代號
     *                                       ['succ_up_money'] 成功且上漲資料加總金額
     *                                       ['total_money']   全部筆數家總金額
     *                                       ['stock_number']  全部筆數
     *                                       ['win_per']       成功定義的百分比
     *                                       ['sell_pic']      賣出設定量
     *                                       
    
     */
    public function mGetComCalData($sch_date = array(), $stock_number = 100, $arr_combo = array(2, 3, 4))
    {
        $rtn = [];
        if (empty($sch_date)) {
            $sch_date['first_date'] = '20180101';
        }
        //從摩根操作的前150名來統計
        $arr_sno = [];
        $cnt     = 0;
        foreach ($this->mGetComStockInfo([], $stock_number) as $_sno => $_data) {
            if ($cnt > $stock_number) {
                break;
            }
            $arr_sno[] = $_sno;
            $cnt++;
        }
        $mor_data   = $this->mGetMorData($sch_date, ['morganstanley'], $arr_sno);
        $stock_info = $this->mGetStockIndex($sch_date, $arr_sno);
        $tw_index   = $this->mGetTWIndex($sch_date);
        $arr_date   = $this->mCalStockDates($sch_date);
        $out_per    = $this->mGetOutPercent($sch_date);
        $bc_idx     = 3;   //精密計算的小數點位數
        $win_per    = 70;  //判斷要看成功率多少的資料
        $sell_pic   = 200; //判斷賣出多少張計為賣出信號
        $arr_cnt    = [];
        foreach ($arr_sno as $_idx => $_sno) {
            $_sdatas = $stock_info[$_sno];
            if (empty($mor_data[$_sno]['morganstanley'])) {
                continue;
            }
            foreach ($arr_combo as $_combo_days) {
                $_chk_date = [];
                $_chk_cnt  = 0;
                foreach ($mor_data[$_sno]['morganstanley'] as $_date => $_mor_data) {
                    $_bms = $_mor_data['1']['bms'];
                    $_buy_date_is_ary = is_array($_chk_date['data'][$_chk_cnt]['buy_date']);
                    $_chk_save1       = ($_bms > 0 && $_chk_date['chk']['prev_bms'] < 0 && !$_buy_date_is_ary);
                    $_chk_save2       = ($_bms > 0 && $_buy_date_is_ary && in_array($_chk_date['chk']['prev_date'], $_chk_date['data'][$_chk_cnt]['buy_date']) && count($_chk_date['data'][$_chk_cnt]['buy_date']) < $_combo_days);
                    //第一次買賣量由負轉正 or 第一次之後的第二筆跟第三筆則要把日期存起來
                    if ($_chk_save1 || $_chk_save2) {
                        $_chk_date['data'][$_chk_cnt]['buy_date'][]  = $_date;
                        $_chk_date['data'][$_chk_cnt]['buy_qua']    += $_bms;
                        //買賣量為負 and 目前已經有存日期資料則把該序號輕空
                    } else if ($_bms < 0 && $_buy_date_is_ary) {
                        $_chk_date['data'][$_chk_cnt]['buy_date'] = '';
                    }
                    //記錄上一筆資料的賣出日期
                    if (is_array($_chk_date['data'][$_chk_cnt - 1]['buy_date']) && '' == $_chk_date['data'][$_chk_cnt - 1]['sell_date'] && $_bms <= $sell_pic * (-1)) {
                        $_chk_date['data'][$_chk_cnt - 1]['sell_date'] = $_date;
                    }
                    //如果目前這個序號資料已經大於等於連續天數,則將序號+1
                    if ($_buy_date_is_ary && count($_chk_date['data'][$_chk_cnt]['buy_date']) >= $_combo_days) {
                        $_chk_cnt++;
                    }
                    $_chk_date['chk']['prev_bms']  = $_bms;
                    $_chk_date['chk']['prev_date'] = $_date;
                }
                if (empty($_chk_date['data'])) {
                    continue;
                }
                $arr_cnt[$_combo_days]++;
                $_sum         = 0;
                $_sum_per     = 0;
                $_win_cnt     = 0;
                $_lose_cnt    = 0;
                $_sum_cnt     = 0;
                $_data_cnt    = 0;
                foreach ($_chk_date['data'] as $_data) {
                    $_bdate3      = $_data['buy_date'][$_combo_days - 1];
                    $_sdate       = $_data['sell_date'];
                    $_bd_index    = $_sdatas[$_bdate3]['close_index'];
                    $_sd_index    = $_sdatas[$_sdate]['close_index'];
                    $_bd_tw_index = $tw_index[$_bdate3]['tw_index'];
                    $_sd_tw_index = $tw_index[$_sdate]['tw_index'];

                    //計算外資持股上升對股票的影響
                    $_sch_date = [];
                    $_sch_date['last_date'] = $_bdate3;
                    $_sch_date['preday']    = 7;
                    $_arr_date = $this->mCalStockDates($_sch_date);
                    $_first_out_hold = $out_per['hold'][$_sno][$_arr_date['first_wkdate']]['hold_percent']; //開頭的外資持股量
                    $_last_out_hold  = $out_per['hold'][$_sno][$_arr_date['last_wkdate']]['hold_percent'];  //結束的外資持股量
                    $_diff_out_hold  = bcsub($_last_out_hold, $_first_out_hold, $bc_idx);
                    $_chk_diff  = (('' != $_bdate3 && '' != $_first_out_hold && '' != $_last_out_hold) && $_diff_out_hold == 0.6);

                    //沒有資料或買入數量不足的就跳過不算了
                    if ('' == $_bdate3 || '' == $_sdate || '' == $_bd_index || '' == $_sd_index || !is_numeric($_bd_index) || !is_numeric($_sd_index) || !$_chk_diff) {
                        continue;
                    }
                    $_ud_tw_index = bcsub($_sd_tw_index, $_bd_tw_index, $bc_idx);
                    $_ud          = bcsub($_sd_index, $_bd_index, $bc_idx);
                    $_sum         = bcadd($_sum, $_ud, $bc_idx);
                    $_ud_per      = bcmul(bcdiv($_sd_index, $_bd_index, $bc_idx), 100, $bc_idx);
                    $_sum_per     = bcadd($_sum_per, bcsub($_ud_per, 100, $bc_idx), $bc_idx);
                    //判斷單次交易為賺錢或賠錢
                    if (1 == bccomp($_ud_per, 100, $bc_idx)) {
                        $_win_cnt++;
                    } else {
                        $_lose_cnt++;
                    }

                    $rtn['sno'][$_sno][$_combo_days]['data'][$_data_cnt]                 = $_data;
                    $rtn['sno'][$_sno][$_combo_days]['data'][$_data_cnt]['bd_index']     = $_bd_index;
                    $rtn['sno'][$_sno][$_combo_days]['data'][$_data_cnt]['sd_index']     = $_sd_index;
                    $rtn['sno'][$_sno][$_combo_days]['data'][$_data_cnt]['ud_per']       = floor($_ud_per);
                    $rtn['sno'][$_sno][$_combo_days]['data'][$_data_cnt]['ud']           = $_ud;
                    $rtn['sno'][$_sno][$_combo_days]['data'][$_data_cnt]['bd_tw_index']  = $_bd_tw_index;
                    $rtn['sno'][$_sno][$_combo_days]['data'][$_data_cnt]['sd_tw_index']  = $_sd_tw_index;
                    $rtn['sno'][$_sno][$_combo_days]['data'][$_data_cnt]['ud_tw_index']  = $_ud_tw_index;
                    $rtn['sno'][$_sno][$_combo_days]['data'][$_data_cnt]['diff_percent'] = $_diff_out_hold;
                    $_data_cnt++;
                }
                $rtn['sno'][$_sno][$_combo_days]['win']  = $_win_cnt;
                $rtn['sno'][$_sno][$_combo_days]['lose'] = $_lose_cnt;
                $rtn['sno'][$_sno][$_combo_days]['sum']  = $_sum_cnt = $_win_cnt + $_lose_cnt;

                $_total_per  = floor(bcadd($_sum_per, 100, $bc_idx));
                $_sno_money  = ($_sum * 1000);
                $_succ_per   = floor((0 == (int)$_sum_cnt ? 0 : bcmul(bcdiv($_win_cnt, $_sum_cnt, $bc_idx), 100, $bc_idx)));
                $_unit_money = floor((0 == $_sum_cnt ? 0 : floor($_sno_money / $_sum_cnt)) * $_succ_per / 100);
                $rtn['sno'][$_sno][$_combo_days]['total_per']   = $_total_per;  //漲跌總和
                $rtn['sno'][$_sno][$_combo_days]['total_money'] = $_sno_money;  //漲跌金額
                $rtn['sno'][$_sno][$_combo_days]['succ_per']    = $_succ_per;   //操作成功率
                $rtn['sno'][$_sno][$_combo_days]['rank']        = $_idx;        //操作排名
                $rtn['sno'][$_sno][$_combo_days]['unit_money']  = $_unit_money; //每次的投資報酬率
                //紀錄連續天數的加總資料,有資料才紀錄(因為之後會取平均)
                if ($_sum_cnt > 0) {
                    $rtn['all'][$_combo_days]['sum_succ_per']   += $_succ_per;
                    $rtn['all'][$_combo_days]['sum_succ_money'] += $_unit_money;
                    $rtn['all'][$_combo_days]['sum_succ_cnt']++;
                }
                $rtn['all'][$_combo_days]['combo_sum_cnt'] += $_sum_cnt;
                //判斷上漲跟下跌的百分比總體加起來正成長還是負成長
                if (1 == bccomp($_total_per, 100, $bc_idx)) {
                    $rtn['all'][$_combo_days]['up_cnt']++;
                    $rtn['all'][$_combo_days]['up_money'] += $_sno_money;
                } else {
                    $rtn['all'][$_combo_days]['dn_cnt']++;
                    $rtn['all'][$_combo_days]['dn_money'] += $_sno_money;
                }
                //判斷單支股票成功率是否大於$win_per設定
                if (1 == bccomp($_succ_per, $win_per, $bc_idx)) {
                    $rtn['all'][$_combo_days]['succ_cnt']++;
                    $rtn['all'][$_combo_days]['succ_sno'][]  = $_sno;
                    $rtn['all'][$_combo_days]['succ_money'] += $_sno_money;
                }
                //以上兩種情況都符合的話存在這裡
                if (1 == bccomp($_succ_per, $win_per, $bc_idx) && 1 == bccomp($_total_per, 100, $bc_idx)) {
                    $rtn['all'][$_combo_days]['succ_up_cnt']++;
                    $rtn['all'][$_combo_days]['succ_up_sno'][]  = $_sno;
                    $rtn['all'][$_combo_days]['succ_up_money'] += $_sno_money;
                }
                $rtn['all'][$_combo_days]['total_money'] += $_sno_money;
                $rtn['all'][$_combo_days]['all_cnt']      = $arr_cnt[$_combo_days];
                $rtn['all'][$_combo_days]['win_per']      = floor($win_per);
                $rtn['all'][$_combo_days]['sell_pic']     = $sell_pic;
                $rtn['all'][$_combo_days]['first_wkdate'] = $arr_date['first_wkdate'];
                $rtn['all'][$_combo_days]['last_wkdate']  = $arr_date['last_wkdate'];
            }
        }

        return $rtn;
    }

    /**
     * 取得摩根日期連續多天買入的股票
     * @param  string $date       查詢最後日期
     * @param  string $combo_days 連續天數
     * @return array $rtn[序號] 股票代號
     */
    public function mGetComComboData($date, $combo_days = 4)
    {
        $pre_days  = 30; //直接以30天工作天的資料來查,再沒有也沒辦法了~
        $mor_data  = $this->mGetMorData([
            'preday'    => $pre_days,
            'last_date' => $date,
        ], [
            'morganstanley'
        ]);
        $combo_sno = [];
        foreach ((array)$mor_data as $_sno => $_data) {
            $_cnt = 0;
            for ($_date = $date; $_cnt < $pre_days; $_date = $this->mCalNextWkDate($_date, -1)) {
                $_bms = $_data['morganstanley'][$_date][1]['bms'];
                //因為日期由後往前,所以遇到賣出的資料直接跳過了 or 查詢當天沒有買賣資料也跳過
                if ($_bms < 0 || ($date == $_date && '' == $_bms)) { 
                    break;
                }
                if ($_bms > 0) {
                    $combo_sno[$_sno]['cnt']++;
                }
                $_cnt++;
            }
            if ($combo_sno[$_sno]['cnt'] < $combo_days) {
                unset($combo_sno[$_sno]);
            }
        }
        return array_keys($combo_sno);
    }

    /**
     * 取得股票即時資料
     * @param  array $datas[序號][sno]    要查詢的股票
     * @return array $rtn[sno][日期][z]   收盤價
     *                   [sno][日期][o]   開盤價
     *                   [sno][日期][h]   最高價
     *                   [sno][日期][l]   最低價
     *                   [sno][日期][uod] 價差價
     *                   [sno][日期][v]   成交量
     */
    public function mGetStockTransaction($datas)
    {
        $sch_date   = $this->mGetSchDate();
        $_sdata     = $this->mGetStockIndex($sch_date, $datas);
        $rtn        = [];
        
        //抓不到股價資料就即時抓取
        if ('' == $_sdata[$datas[0]][$this->sLastWkdate]) {
            $_arr = [];
            foreach ($datas as $_sno) {
                $_arr[] = 'tse_' . $_sno . '.tw';
                $_arr[] = 'otc_' . $_sno . '.tw';
            }
            //抓即時股價資訊
            //https://mis.twse.com.tw/stock/api/getStock.jsp?ch=1565.tw&json=1&delay=0
            //https://mis.twse.com.tw/stock/api/getStockInfo.jsp?ex_ch=tse_2439.tw&json=1&delay=0&_=1552123547443
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, 'https://mis.twse.com.tw/stock/api/getStockInfo.jsp?ex_ch=' . implode('|', $_arr) . '&json=1&delay=0&_=' . microtime(true));
            $data = json_decode(curl_exec($ch));
            curl_close($ch);

            foreach ($data->msgArray as $_data) {
                $_sno = $_data->c;
                $rtn[$_sno][$this->sLastWkdate]['c']     = $_data->c;     //股票代號
                $rtn[$_sno][$this->sLastWkdate]['z']     = $_data->z;     //當盤成交價
                $rtn[$_sno][$this->sLastWkdate]['n']     = $_data->n;     //公司簡稱
                $rtn[$_sno][$this->sLastWkdate]['nf']    = $_data->nf;    //公司全名
                $rtn[$_sno][$this->sLastWkdate]['tv']    = $_data->tv;    //當盤成交量
                $rtn[$_sno][$this->sLastWkdate]['v']     = $_data->v;     //累積成交量
                $rtn[$_sno][$this->sLastWkdate]['b']     = $_data->b;     //揭示買價(從高到低，以_分隔資料)
                $rtn[$_sno][$this->sLastWkdate]['g']     = $_data->g;     //揭示買量(配合b，以_分隔資料)
                $rtn[$_sno][$this->sLastWkdate]['a']     = $_data->a;     //揭示賣價(從低到高，以_分隔資料)
                $rtn[$_sno][$this->sLastWkdate]['f']     = $_data->f;     //揭示賣量(配合a，以_分隔資料)
                $rtn[$_sno][$this->sLastWkdate]['o']     = $_data->o;     //開盤股價
                $rtn[$_sno][$this->sLastWkdate]['h']     = $_data->h;     //最高價
                $rtn[$_sno][$this->sLastWkdate]['l']     = $_data->l;     //最低價
                $rtn[$_sno][$this->sLastWkdate]['y']     = $_data->y;     //昨收價
                $rtn[$_sno][$this->sLastWkdate]['u']     = $_data->u;     //漲停價
                $rtn[$_sno][$this->sLastWkdate]['w']     = $_data->w;     //跌停價
                $rtn[$_sno][$this->sLastWkdate]['tlong'] = $_data->tlong; //epoch毫秒數
                $rtn[$_sno][$this->sLastWkdate]['d']     = $_data->d;     //最近交易日期(YYYYMMDD)
                $rtn[$_sno][$this->sLastWkdate]['t']     = $_data->t;     //最近成交時刻(HH:MI:SS)
                $rtn[$_sno][$this->sLastWkdate]['uod']   = bcsub($_data->z, $_data->y, 2); //價差
            }
        } else {
            foreach ($_sdata as $_sno => $_data) {
                foreach ($_data as $_date => $_data1) {
                    $rtn[$_sno][$_date]['z']   = $_data1['close_index']; //收盤價
                    $rtn[$_sno][$_date]['o']   = $_data1['open_index'];  //開盤價
                    $rtn[$_sno][$_date]['h']   = $_data1['top_index'];   //最高價
                    $rtn[$_sno][$_date]['l']   = $_data1['low_index'];   //最低價
                    $rtn[$_sno][$_date]['uod'] = $_data1['uod'];         //價差價
                    $rtn[$_sno][$_date]['v']   = $_data1['ok_count'];    //成交量
                }
            }
        }

        return $rtn;
    }

    /**
     * 取得股票購買的平均量
     * @param array $datas[sno] 要查詢的股票
     */
    protected function mGetStockAverageAmount($datas)
    {
        $stock_data = $this->mGetStockTransaction($datas);
        $rtn = [];
        foreach ($stock_data as $sno => $_data) {
            $sum = 0;
            foreach ($_data as $_item) {
                $sum += $_item['v'];
            }
            $rtn[$sno]['average_count'] = intval($sum / count($_data));
        }
        return $rtn;
    }
}
