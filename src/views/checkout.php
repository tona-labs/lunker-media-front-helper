<?php 
$reservation = reservate($_POST);
if(is_null($reservation)) {
	$url = config('app.url').'/payment-uncompleted';
	header('location:'.$url);
	return;
}
pay($reservation);
?>