<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);
define('APP_START', microtime(true));

require_once BASE_PATH . '/app/Core/Env.php';
require_once BASE_PATH . '/app/Core/Logger.php';
require_once BASE_PATH . '/app/Core/Database.php';
require_once BASE_PATH . '/app/Core/Session.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
require_once BASE_PATH . '/app/Core/Request.php';
require_once BASE_PATH . '/app/Core/Response.php';
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Router.php';
require_once BASE_PATH . '/app/Core/App.php';
require_once BASE_PATH . '/app/Core/RateLimiter.php';
require_once BASE_PATH . '/app/Core/Audit.php';
require_once BASE_PATH . '/app/Support/Str.php';
require_once BASE_PATH . '/app/Support/Flash.php';
require_once BASE_PATH . '/app/Exceptions/ValidationException.php';
require_once BASE_PATH . '/app/Exceptions/NotFoundException.php';
require_once BASE_PATH . '/app/Services/UploadService.php';
require_once BASE_PATH . '/app/Services/MailerService.php';
require_once BASE_PATH . '/app/Services/AuthService.php';
require_once BASE_PATH . '/app/Services/VideoService.php';
require_once BASE_PATH . '/app/Services/TranscodeService.php';
require_once BASE_PATH . '/app/Models/User.php';
require_once BASE_PATH . '/app/Models/Video.php';
require_once BASE_PATH . '/app/Models/Category.php';
require_once BASE_PATH . '/app/Middleware/AuthMiddleware.php';
require_once BASE_PATH . '/app/Middleware/AdminMiddleware.php';
require_once BASE_PATH . '/app/Middleware/CsrfMiddleware.php';
require_once BASE_PATH . '/app/Middleware/RateLimitMiddleware.php';
require_once BASE_PATH . '/app/Controllers/HomeController.php';
require_once BASE_PATH . '/app/Controllers/AuthController.php';
require_once BASE_PATH . '/app/Controllers/VideoController.php';
require_once BASE_PATH . '/app/Controllers/AdminController.php';

\App\Core\Env::load(BASE_PATH . '/.env');
\App\Core\Logger::init(BASE_PATH . '/storage/logs');

date_default_timezone_set('Asia/Jakarta');

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new \ErrorException($message, 0, $severity, $file, $line);
});
