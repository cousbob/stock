<?php
// 載入需要的函式
if (!function_exists('nextday')) {
    require_once dirname(dirname(__file__)) . './stock.inc.php';
}
require_once dirname(__FILE__) . './MorInfo.php';
class cGainStockInfo
{
    private $sRootPath = ''; //主目錄路徑
    private $sDataPath = ''; //資料目錄路徑
    private $sYY       = ''; //目前的年度(yyyy)
    private $sMM       = ''; //目前的月份(mm)
    private $sDD       = ''; //目前的日期(dd)
    private $sToday    = ''; //當天的日期(yyyymmmdd)
    private $nGetType  = 1;  //1=晚前抓preDay天的資料,2=從funBegdate抓到funEnddate,預設為1
    private $nPreDay   = 1;  //往前抓多少天的資料,預設2天
    private $sBegdate  = ''; //開始抓資料的日期(yyyymmdd)
    private $sEnddate  = ''; //結束抓資料的日期(yyyymmdd)
    private $aCom      = []; //外資的代號跟中文名稱陣列
    private $aMorDays  = [1, 5, 10, 30]; //個別外資抓X日的資料
    //抓股票資的代號
    private $aAllGainType = [
        'mGetMorInfo'     => '外資個股買賣量',
        'mOutHoldPrecent' => '外資持股量',
        'mTwIndex'        => '台股指數',
        'mThreebigBos'    => '三大法人買賣量',
        'mStockPrice'     => '個股每日股價',
        'mGetHoldStock'   => '計算外資持股量',
    ];
    //所有買賣量的級距
    private $aBosType = ['buy', 'sel', 'bms'];

    public function __construct()
    {
        $this->sYY       = date('Y');
        $this->sMM       = date('m');
        $this->sDD       = date('d');
        $this->sToday    = $this->sYY . $this->sMM . $this->sDD;
        $this->sRootPath = dirname(dirname(__file__)) . '/';
        $this->sDataPath = $this->sRootPath . '../data';
        $this->aCom      = MorInfo::getMorInfo();
    }

    public function mSetGetType($val)
    {
        $this->nGetType = $val;
    }

    public function mGetGetType()
    {
        $chk_get_type    = $this->nGetType;
        $chk_fun_begdate = $this->mGetBegdate();
        $chk_fun_enddate = $this->mGetEnddate();
        //判斷如果設定值不正常的話,就要給預設值
        if ('' == $chk_get_type || !is_numeric($chk_get_type) || (1 != $chk_get_type && ('' == $chk_fun_begdate || '' == $chk_fun_enddate))) {
            $this->mSetGetType(1);
        }
        return $this->nGetType;
    }

    public function mSetPreDay($val)
    {
        $this->nPreDay = $val;
    }

    public function mGetPreDay()
    {
        $chk_pre_day = $this->nPreDay;
        if ('' == $chk_pre_day || !is_numeric($chk_pre_day)) {
            $this->mSetPreDay(1);
        }
        return $this->nPreDay;
    }

    public function mSetBegdate($val)
    {
        $this->sBegdate = $val;
    }

    public function mGetBegdate()
    {
        return $this->sBegdate;
    }

    public function mSetEnddate($val)
    {
        $this->sEnddate = $val;
    }

    public function mGetEnddate()
    {
        return $this->sEnddate;
    }

    public function mGetAllGainType()
    {
        return $this->aAllGainType;
    }

    /**
     * 取得股票資料的主要Method
     * @param array $arr_gain_type['mGetMorInfo']       外資個股買賣量
     *              $arr_gain_type['mOutHoldPrecent']   外資持股量
     *              $arr_gain_type['mTwIndex']          台股指數
     *              $arr_gain_type['mThreebigBos']      三大法人買賣量
     *              $arr_gain_type['mStockPrice']       每日股價
     *              $arr_gain_type['mGetHoldStock']     重新計算持股         
     *  @return void 
     *  
     * */
    public function mGainStock($arr_gain_type)
    {
        foreach ($this->mGetAllGainType() as $_type => $_str) {
            if (true == $arr_gain_type[$_type]) {
                $this->$_type();
            }
        }
    }

    /**
     * 統一設定curl
     * @return object $ch
     */
    private function setCurl() 
    {
        // 建立CURL連線
        $ch = curl_init();
        // 可以存入陣列裡面
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        
        return $ch;
    }

