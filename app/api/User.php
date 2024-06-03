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

class User
{
    /**
     * @param Request $request
     * @return Response
     * @throws Throwable
     * @api GET /user
     */
    function index(Request $request): Response
    {
        return response(Server::listAccount());
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Throwable
     * @api POST /user
     */
    function store(Request $request): Response
    {
        $port = (int)$request->data['port'];
        $password = $request->data['password'];
        return response(Server::addAccount($port, $password));
    }

    /**
     * @param Request $request
     * @param string $id
     * @return Response
     * @throws Throwable
     * @api GET /user/{id}
     */
    function show(Request $request, string $id): Response
    {
        return response(Server::getClientIp((int)$id));
    }

    /**
     * @param Request $request
     * @param string $id
     * @return Response
     * @throws Throwable
     * @api PUT /user/{id}
     */
    function update(Request $request, string $id): Response
    {
        $password = $request->data['password'];
        return response(Server::changePassword((int)$id, $password));
    }

    /**
     * @param Request $request
     * @param string $id
     * @return Response
     * @throws Throwable
     * @api DELETE /user/{id}
     */
    function destroy(Request $request, string $id): Response
    {
        return response(Server::removeAccount((int)$id));
    }
}