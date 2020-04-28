<?php

class MorInfo
{
	public static function getMorInfo()
	{
		$arr_com = array();
		/* 設定外資資訊的地方
		get_mor_info.php 與 stock.php會用到 
		*/
		$arr_com['morganstanley']['num']  = '1470'; //台灣摩根士丹利
		$arr_com['goldmansachs']['num']   = '1480'; //美商高盛
		$arr_com['maylin']['num']         = '1440'; //美林
		$arr_com['singapore']['num']      = '1650'; //新加坡商瑞銀
		$arr_com['germanreich']['num']    = '1530'; //港商德意志
		$arr_com['macquarie']['num']      = '1360'; //港商麥格理
		$arr_com['nomura']['num']         = '1560'; //港商野村
		$arr_com['creditsuisse']['num']   = '1520'; //瑞士信貸
		$arr_com['JPmorganchase']['num']  = '8440'; //摩根大通
		$arr_com['citigroup']['num']      = '1590'; //花旗環球

		$arr_com['morganstanley']['name'] = '台灣摩根士丹利券商';
		$arr_com['goldmansachs']['name']  = '美商高盛券商';
		$arr_com['maylin']['name']        = '美林券商';
		$arr_com['singapore']['name']     = '新加坡商瑞銀券商';
		$arr_com['germanreich']['name']   = '港商德意志券商';
		$arr_com['macquarie']['name']     = '港商麥格理券商';
		$arr_com['nomura']['name']        = '港商野村券商';
		$arr_com['creditsuisse']['name']  = '瑞士信貸券商';
		$arr_com['JPmorganchase']['name'] = '摩根大通券商';
		$arr_com['citigroup']['name']     = '花旗環球券商';

		return $arr_com;
	}
	public static function getComLong()
	{
		return array('morganstanley','singapore','goldmansachs','maylin','germanreich','citigroup');
	}

	public static function getComShort()
	{
		return array('macquarie','nomura','creditsuisse','JPmorganchase');
	}

}