    /**
     * 取得外資每日交易前幾名
     *
     * @return void
     */
    private function mGetMorInfo()
    {
        $pre_day     = $this->mGetPreDay();
        $fun_begdate = $this->mGetBegdate();
        $fun_enddate = $this->mGetEnddate();
        $ch          = $this->setCurl();

        // 如果get_type設定1的話,是抓每天最新的,2的話是依照設定的日期區間抓資料
        if ($this->mGetGetType() != 1) {
            $pre_day = 999999;

            $b_yy = substr($fun_begdate, 0, 4); //2012
            $b_mm = substr($fun_begdate, 4, 2); //10
            $b_dd = substr($fun_begdate, 6, 2); //23

            $e_yy = substr($fun_enddate, 0, 4); //2013
            $e_mm = substr($fun_enddate, 4, 2); //6
            $e_dd = substr($fun_enddate, 6, 2); //23

            $mk_e_date = mktime(0, 0, 0, $e_mm, $e_dd, $e_yy);
        }

        // 存所有外資的資料
        //http://jdata.yuanta.com.tw/z/zg/zgb/zgb0_1470_10.djhtm
        //http://jdata.yuanta.com.tw/z/zg/zgb/zgb0.djhtm?a=1470&b=1470&c=B&d=30
        //http://jdata.yuanta.com.tw/z/zg/zgb/zgb0.djhtm?a=1470&b=1470&c=B&e=2017-7-3&f=2017-8-11
        foreach ($this->aMorDays as $_day) {
            foreach ($this->aCom as $com_name => $_data) {
                for ($i = 0; $i < $pre_day; $i++) {
                    if (1 == $this->mGetGetType()) {
                        $_url = "http://jdata.yuanta.com.tw/z/zg/zgb/zgb0.djhtm?a=" . $_data['num'] . "&b=" . $_data['num'] . "&c=E&d=" . $_day;
                    } else {
                        $_date     = date('Y-m-d', mktime(0, 0, 0, $b_mm, $b_dd + $i, $b_yy));
                        $_bef_date = date('Y-m-d', mktime(0, 0, 0, $b_mm, $b_dd + ($i - $_day + 1), $b_yy));
                        $_mk_date  = mktime(0, 0, 0, $b_mm, $b_dd + $i, $b_yy);

                        if ($_mk_date > $mk_e_date) {
                            break;
                        }
                        $_url = "http://jdata.yuanta.com.tw/z/zg/zgb/zgb0.djhtm?a=" . $_data['num'] . "&b=" . $_data['num'] . "&c=E&e=" . $_bef_date . "&f=" . $_date;
                    }

                    // 設定外資網址
                    curl_setopt($ch, CURLOPT_URL, $_url);
                    $temp = curl_exec($ch);
                    $this->save_info($this->get_com_info(explode("\n", $temp)), $_day, $temp);
                    sleep(3);
                }
            }
        }

        // 關閉CURL連線
        curl_close($ch);
    }


    /**
     * 取得券商資訊
     *
     * @param  array $arr 從html拆解出來的陣列
     * @return array $rtn 要新增成txt檔的陣列
     */
    private function get_com_info($arr)
    {
        $rtn  = [];
        $flag_count = 0;
        $flag_stock = '';

        foreach ($arr as $_str) {
            $_arr1 = [];
            $_str1 = '';
            $_str  = mb_convert_encoding($_str, "UTF-8", "BIG5,UTF-8");
            // 取得公司的代號
            if (false !== strpos($_str, 'GenFundCorpCombo(\'')) {
                $_arr = explode('\'', $_str);
                $rtn['com_num'] = $_arr[1];
            }

            // 取得日期
            if (false !== strpos($_str, 't11')) {
                $_str1 = leave_tag($_str, 'div');
                $_arr1 = explode('：', $_str1);
                $rtn['date'] = substr($_arr1[2], 0, 8);
            }
            // 取得買賣的名稱
            if (false !== strpos($_str, 'GenLink2stk(\'')) {
                $_arr1 = explode('\'', $_str);
                $_str1 = $_arr1[1];
                $flag_stock = substr($_str1, 2);
                $rtn['stock'][$flag_stock]['bos'] = '';
            } else if (false !== strpos($_str, 'Link2Stk(\'')) {
                $_arr = explode('\'', $_str);
                $flag_stock = $_arr[1];
                $rtn['stock'][$flag_stock]['bos'] = '';
            }

            // 取得買賣的明細
            if (false !== strpos($_str, 't3n1')) {
                $_str1 = leave_tag($_str, 'td');
                $_str1 = str_replace(',', '', $_str1);
                $rtn['stock'][$flag_stock]['bos'] .= $_str1 . ',';
                $flag_count++;
                if ($flag_count > 2) {
                    // 移除最後的逗點
                    $rtn['stock'][$flag_stock]['bos'] = substr($rtn['stock'][$flag_stock]['bos'], 0, strlen($rtn['stock'][$flag_stock]['bos']) - 1);
                    $flag_count = 0;
                }
            }
        }
        return $rtn;
    }


