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
        'listen' => 'udp://' . env('MANAGER_ADDRESS', '0.0.0.0:6002'),
        'count' => 1,
        'reloadable' => false,
        'reusePort' => false,
        'handler' => services\ShadowSocks::class,
        'constructor' => [
            'address' => env('SHADOWSOCKS_ADDRESS','127.0.0.1:6001')
        ],
    ]
];
