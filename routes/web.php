<?php
declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\VideoController;
use App\Core\Router;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

$r = new Router();

$r->get('/',               [HomeController::class, 'index']);
$r->get('/health',         [AdminController::class, 'health']);
$r->get('/privacy',        [AdminController::class, 'privacy']);
$r->get('/terms',          [AdminController::class, 'terms']);

$r->get('/login',          [AuthController::class, 'showLogin']);
$r->post('/login',         [AuthController::class, 'login'],    [CsrfMiddleware::class]);
$r->get('/register',       [AuthController::class, 'showRegister']);
$r->post('/register',      [AuthController::class, 'register'], [CsrfMiddleware::class]);
$r->post('/logout',        [AuthController::class, 'logout'],   [AuthMiddleware::class, CsrfMiddleware::class]);

$r->get('/video/{id}',     [VideoController::class, 'show']);

$r->get('/admin',          [AdminController::class, 'dashboard'], [AdminMiddleware::class]);
$r->get('/video/create',   [VideoController::class, 'create'],  [AdminMiddleware::class]);
$r->post('/video',         [VideoController::class, 'store'],   [AdminMiddleware::class, CsrfMiddleware::class]);
$r->get('/video/{id}/edit',[VideoController::class, 'edit'],    [AdminMiddleware::class]);
$r->post('/video/{id}',    [VideoController::class, 'update'],  [AdminMiddleware::class, CsrfMiddleware::class]);
$r->post('/video/{id}/delete', [VideoController::class, 'delete'], [AdminMiddleware::class, CsrfMiddleware::class]);


// ─── Email Verification ───
$r->get('/verify-email',   [AuthController::class, 'showVerifyEmail']);
$r->post('/resend-verification', [AuthController::class, 'resendVerification'], [CsrfMiddleware::class]);

// ─── Password Reset ───
$r->get('/forgot-password',  [AuthController::class, 'showForgotPassword']);
$r->post('/forgot-password', [AuthController::class, 'sendResetLink'], [CsrfMiddleware::class]);
$r->get('/reset-password',   [AuthController::class, 'showResetPassword']);
$r->post('/reset-password',  [AuthController::class, 'resetPassword'], [CsrfMiddleware::class]);

return $r;

