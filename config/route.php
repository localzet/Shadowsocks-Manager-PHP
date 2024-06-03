<?php

/**
 * @package     Triangle Web
 * @link        https://github.com/Triangle-org
 *
 * @copyright   2018-2024 Localzet Group
 * @license     https://mit-license.org MIT
 */

use support\Request;
use support\Response;
use Triangle\Engine\Router;

Router::disableDefaultRoute();
Router::any('/', function (Request $request): Response {
    return response(config('app.name', 'name'));
});

Router::resource('/user', app\api\User::class);
Router::resource('/flow', app\api\Flow::class);

Router::get('/version', function (Request $request): Response {
    return response(services\Server::getVersion());
});
