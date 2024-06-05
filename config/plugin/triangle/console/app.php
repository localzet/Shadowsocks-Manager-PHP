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

$base_path = defined('BASE_PATH') ? BASE_PATH : (Composer\InstalledVersions::getRootPackage()['install_path'] ?? null);

return [
    'enable' => true,

    'build' => [
        'input_dir' => $base_path,
        'output_dir' => $base_path . DIRECTORY_SEPARATOR . 'build',

        'exclude_pattern' => '#^(?!.*(composer.json|/.github/|/.idea/|/.git/|/.setting/|/runtime/|/vendor-bin/|/build/))(.*)$#',
        'exclude_files' => [
            '.env',
            '.env.example',
            '.gitattributes',
            '.gitignore',
            'CODE_OF_CONDUCT.md',
            'CODE_OF_CONDUCT_ru.md',
            'composer.json',
            'composer.lock',
            'composer.phar',
            'CONTRIBUTING.md',
            'CONTRIBUTING_ru.md',
            'LICENSE',
            'php-8.3',
            'README.md',
            'ssmgr.phar',
            'ssmgr',
        ],

        'phar_alias' => 'ssmgr',
        'phar_filename' => 'ssmgr.phar',
        'phar_stub' => 'master',

        // Для бинарной сборки:
        'php_version' => 8.3,
        'custom_ini' => 'memory_limit = 512M',

        'bin_filename' => 'ssmgr',
    ],
];
