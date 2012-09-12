<?php

$app=require __DIR__.'/lib/base.php';

$app->set('AUTOLOAD','inc/;app/;inc/temp/');
$app->set('CACHE',FALSE);
$app->set('DEBUG',3);
$app->set('EXTEND',TRUE);
$app->set('EXTERNAL','bin/');
$app->set('FONTS','fonts/');
$app->set('GUI','gui/');
$app->set('LOCALES','dict/');
$app->set('LOG','log/');
$app->set('PROXY',TRUE);
$app->set('TEMP','temp/');

$app->set('timer',microtime(TRUE));

$app->config('inc/config.ini');

$app->route('GET /error','Main->ehandler');
$app->route('GET /order', 'controller\order->indexAction');
$app->route('POST /order/create', 'controller\order->createAction');
$app->route('GET /order/error', 'controller\order->errorAction');
$app->route('GET /order/pay', 'controller\order->payAction');
$app->route('GET /order/success', 'controller\order->successAction');

$app->route('POST /robokassa/result', 'controller\robokassa->resultAction');
$app->route('POST /robokassa/success', 'controller\robokassa->successAction');
$app->route('POST /robokassa/error', 'controller\robokassa->errorAction');
$app->route('GET /captcha',
	function() {
		Graphics::captcha(180,60,5);
	}
);

$app->route('GET /min',
	function() {
		Web::minify($_GET['base'],explode(',',$_GET['files']));
	},
	0
);


$app->run();