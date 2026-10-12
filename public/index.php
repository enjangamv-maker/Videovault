<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

\App\Core\App::boot();

$router = require BASE_PATH . '/routes/web.php';
$router->dispatch();
