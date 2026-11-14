<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once dirname(__DIR__).'/usage-log.php';
echo 'Expired usage log files removed: '.usagePrune().PHP_EOL;
