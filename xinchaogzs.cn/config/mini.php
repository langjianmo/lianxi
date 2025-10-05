<?php
/**
 * 小程序配置
 * mini.php
 * @author houguang<houguang@sina.cn>
 * @date 2020-07-15 1:05
 * @description
 */

return [
	'app_id'        => "wx40bd73e3ad5e0fca",
	'secret'        => "4cfe6076f13695ebb905bb6c61ceac8b",
	'mch_id'        => "1659481168",
	'key'           => "qwertyuiop1234567890asdfghjklzxc",
	"notify_url"    => (string)url("api/notify/index", [], null, true),
	// 'cert_path'     => app()->getRootPath() . '/extend/1659481168/apiclient_cert.pem',
	// 'key_path'      => app()->getRootPath() . '/extend/1659481168/apiclient_key.pem',
	'cert_path'     => app()->getRootPath() . '/extend/wx/cert/apiclient_cert.pem',
	'key_path'      => app()->getRootPath() . '/extend/wx/cert/apiclient_key.pem',
	'response_type' => 'array',
	'log'           => [
		'default'  => 'debug',
		'channels' => [
			'dev'  => [
				'driver' => 'single',
				'path'   => app()->getRuntimePath() . '/log/' . date("Ym") . '/easywechat.log',
				'level'  => 'debug',
			],
			'prod' => [
				'driver' => 'daily',
				'path'   => app()->getRuntimePath() . '/log/' . date("Ym") . '/easywechat_'.date("d").'.log',
				'level'  => 'info',
			],
		],
	],
	'http'          => [
		'max_retries' => 1,
		'retry_delay' => 500,
		'timeout'     => 90,
	],
];