    /**
     * 將整理出來的資料存成txt檔
     * @param  array  $arr  整理好的陣列
     * @param  string $day  查詢的日期
     * @param  string $temp 從網頁上抓下來的資料
     * @return void
     */
    private function save_info($arr, $day, $temp)
    {
        $save_str  = '';
        $save_name = '';

        foreach ($this->aCom as $_key => $_data) {
            if ($arr['com_num'] == $_data['num']) {
                $save_name = $_key;
            }
        }

        // 當天抓不到資料
        if (!is_array($arr['stock'])) {
            return false;
        }

        $com_info = array(); //準備要計算持股量的陣列
        foreach ($arr['stock'] as $_num => $_data) {
            $save_str .= $_num . '#' . $_data['bos'] . '@';

            $_com_arr1 = explode(',', $_data['bos']);
            $com_info[$save_name][$_num]['buy']  += $_com_arr1[0];
            $com_info[$save_name][$_num]['sell'] += $_com_arr1[1];
            $com_info[$save_name][$_num]['bos']  += $_com_arr1[2];
        }

        if (!is_dir($this->sDataPath . "./stock/out_com_per/" . $save_name)) {
            mkdir($this->sDataPath . "./stock/out_com_per/" . $save_name);
        }
        if (!is_dir($this->sDataPath . "./stock/out_com_per/" . $save_name . "/txt_" . $day)) {
            mkdir($this->sDataPath . "./stock/out_com_per/" . $save_name . "/txt_" . $day);
        }
        if (!is_dir($this->sDataPath . "./stock/out_com_per/" . $save_name . "/html_" . $day)) {
            mkdir($this->sDataPath . "./stock/out_com_per/" . $save_name . "/html_" . $day);
        }

        $file = fopen($this->sDataPath . "./stock/out_com_per/" . $save_name . "/txt_" . $day . "/" . $save_name . "_" . $day . "_" . $arr['date'] . ".txt", "w");
        fwrite($file, $save_str);
        fclose($file);

        $file  = fopen($this->sDataPath . "./stock/out_com_per/" . $save_name . "/html_" . $day . "/" . $save_name . "_" . $day . "_" . $arr['date'] . ".html", "w");
        fwrite($file, $temp);
        fclose($file);

        // 計算外資持有量
        if ('1' == $day) {
            for ($cal_date = $arr['date']; nextday($arr['date'], 30, 'B') < $cal_date; $cal_date = nextday($cal_date, 1, 'B')) {
                $hold_file_name = $this->sDataPath . "./stock/out_com_per/" . $save_name . "/hold_stock/hold_stock_" . $cal_date . ".txt";
                if (file_exists($hold_file_name)) {
                    $handle   = fopen($hold_file_name, "r");
                    $contents = fread($handle, filesize($hold_file_name));
                    $_arr     = explode('@', $contents);
                    foreach ($_arr as $_stock_str) {
                        $_com_arr1 = explode('#', $_stock_str);
                        $_sno      = $_com_arr1[0];
                        if ('' == $_sno) {
                            continue;
                        }

                        $_arr_bos = explode(',', $_com_arr1[1]);
                        $_buy     = $_arr_bos[0];
                        $_sell    = $_arr_bos[1];
                        $_bos     = $_arr_bos[2];
                        $com_info[$save_name][$_sno]['buy']  += $_buy;
                        $com_info[$save_name][$_sno]['sell'] += $_sell;
                        $com_info[$save_name][$_sno]['bos']  += $_bos;
                    }

                    //把所有外資買賣量存起來,得到最後可能的持有量
                    foreach ($com_info as $com_name => $_data) {
                        $com_str = '';
                        foreach ($_data as $_sno => $_data1) {
                            $_buy     = $_data1['buy'];
                            $_sell    = $_data1['sell'];
                            $_bos     = $_buy - $_sell;
                            $com_str .= $_sno . '#' . $_buy . ',' . $_sell . ',' . $_bos . '@';
                        }
                        $com_str = substr($com_str, 0, strlen($com_str) - 1);

                        fwrite(fopen($this->sDataPath . "./stock/out_com_per/" . $com_name . "/hold_stock.txt", "w"), $com_str);
                        fclose($file);

                        $dir_path = $this->sDataPath . "./stock/out_com_per/" . $com_name . "/hold_stock/";
                        if (!is_dir($dir_path)) {
                            mkdir($dir_path);
                        }
                        $file = fopen($dir_path . "hold_stock_" . $arr['date'] . ".txt", "w");
                        fwrite($file, $com_str);
                        fclose($file);
                    }
                    break;
                }
            }
        }
    }


    /**
     * 取得外資每日持股量
     *
     * @return void
     */
    private function mOutHoldPrecent()
    {
        $ch      = $this->setCurl();
        // 如果get_type設定1的話,是抓每天最新的,2的話是依照設定的日期區間抓資料
        $begdate = date('Ymd', mktime(0, 0, 0, $this->sMM, $this->sDD - $this->mGetPreDay(), $this->sYY));
        $enddate = $this->sToday;
        if ($this->mGetGetType() != 1) {
            $begdate = $this->mGetBegdate();
            $enddate = $this->mGetEnddate();
        }

        // 存所有外資的資料
        //https://www.twse.com.tw/fund/MI_QFIIS?response=html&date=20171006&selectType=ALLBUT0999
        for ($cal_date = $begdate; $cal_date <= $enddate; $cal_date = nextday($cal_date)) {
            // 設定外資網址
            curl_setopt($ch, CURLOPT_URL, "https://www.twse.com.tw/fund/MI_QFIIS?response=html&selectType=ALLBUT0999&date=" . $cal_date);
            $this->mSaveOutHoldPercent(
                $this->mGetTwInfoD(explode("\n", curl_exec($ch))), 
                $cal_date
            );
            sleep(3);
        }
        // 關閉CURL連線
        curl_close($ch);
    }

