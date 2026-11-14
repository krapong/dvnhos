<?php
// This file is used by PHP's development server to restrict public endpoints.
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(!in_array($path,['/','/index.php','/api.php','/manual.php','/risk.php','/login.php','/logout.php','/usage.php','/usage-history.php'],true)){
    http_response_code(404);exit('Not found.');
}
require __DIR__.($path==='/'?'/index.php':$path);
