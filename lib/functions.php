<?php

	/**
	 * @param $val
	 *
	 * @return array|string
	 */
	function xssproof($val)
	{

		if(is_array($val))
			foreach($val as $k => $v)
				$val[$k] = xssproof($v);
		else
			$val = strip_tags(@trim($val));

		return $val;

	} // function xssproof($val)


	/**
	 * Format Date to DB-Ready Date
	 *
	 * @param $date
	 *
	 * @return false|string
	 */
	function dateToDBDate($date)
	{

		if (is_numeric($date))
		{
			return date('Y-m-d', $date);
		}
		else if (preg_match('/\d{2}\.\d{2}\.\d{4}/', $date))
		{
			$arDate = explode('.', $date);

			return $arDate[2].'-'.$arDate[1].'-'.$arDate[0];
		}
		else if (strtotime($date) > 0)
		{
			return date('Y-m-d', strtotime($date));
		}

		return '0000-00-00';

	} // function dateToDBDate($date)


	/**
	 * Format DB Date to Output-Ready Date
	 *
	 * @param      $date
	 * @param bool $dateOnly
	 *
	 * @return false|string
	 */
	function DBDatetoDate($date, $dateOnly = true)
	{

		if ($date == '0000-00-00') return '';

		if ($dateOnly && strtotime($date) != 0) return date('d.m.Y', strtotime($date));
		else if (strtotime($date) != 0) return date('d.m.Y H:i:s', strtotime($date));

	} // function DBDatetoDate($date, $dateOnly)


	/**
	 * @param $del
	 * @param $val
	 *
	 * @return array
	 */
	function deep_explode($del, $val)
	{

		if(is_array($val) && !arrayHasSizedElements($val)) $val = "";
		$arVal = explode($del, $val);

		foreach($arVal as $k => $v)
		{
			if(trim($v) == "") unset($arVal[$k]);
		}

		return $arVal;

	} // funtion deep_explode($del, $val)

	/**
	 * @param $del
	 * @param $ar
	 *
	 * @return string
	 */
	function deep_implode($del, $ar)
	{

		foreach($ar as $k => $v)
		{
			if(trim($v) == '') unset($ar[$k]);
		}

		return implode($del, $ar);

	} // function deep_implode($del, $ar)


	/**
	 * Removes Empty Elements in $val Array
	 *
	 * @param        $val
	 * @param string $clearEntry
	 *
	 * @return array|string
	 */
	function deep_trim($val, $clearEntry = '')
	{

		if(!is_array($clearEntry)) $testClearEntry = array($clearEntry);
		else $testClearEntry = $clearEntry;

		if(is_array($val))
		{
			foreach($val as $k => $v)
			{
				$val[$k] = deep_trim($v, $clearEntry);
				if($clearEntry !== false && in_array($val[$k], $testClearEntry)) unset($val[$k]);
			}
		}
		else
		{
			$val = trim($val);
		}

		return $val;

	} // function deep_trim($val, $clearEntry)


	function debug($val)
	{

		if(is_array($val) || is_object($val))
		{

			echo '<pre class="posMessage debug">';
			print_r($val);
			echo '</pre>';

		} else {

			echo '<pre class="negMessage debug">'.var_dump($val).'</pre>';

		}

	} // function debug($val)

	function activate_debug_mode()
	{

		error_reporting(E_ALL | E_WARNING | E_NOTICE);
		ini_set('display_errors', 1);

		flush();

	} // activate_debug_mode()

	function isset_true(&$val)
	{

		return (isset($val) && $val === true);

	} // function isset_true($val)

	function isSizedDouble(&$val)
	{

		if(!isset($val)) return false;

		$dVal = doubleval($val);

		if($dVal <= 0) return false;

		return true;

	} // function isSizedDouble($val)


	function isSized(&$val)
	{

		if(isset($val) && $val > 0) return true;

		return false;

	} // function isSized($val)


	function isSizedInt(&$int, $val = false)
	{

		$isset = true;

		if(!isset($int) || !is_numeric($int)) $isset = false;
		else if($int <= 0) $isset = false;

		if($isset === true && $val !== false)
		{
			if($val == $int) return true;
			else return false;
		}

		return $isset;

	} // function isSizedInt($int, $val)


	function isSizedArray(&$array, $size = 1)
	{

		if(isset($array) && is_array($array) && count($array) >= $size)
		{
			return true;
		}

		return false;

	} // function isSizedArray($array, $val)


	function isSizedString(&$val)
	{

		if(!isset($val)) return false;
		if(is_int($val)) return false;
		if(gettype($val) !== 'string') return false;
		if(strlen($val) <= 0) return false;

		return true;

	} // function isSizedString($val)
