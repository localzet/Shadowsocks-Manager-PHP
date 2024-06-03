<?php

/**
 * @package     Triangle Cloud Platform
 * @link        https://github.com/localzet-dev/CloudPlatform
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2024 Zorin Projects S.P.
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as published
 *              by the Free Software Foundation, either version 3 of the License, or
 *              (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *              For any questions, please contact <creator@localzet.com>
 */

use Triangle\Engine\View\Blade;
use Triangle\Engine\View\Raw;
use Triangle\Engine\View\ThinkPHP;
use Triangle\Engine\View\Twig;

return [
    'handler' => match (env('VIEW_HANDLER', 'raw')) {
        'blade' => Blade::class,
        'raw' => Raw::class,
        'think' => ThinkPHP::class,
        'twig' => Twig::class,
    },
    'options' => [
        'view_suffix' => 'phtml',
        'vars' => [],
        'pre_renders' => [
//            [
//                'app' => null,
//                'plugin' => null,
//                'template' => 'index_header',
//                'vars' => [],
//            ],
        ],
        'post_renders' => [
//            [
//                'app' => null,
//                'plugin' => null,
//                'template' => 'index_footer',
//                'vars' => [],
//            ],
        ],
    ],
];
