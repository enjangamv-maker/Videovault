<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

putenv('DB_NAME=videovault_test');

$ref = new ReflectionClass(\App\Core\Env::class);
$prop = $ref->getProperty('vars');
$prop->setAccessible(true);
$vars = $prop->getValue();
$vars['DB_NAME'] = 'videovault_test';
$prop->setValue(null, $vars);

ini_set('session.use_cookies', '0');
ini_set('session.use_only_cookies', '0');
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}