    /**
     * 將整理出來的資料存成txt檔
     *
     * @param  array  $arr     整理好的陣列
     * @param  string $now_ymd yyyymm(dd)
     */
    private function mSaveOutHoldPercent($arr, $now_ymd)
    {
        if (empty($arr)) {
            return false;
        }

        if (!is_dir($this->sDataPath . "./stock/out_hold_percent/")){
            mkdir($this->sDataPath . "./stock/out_hold_percent/");
        }
        $file_path = $this->sDataPath . "./stock/out_hold_percent/txt_" . $now_ymd . ".txt";
        $contents  = '';
        if (file_exists($file_path)) {
            $file     = fopen($file_path, "r");
            $contents = fread($file, filesize($file_path));
        }
        $file     = fopen($file_path, "a");
        $save_str = '';
        foreach ($arr as $cnt => $str) {
            $_data = explode(',', $str);
            $_str  = $_data[0] . '#' . $_data[1] . ',' . $_data[2] . ',' . $_data[3] . ',' . $_data[4] . ',' . $_data[5] . ',' . $_data[6] . ',' . $_data[7] . ',' . $_data[8] . ',' . $_data[9] . ',' . $_data[10] . ',' . $_data[11];

            // 檢查這個股票料沒有存過,才要存入$save_str
            if (false === strpos($contents, $_data[0] . '#')) {
                $save_str = ('' == $save_str && '' == $contents) ? $_str : $save_str . '@' . $_str;
            }
        }
        fwrite($file, $save_str);
        fclose($file);
    }


    /**
     * 取得台股每日的收盤價
     *
     * @return void
     */
    private function mTwIndex()
    {
        $ch      = $this->setCurl();
        // 如果get_type設定1的話,是抓每天往前算$pre_day天最新的資料,2的話是依照設定的日期區間抓資料
        $begdate = date('Ymd', mktime(0, 0, 0, $this->sMM, $this->sDD - $this->mGetPreDay(), $this->sYY));
        $enddate = $this->sToday;
        if ($this->mGetGetType() != 1) {
            $begdate = $this->mGetBegdate();
            $enddate = $this->mGetEnddate();
        }

        // 存所有外資的資料
        //https://www.twse.com.tw/exchangeReport/FMTQIK?response=html&date=20170911
        for ($cal_date = monbegdate($begdate); $cal_date <= $enddate; $cal_date = nextmont($cal_date)) {   
            // 設定外資網址
            curl_setopt($ch, CURLOPT_URL, "https://www.twse.com.tw/exchangeReport/FMTQIK?response=html&date=" . $cal_date);
            $this->mSaveTwIndex(
                $this->mGetTwInfo(
                    explode("\n", curl_exec($ch)), 
                    6, 
                    $cal_date
                ), 
                $cal_date
            );
            sleep(3);
        }
        // 關閉CURL連線
        curl_close($ch);
    }

    /**
     * 將整理出來的資料存成txt檔
     * @param  array  $arr     整理好的陣列
     * @param  string $now_ym  yyyymm(dd)
     */
    private function mSaveTwIndex($arr, $now_ym)
    {
        if (!is_dir($this->sDataPath . "./stock/tw_index/")) {
            mkdir($this->sDataPath . "./stock/tw_index/");
        }

        $file_path  = $this->sDataPath . "./stock/tw_index/txt_" . substr($now_ym, 0, 4) . ".txt";
        $contents   = '';
        if (file_exists($file_path)) {
            $file     = fopen($file_path, "r");
            $contents = fread($file, filesize($file_path));
        }
        $file     = fopen($file_path, "a");
        $save_str = '';
        foreach ($arr as $_date => $_data) {
            $this_date = implode('', explode('/', $_date));
            $_str      = $_data[1] . ',' . $_data[2] . ',' . $_data[3] . ',' . $_data[4] . ',' . $_data[5];

            // 檢查這個日期料沒有存過,才要存入$save_str
            if (false === strpos($contents, $this_date . '=')) {
                $save_str = ('' == $save_str && '' == $contents) ? $this_date . '=' . $_str : $save_str . '@' . $this_date . '=' . $_str;
            }
        }
        fwrite($file, $save_str);
        fclose($file);
    }

    /**
     * 取得三大法人買賣量
     * @return void
     */
    private function mThreebigBos()
    {
        $ch      = $this->setCurl();
        // 如果get_type設定1的話,是抓每天往前算$pre_day天最新的資料,2的話是依照設定的日期區間抓資料
        $begdate = date('Ymd', mktime(0, 0, 0, $this->sMM, $this->sDD - $this->mGetPreDay(), $this->sYY));
        $enddate = $this->sToday;
        if ($this->mGetGetType() != 1) {
            $begdate = $this->mGetBegdate();
            $enddate = $this->mGetEnddate();
        }

        // 存所有外資的資料
        //https://www.twse.com.tw/fund/T86?response=html&date=20170908&selectType=ALLBUT0999
        for ($cal_date = $begdate; $cal_date <= $enddate; $cal_date = nextday($cal_date)) {
            // 設定外資網址
            curl_setopt($ch, CURLOPT_URL, "https://www.twse.com.tw/fund/T86?response=html&selectType=ALLBUT0999&date=" . $cal_date);
            $this->mSaveThreebigBos(
                $this->mGetTwInfoD(explode("\n", curl_exec($ch))),
                $cal_date
            );
            sleep(3);
        }
        // 關閉CURL連線
        curl_close($ch);
    }

