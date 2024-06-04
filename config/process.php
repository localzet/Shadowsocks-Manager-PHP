<?php

/**
 * @package     Triangle Web
 * @link        https://github.com/Triangle-org
 *
 * @copyright   2018-2024 Localzet Group
 * @license     https://mit-license.org MIT
 */

return [
    'ShadowSocks' => [
        'listen' => env('MANAGER_LISTEN', 'udp://127.0.0.1:6002'),
        'count' => 1,
        'reloadable' => false,
        'reusePort' => false,
        'handler' => services\ShadowSocks::class,
        'constructor' => [
            'address' => env('SHADOWSOCKS_LISTEN','udp://127.0.0.1:6001')
        ],
    ]
];
