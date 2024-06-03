<?php

/**
 * @package     Triangle Web
 * @link        https://github.com/Triangle-org
 *
 * @copyright   2018-2024 Localzet Group
 * @license     https://mit-license.org MIT
 */

namespace app\middleware;

use Exception;
use localzet\LWT;
use support\Log;
use Throwable;
use Triangle\Engine\Exception\BusinessException;
use Triangle\Engine\Http\Request;
use Triangle\Engine\Http\Response;
use Triangle\Engine\Middleware\MiddlewareInterface;

class CommandMiddleware implements MiddlewareInterface
{

    /**
     * @param Request $request
     * @param callable $handler
     * @return Response
     * @throws Exception|Throwable
     */
    public function process(Request $request, callable $handler): Response
    {
        if ($request->route?->getName() === null) {
            return $handler($request);
        }

        if (env('LWT_ENABLE', true)) {
            $header = $request->header('X-API-Header');
            $signature = $request->header('X-API-Signature');
            $payload = $request->input('data');

            if (!$header || !$signature || !$payload) {
                throw new BusinessException('Отсутствует цифровая подпись');
            }

            try {
                $data = LWT::decode("$header.$payload.$signature",
                    file_get_contents(runtime_path(env('LWT_SIGNATURE_KEY'))),
                    env('LWT_ENCRYPTION'),
                    file_get_contents(runtime_path(env('LWT_ENCRYPTION_KEY'))));

                $request->data = $data;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                throw new BusinessException('Ошибка проверки цифровой подписи');
            }
        } else {
            $request->data = $request->all();
        }

        return $handler($request);
    }
}