    /**
     * 將整理出來的資料存成txt檔
     *
     * @param  array  $arr     整理好的陣列
     * @param  string $now_ymd yyyymm(dd)
     */
    private function mSaveThreebigBos($arr, $now_ymd)
    {
        if (empty($arr)) {
            return false;
        }
        if (!is_dir($this->sDataPath . "./stock/threebig_bos/")) {
            mkdir($this->sDataPath . "./stock/threebig_bos/");
        }

        $file_path = $this->sDataPath . "./stock/threebig_bos/txt_" . $now_ymd . ".txt";
        $contents  = '';
        if (file_exists($file_path)) {
            $file     = fopen($file_path, "r");
            $contents = fread($file, filesize($file_path));
        }
        $file     = fopen($file_path, "a");
        $save_str = '';
        foreach ($arr as $cnt => $str) {
            $_data = explode(',', $str);
            $_str  = $_data[0] . '#' . $_data[1] . ',' . $_data[2] . ',' . $_data[3] . ',' . $_data[4] . ',' . $_data[5] . ',' . $_data[6] . ',' . $_data[7] . ',' . $_data[8] . ',' . $_data[9] . ',' . $_data[10] . ',' . $_data[11] . ',' . $_data[12] . ',' . $_data[13] . ',' . $_data[14] . ',' . $_data[15];

            // 檢查這個股票料沒有存過,才要存入$save_str
            if (false === strpos($contents, $_data[0] . '#')) {
                $save_str = ('' == $save_str && '' == $contents) ? $_str : $save_str . '@' . $_str;
            }
        }
        fwrite($file, $save_str);
        fclose($file);
    }


    /**
     * 取得外資有的股票每日的價格
     *
     * @return void
     */
    private function mStockPrice()
    {
        $ch      = $this->setCurl();
        // 如果get_type設定1的話,是抓每天往前算$pre_day天最新的資料,2的話是依照設定的日期區間抓資料
        $begdate = date('Ymd', mktime(0, 0, 0, $this->sMM, $this->sDD - $this->mGetPreDay(), $this->sYY));
        $enddate = $this->sToday;
        if ($this->mGetGetType() != 1) {
            $begdate = $this->mGetBegdate();
            $enddate = $this->mGetEnddate();
        }

        $type_last_date = $this->mGetGainTypeLastdate();

        // 存所有外資的資料,每次查詢是一個月的資料
        //https://www.twse.com.tw/exchangeReport/STOCK_DAY?response=html&date=20170909&stockNo=6412
        for ($cal_date = monbegdate($begdate); $cal_date <= $enddate; $cal_date = nextmont($cal_date)) {
            // 取得外資持股的股票代號
            $file_path = $this->sDataPath . "./stock/threebig_bos/txt_" . $type_last_date['mThreebigBos'] . ".txt";
            $_data     = explode('@', fread(fopen($file_path, "r"), filesize($file_path)));
            foreach ($_data as $_str) {
                $_url = "https://www.twse.com.tw/exchangeReport/STOCK_DAY?response=html&date=" . $cal_date . "&stockNo=" . explode('#', $_str)[0];
                // 設定外資網址
                curl_setopt($ch, CURLOPT_URL, $_url);
                $this->mSaveStockPrice(
                    $this->mGetTwInfo(
                        explode("\n", curl_exec($ch)), 
                        9, 
                        $cal_date
                    ), 
                    explode('#', $_str)[0], 
                    $cal_date
                );
                sleep(5);
            }
        }

        // 關閉CURL連線
        curl_close($ch);
    }


    /**
     * 將整理出來的資料存成txt檔
     *
     * @param  array  $arr  整理好的陣列
     */
    private function mSaveStockPrice($arr, $sno, $now_ym)
    {
        if (!is_dir($this->sDataPath . "./stock/stock_price_s/")) {
            mkdir($this->sDataPath . "./stock/stock_price_s/");
        }
        if (!is_dir($this->sDataPath . "./stock/stock_price_s/" . $sno)) {
            mkdir($this->sDataPath . "./stock/stock_price_s/" . $sno);
        }

        $file_path_s = $this->sDataPath . "./stock/stock_price_s/" . $sno . "/txt_" . substr($now_ym, 0, 4) . ".txt";
        $file_s      = fopen($file_path_s, "a+");
        $contents_s  = '';
        if (filesize($file_path_s) > 0) {
            $contents_s = fread($file_s, filesize($file_path_s));
        }
        $save_str = '';
        foreach ($arr as $_date => $_data) {
            $this_date = implode('', explode('/', $_date));
            if (!is_dir($this->sDataPath . "./stock/stock_price_d/")) {
                mkdir($this->sDataPath . "./stock/stock_price_d/");
            }
            $file_path  = $this->sDataPath . "./stock/stock_price_d/txt_" . $this_date . ".txt";
            $file       = fopen($file_path, "a+");
            $contents   = '';
            if (filesize($file_path) > 0) {
                $contents = fread($file, filesize($file_path));
            }
            $_str = $sno . '#' . $_data[1] . ',' . $_data[2] . ',' . $_data[3] . ',' . $_data[4] . ',' . $_data[5] . ',' . $_data[6] . ',' . $_data[7] . ',' . $_data[8];
            // 已經有存過了,就不要再處理了
            if (false === strpos($contents, $sno . '#')) {
                // 不是第一筆資料,要加上串街符號
                fwrite($file, ('' != trim($contents)) ? '@' . $_str : $_str );
            }
            fclose($file);

            // 檢查這個日期料沒有存過,才要存入$save_str
            if (false === strpos($contents_s, $this_date . '=')) {
                $save_str = ('' == $save_str && '' == $contents_s) ? $this_date . '=' . $_str : $save_str . '@' . $this_date . '=' . $_str;
            }
        }
        fwrite($file_s, $save_str);
        fclose($file_s);
    }


