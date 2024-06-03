<?php

/**
 * @package     Triangle Web
 * @link        https://github.com/Triangle-org
 *
 * @copyright   2018-2024 Localzet Group
 * @license     https://mit-license.org MIT
 */

namespace app\api;

use services\Server;
use support\Request;
use support\Response;
use Throwable;

class Flow
{
    /**
     * @param Request $request
     * @return Response
     * @throws Throwable
     * @api GET /flow
     */
    public function index(Request $request): Response
    {
        return response(Server::getFlow($request->data));
    }
}
