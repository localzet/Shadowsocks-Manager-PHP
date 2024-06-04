<?php

/**
 * @package     Triangle Web
 * @link        https://github.com/Triangle-org
 *
 * @copyright   2018-2024 Localzet Group
 * @license     https://mit-license.org MIT
 */

namespace services;

use app\model\Account;
use app\model\Flow;
use Composer\InstalledVersions;
use JetBrains\PhpStorm\ArrayShape;
use localzet\Server\Connection\AsyncUdpConnection;
use support\Cache;
use support\Log;
use Throwable;

class Server
{
    protected const CACHE_PREFIX = 'SS-API_';
    protected const CACHE_VARS = [
        'existPort' => 'PORTS',
        'portsForLibev' => 'PORTS_LIBEV',
        'existPortUpdatedAt' => 'PORTS_UPDATED',

        'isGfw' => 'GFW',
        'getGfwStatusTime' => 'GFW_UPDATED',

        'firstFlow' => 'FLOW_FIRST',
        'lastFlow' => 'FLOW_LAST',

        'clientIp' => 'CLIENTS',
        'restart' => 'RESTART',
    ];

    /**
     * @param int $port
     * @param string $password
     * @return array
     * @throws Throwable
     */
    public static function addAccount(int $port, string $password): array
    {
        self::sendMessage('add: {"server_port": ' . $port . ', "password": "' . $password . '"}');
        Account::insert(compact('port', 'password'));
        return compact('port', 'password');
    }

    /**
     * @param int $port
     * @return array
     * @throws Throwable
     */
    public static function removeAccount(int $port): array
    {
        Account::where(compact('port'))->delete();
        Flow::where(compact('port'))->delete();
        self::sendMessage('remove: {"server_port": ' . $port . '}');
        return compact('port');
    }

    /**
     * @param int $port
     * @param string $password
     * @return array
     * @throws Throwable
     */
    public static function changePassword(int $port, string $password): array
    {
        Account::where(compact('port'))->update(compact('password'));
        self::sendMessage('remove: {"server_port": ' . $port . '}');
        self::sendMessage('add: {"server_port": ' . $port . ', "password": "' . $password . '"}');
        return compact('port', 'password');
    }

    public static function listAccount(): array
    {
        return Account::get(['port', 'password'])?->toArray();
    }

    /**
     * @param array{'startTime': int, 'endTime': int, 'clear': boolean} $options
     * @return array[]
     */
    public static function getFlow(array $options): array
    {
        $startTime = $options['startTime'] ?? 0;
        $endTime = $options['endTime'] ?? time();

        $accounts = Account::select('port')->get()->toArray();
        $flows = Flow::select('port')
            ->selectRaw('SUM(flow) as sumFlow')
            ->groupBy('port')
            ->whereBetween('time', [$startTime, $endTime])
            ->get()->keyBy('port')->toArray();

        $accounts = array_map(function ($account) use ($flows) {
            if (isset($flows[$account['port']])) {
                $account['sumFlow'] = $flows[$account['port']]['sumFlow'];
            } else {
                $account['sumFlow'] = 0;
            }

            return $account;
        }, $accounts);

        if (isset($options['clear']) && $options['clear']) {
            Flow::whereBetween('time', [$startTime, $endTime])->delete();
        }

        return $accounts;
    }

    /**
     * @return array{version: null|string, isGfw: bool}
     */
    #[ArrayShape(['version' => "null|string", "isGfw" => "bool"])]
    public static function getVersion(): array
    {
        return [
            'version' => InstalledVersions::getPrettyVersion('localzet/server'),
            "isGfw" => self::get('isGfw', 0) > 5
        ];
    }

    /**
     * @param int $port
     * @return array
     */
    public static function getClientIp(int $port): array
    {
        $clientIp = self::get('clientIp', []);

        $clientIp = array_filter($clientIp, function ($f) {
            return (time() - $f['time']) <= (15 * 60 * 1000);
        });

        $result = [];
        array_map(function ($m) use (&$result) {
            if (!in_array($m['ip'], $result)) {
                $result[] = $m['ip'];
            }
        }, array_filter($clientIp, function ($f) use ($port) {
            return (time() - $f['time']) <= (15 * 60 * 1000) && $f['port'] === $port;
        }));

        return $result;
    }


    /**
     * @param $message
     * @return void
     * @throws Throwable
     */
    private static function sendMessage($message): void
    {
        $shadowsocks = new AsyncUdpConnection(env('SHADOWSOCKS_LISTEN', 'udp://127.0.0.1:6001'));
        // ->close() вызовет ->send() из-за текста, а тот вызовет ->connect() из-за ->connected === false
        $shadowsocks->close('list');

        self::log('(sendMessage) ' . $message);
    }

    /**
     * @param $message
     * @param string $level
     * @return void
     */
    private static function log($message, string $level = 'debug'): void
    {
        \localzet\Server::log('[ShadowSocks] ' . $message);
        Log::$level('[ShadowSocks] ' . $message);
    }

    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    private static function get($key, $default = null): mixed
    {
        return Cache::get(self::CACHE_PREFIX . self::CACHE_VARS[$key], $default);
    }

    /**
     * @param $key
     * @param $value
     * @return void
     */
    private function set($key, $value): void
    {
        Cache::set(self::CACHE_PREFIX . self::CACHE_VARS[$key], $value);
    }
}