    /**
     * 重新計算持股量
     *
     * @return void
     */
    private function mGetHoldStock()
    {
        //取得所有知道的外資的買賣量
        $com_info  = [];
        foreach ($this->aCom as $com_name => $_data) {
            $root_file_path = $this->sDataPath . './stock/out_com_per/' . $com_name . '/txt_1/';
            if (!is_dir($root_file_path)) {
                continue;
            }
            $everyday_filename = scandir($root_file_path);

            foreach ($everyday_filename as $_file_path) {
                if ('.' == $_file_path || '..' == $_file_path) {
                    continue;
                }

                $_arr  = explode('_', substr($_file_path, 0, strlen($_file_path) - 4));
                $_date = $_arr[count($_arr) - 1];

                $file_path = $root_file_path . $_file_path;
                $handle    = fopen($file_path, "r");
                if (0 == filesize($file_path)) {
                    continue;
                }
                $contents  = fread($handle, filesize($file_path));
                $_arr      = explode('@', $contents);
                foreach ($_arr as $_stock_str) {
                    $_com_arr1 = explode('#', $_stock_str);
                    $_sno      = $_com_arr1[0];
                    if ('' == $_sno) {
                        continue;
                    }

                    $_arr_bos = explode(',', $_com_arr1[1]);
                    $com_info[$com_name][$_sno]['buy']  += $_arr_bos[0];
                    $com_info[$com_name][$_sno]['sell'] += $_arr_bos[1];
                }

                // 存當天的外資持股量
                $com_str = '';
                foreach ($com_info[$com_name] as $_sno => $_data1) {
                    $com_str .= $_sno . '#' . $_data1['buy'] . ',' . $_data1['sell'] . ',' . ($_data1['buy'] - $_data1['sell']) . '@';
                }
                $com_str   = substr($com_str, 0, strlen($com_str) - 1);
                $dir_path  = $this->sDataPath . "./stock/out_com_per/" . $com_name . "/hold_stock/";
                if (!is_dir($dir_path)) {
                    mkdir($dir_path);
                }
                $file = fopen($dir_path . "hold_stock_" . $_date . ".txt", "w");
                fwrite($file, $com_str);
                fclose($file);
            }
        }

        //把所有外資買賣量存起來,得到最後可能的持有量
        foreach ($com_info as $com_name => $_data) {
            $com_str = '';
            foreach ($_data as $_sno => $_data1) {
                $com_str .= $_sno . '#' . $_data1['buy'] . ',' . $_data1['sell'] . ',' . ($_data1['buy'] - $_data1['sell']) . '@';
            }
            $com_str = substr($com_str, 0, strlen($com_str) - 1);
            $file    = fopen($this->sDataPath . "./stock/out_com_per/" . $com_name . "/hold_stock.txt", "w");
            fwrite($file, $com_str);
            fclose($file);
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
    function mGetTwInfo($arr, $col_num, $now_ym = '')
    {
        $rtn = [];
        $cal_col_num = 0;
        foreach ($arr as $_str) {
            if ('' == trim($_str)) {
                continue;
            }

            // 取得公司的代號
            if ($cal_col_num > 0) {
                $rtn[$_date][$cal_col_num] = str_replace(',', '', leave_tag($_str, 'td'));
                $cal_col_num++;

                if ($cal_col_num > ($col_num - 1)) {
                    $cal_col_num = 0;
                }
            } else if ($cal_col_num == 0 && false !== strpos($_str, '<td>' . (substr($now_ym, 0, 4) - 1911) . '/' . substr($now_ym, 4, 2))) {
                $_arr  = explode('/', leave_tag($_str, 'td'));
                $_date = ($_arr[0] + 1911) . '/' . $_arr[1] . '/' . $_arr[2];

                $rtn[$_date][$cal_col_num] = $_date;
                $cal_col_num = 1;
            }
        }
        return $rtn;
    }

    /**
     * 取得券商資訊
     * @param  array  $arr     從html拆解出來的陣列
     * @return array $rtn 要新增成txt檔的陣列
     */
    function mGetTwInfoD($arr)
    {
        $rtn         = [];
        $cal_col_num = 0;
        $row_str     = '';
        foreach ($arr as $_str) {
            if ('' == trim($_str)){
                continue;
            } 
            if (false !== strpos($_str, '<tbody>')) {
                $chk_tbody = 'Y';
            }
            if (false !== strpos($_str, '</tbody>')) {
                $chk_tbody = 'N';
            }
            if ('Y' == $chk_tbody && false !== strpos($_str, '<tr>')) {
                $chk_tr    = 'Y';
            }
            if ('Y' == $chk_tbody && false !== strpos($_str, '</tr>')) {
                $chk_tr    = 'N';
            }
            // 如果已經到檔案結尾的話，就跳出了，後面沒有資訊要存
            if ('N' == $chk_tbody) {
                break;
            }

            // 取得公司的代號
            if ('Y' == $chk_tr && false !== strpos($_str, '<td>')) {
                $_str1   = trim(str_replace(',', '', leave_tag($_str, 'td')));
                $row_str = ('' == $row_str) ? $_str1 : $row_str . ',' . $_str1;
            } else if ('N' == $chk_tr && false !== strpos($_str, '</tr>')) {
                $rtn[$cal_col_num] = $row_str;
                $row_str = '';
                $cal_col_num++;
            }
        }
        return $rtn;
    }

    /**
     * 抓取摩根士丹利最常投資的股票
     * /txt/stock/out_com_per/morganstanley/stock_info.txt
     *
     * @return void 
     */
    public function mGetMorLoveSotck()
    {
        $get_stock  = new cGetStockInfo();
        $rtn        = [];
        $com_type   = ['morganstanley'];
        $mor_data   = $get_stock->mGetMorData([
                'first_date' => (substr($this->sToday, 0, 4) - 1) . '0101',
                'last_date'  =>$this->sToday
            ], 
            $com_type
        );
        $stock_info = $get_stock->mGetOutPercent([
            'first_date' => '',
            'last_date'  => $this->sToday
        ]);

        $out_hold = [];
        foreach ((array)$stock_info['hold'] as $_sno => $_data) {
            foreach ($_data as $_date => $_data1) {
                $out_hold[$_sno] = $_data1;
            }
        }

        $sno_cnt = [];
        $sno_max = [];
        $sno_sum = [];
        $sno_lv  = [];
        //整理從摩根抓出來的資料
        foreach ($mor_data as $_sno => $_data) {
            foreach ($_data['morganstanley'] as $_date => $_data1) {
                $sno_cnt[$_sno]['cnt']++;
                //取得最新的發行股數
                $sno_cnt[$_sno]['all_stock'] = $out_hold[$_sno]['all_stock'];
                //目前只記錄正數的資料
                foreach ($this->aBosType as $_bos_str) {
                    $_bos = $_data1[1][$_bos_str];
                    if ($_bos > $sno_max[$_sno][$_bos_str]) {
                        $sno_max[$_sno][$_bos_str] = $_bos;
                    }
                    $sno_sum[$_sno][$_bos_str] += $_bos;
                    $_bos_lv = $this->mGetBuyLevel($_bos);
                    $sno_lv[$_sno][$_bos_str]['cnt'][$_bos_lv]++;
                    //記錄正數的數量
                    if ($_bos > 0) {
                        $sno_lv[$_sno][$_bos_str]['pos_cnt']++;
                    }
                }
            }
        }

        $arr_cnt = 0;
        foreach ($sno_cnt as $_sno => $_data) {
            $_cnt = $_data['cnt'];
            $rtn[$arr_cnt]['sno']  = $_sno;
            $rtn[$arr_cnt]['cnt']  = $_cnt;
            $rtn[$arr_cnt]['name'] = $stock_info['info'][$_sno]['name'];
            $rtn[$arr_cnt]['all_stock']  = $_data['all_stock'];
            $rtn[$arr_cnt]['stock_type'] = $this->mGetCapitalDtock($_data['all_stock']);
            //買/賣/買減賣在這邊處理成依照級距記錄買量的百分比
            foreach ($this->aBosType as $_bos_str) {
                $rtn[$arr_cnt]['max_' . $_bos_str] = $sno_max[$_sno][$_bos_str];
                $rtn[$arr_cnt]['sum_' . $_bos_str] = $sno_sum[$_sno][$_bos_str];
                $rtn[$arr_cnt]['avg_' . $_bos_str] = ceil($sno_sum[$_sno][$_bos_str] / $_cnt);
                $_pos_cnt  = $sno_lv[$_sno][$_bos_str]['pos_cnt'];
                $_arr      = [];
                $_chk_per1 = 0;
                $_chk_per2 = 0;
                //$i從9開始,先不看小於0的部分
                for ($i = 9; $i <= 16; $i++) {
                    $_lv = $sno_lv[$_sno][$_bos_str]['cnt'][$i];

                    if ('' != $_lv && $_pos_cnt > 0) {
                        $_per = (round(($_lv / $_pos_cnt), 2) * 100);
                        $_chk_per2 += $_per;
                        $_chk_str   = '';
                        //加總後百分比落在25-75之間 or 加總前小於等於25加總後大於等於25 or 加總前介於25-75且加總後大於等於75
                        $_chk_1 = (25 <= $_chk_per2 && $_chk_per2 <= 75);
                        $_chk_2 = ($_chk_per1 <= 25 && $_chk_per2 >= 25);
                        $_chk_3 = (25 <= $_chk_per1 && $_chk_per1 <= 75 && $_chk_per2 >= 75);
                        //(✔)表示是主要操作的範圍
                        if ($_chk_1 || $_chk_2 || $_chk_3) {
                            $_chk_str = '(✔)';
                        }
                        $_chk_per1 = $_chk_per2;
                        $_arr[$_bos_str . '_lv'][$i] = $_per . '%' . $_chk_str;
                    }
                }
                $rtn[$arr_cnt][$_bos_str . '_lv'] = $_arr[$_bos_str . '_lv'];
            }
            $arr_cnt++;
        }
        //排序,操作最多次的排最前面
        for ($i = 0; $i < $arr_cnt; $i++) {
            for ($j = 0; $j < $arr_cnt; $j++) {
                if ($i != $j && $rtn[$j]['cnt'] < $rtn[$i]['cnt']) {
                    $_arr    = $rtn[$i];
                    $rtn[$i] = $rtn[$j];
                    $rtn[$j] = $_arr;
                }
            }
        }

        //將資料存到txt檔,這樣要抓資料的時候就不用重新計算一次了
        $save_str = '';
        foreach ($rtn as $_cnt => $_data) {
            $_data_str = '';
            foreach ($_data as $_key => $_val) {
                if ('sno' == $_key) {
                    continue;
                }
                if (false !== strpos($_key, '_lv')) {
                    $_lv_str = '';
                    foreach ((array)$_val as $_lv => $_per) {
                        $_lv_str .= ';' . $_lv . '!' . $_per;
                    }
                    $_data_str .= ',' . $_key . '=' . substr($_lv_str, 1);
                } else {
                    $_data_str .= ',' . $_key . '=' . $_val;
                }
            }
            $_data_str = substr($_data_str, 1);
            $save_str  = $save_str . '@' . $_data['sno'] . '#' . $_data_str;
        }
        $file = fopen($this->sDataPath . "./stock/out_com_per/morganstanley/stock_info.txt", "w");
        fwrite($file, substr($save_str, 1));
        fclose($file);
    }

    /**
     * 買入張數的級距計算器
     * @param  int $buy 買入的股票的張數
     * @return array $rtn
     */
    public function mGetBuyLevel($buy)
    {
        if       (-20000 > $buy) {
            return 1;
        } elseif (-10000 > $buy) {
            return 2;
        } elseif (-5000  > $buy) {
            return 3;
        } elseif (-2000  > $buy) {
            return 4;
        } elseif (-1000  > $buy) {
            return 5;
        } elseif (-500   > $buy) {
            return 6;
        } elseif (-100   > $buy) {
            return 7;
        } elseif (0      > $buy) {
            return 8;
        } elseif (100    > $buy) {
            return 9;
        } elseif (500    > $buy) {
            return 10;
        } elseif (1000   > $buy) {
            return 11;
        } elseif (2000   > $buy) {
            return 12;
        } elseif (5000   > $buy) {
            return 13;
        } elseif (10000  > $buy) {
            return 14;
        } elseif (20000  > $buy) {
            return 15;
        } else                   {
            return 16;
        }
    }

    /**
     * 判斷股本是小型中型還是大型
     * @param  int $all_stock 發行的股數
     * @return string $rtn S=小型股,M=中型股,L=大型股
     */
    public function mGetCapitalDtock($all_stock)
    {
        //股本小於1億屬於小型股
        if ($all_stock < 100000000) {
            $rtn = 'S';
        //股本在10-50億屬於中型股
        } elseif ($all_stock < 500000000){
            $rtn = 'M';
        //股本在50億以上屬於大型股
        } else {
            $rtn = 'L';
        }
        return $rtn;
    }

    /**
     * 抓每種資料的最後更新日期
     * @return array $rtn[每種狀態] 最後更新日期
     */
    public function mGetGainTypeLastdate()
    {
        $type_date = [];
        foreach ($this->mGetAllGainType() as $_type => $_str) {
            $_file_path = $this->sDataPath;
            switch ($_type) {
                case 'mGetMorInfo':
                    $_file_path .= './stock/out_com_per/morganstanley/txt_1/';
                    break;
                case 'mOutHoldPrecent':
                    $_file_path .= './stock/out_hold_percent/';
                    break;
                case 'mTwIndex':
                    $_file_path .= './stock/tw_index/';
                    break;
                case 'mThreebigBos':
                    $_file_path .= './stock/threebig_bos/';
                    break;
                case 'mStockPrice':
                    $_file_path .= './stock/stock_price_d/';
                    break;
                default:
                    $_file_path = '';
                    break;
            }
            if (!is_dir($_file_path)) {
                continue;
            }
            $_arr_filename = scandir($_file_path);
            rsort($_arr_filename);
            $_arr  = explode('.', $_arr_filename[0]);
            $_date = ('mTwIndex' != $_type) ? substr($_arr[0], -8) : substr($_arr[0], -4);
            if ($_date > $type_date[$_type]) {
                $type_date[$_type] = $_date;
            }
        }

        $_file_path = $this->sDataPath . './stock/tw_index/txt_' . $type_date['mTwIndex'] . '.txt';
        if ('' != $type_date['mTwIndex'] && file_exists($_file_path)) {
            $_handle    = fopen($_file_path, "r");
            $_contents  = fread($_handle, filesize($_file_path));
            $_arr       = explode('@', $_contents);
            rsort($_arr);
            $_arr1 = explode('=', $_arr[0]);
            if ($_arr1[0] > $type_date['mTwIndex']) {
                $type_date['mTwIndex'] = $_arr1[0];
            }
        }
        
        return $type_date;
    }
}
