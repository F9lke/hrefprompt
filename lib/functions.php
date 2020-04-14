<?php
 
	function hrefp_dateToDBDate($date)
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

	} // function hrefp_dateToDBDate($date)

	function hrefp_debug($val)
	{

		if(is_array($val) || is_object($val))
		{

			echo '<pre class="posMessage debug">';
			print_r($val);
			echo '</pre>';

		} else {

			echo '<pre class="negMessage debug">'.var_dump($val).'</pre>';

		}

	} // function hrefp_debug($val)

	function hrefp_activate_debug_mode()
	{

		error_reporting(E_ALL | E_WARNING | E_NOTICE);
		ini_set('display_errors', 1);

		flush();

	} // function hrefp_activate_debug_mode()

	function hrefp_isset_true(&$val)
	{

		return (isset($val) && $val === true);

	} // function hrefp_isset_true($val)

	function hrefp_isSizedArray(&$array, $size = 1)
	{

		if(isset($array) && is_array($array) && count($array) >= $size)
		{
			return true;
		}

		return false;

	} // function hrefp_isSizedArray($array, $val)

	function hrefp_isSizedString(&$val)
	{

		if(!isset($val)) return false;
		if(is_int($val)) return false;
		if(gettype($val) !== 'string') return false;
		if(strlen($val) <= 0) return false;

		return true;

	} // function hrefp_isSizedString($val)
