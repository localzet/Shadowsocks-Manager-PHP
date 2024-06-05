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
use localzet\Server;
use localzet\Server\Connection\AsyncUdpConnection;
use localzet\Timer;
use support\Cache;
use support\Log;
use Throwable;

class ShadowSocks
{
    protected ?AsyncUdpConnection $shadowsocks = null;

    protected ?string $shadowsocksType = 'libev';
    protected ?bool $isNewPython = false;

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

    public function __construct(protected $address)
    {
        $this->set('existPortUpdatedAt', time());
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function onServerStart(): void
    {
        shell_exec('sudo systemctl reset-failed shadowsocks-manager');
        shell_exec('sudo systemctl restart shadowsocks-manager');

        // Создание нового UDP-соединения
        $this->shadowsocks = new AsyncUdpConnection($this->address);
        $this->shadowsocks->onConnect = [$this, 'onConnect'];
        $this->shadowsocks->onMessage = [$this, 'onMessage'];
        $this->shadowsocks->onError = [$this, 'onError'];
        $this->shadowsocks->onClose = [$this, 'onClose'];

        // Установка соединения
        $this->shadowsocks->connect();
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function onConnect(): void
    {
        $this->log('(onConnect) Новое соединение', 'info');
        $this->startUp();
        $this->sendPing();
        $this->getGfwStatus();

        // Добавление таймера, который срабатывает каждые 60 секунд
        Timer::add(60, function () {
            $this->resend();
            $this->sendPing();
            $this->getGfwStatus();
        });
    }

    /**
     * @param $connection
     * @param $data
     * @throws Throwable
     */
    public function onMessage($connection, $data): void
    {
        $this->log('(onMessage) ' . $data);
        $msgStr = $data;

        // Проверка типа сообщения и выполнение соответствующих действий
        if (str_starts_with($msgStr, 'pong')) {
            // Если сообщение начинается с 'pong', устанавливаем тип shadowsocks в 'python'
            $this->shadowsocksType = 'python';
        } else if (str_starts_with($msgStr, '[{')) {
            // Если сообщение начинается с '[{', устанавливаем isNewPython в true и обновляем existPort
            $this->isNewPython = true;
            $this->set('portsForLibev', json_decode($msgStr, true));
            $this->setExistPort($this->get('portsForLibev', []));
        } else if (str_starts_with($msgStr, "[\n\t")) {
            // Если сообщение начинается с "[\n\t", устанавливаем тип shadowsocks в 'libev' и обновляем existPort
            $this->shadowsocksType = 'libev';
            $this->set('portsForLibev', json_decode($msgStr, true));
            $this->setExistPort($this->get('portsForLibev', []));
        } else if (str_starts_with($msgStr, 'stat:')) {
            // Если сообщение начинается с 'stat:', обрабатываем статистические данные
            $flow = json_decode(substr($msgStr, 5), true);
            $this->log("Flow:\n" . print_r($flow, true), 'info');
            if (!$this->isNewPython && !empty($flow)) {
                $this->setExistPort($flow);
            }
            $realFlow = $this->compareWithLastFlow($flow, $this->get('lastFlow'));

            // Если текущая минута делится на 3 без остатка, получаем подключенные IP-адреса
            if (date('i') % 3 === 0) {
                foreach ($realFlow as $rf => $value) {
                    if ($value) {
                        $this->getConnectedIp($rf);
                    }
                }
            }

            // Записываем в лог информацию о потоке
            $this->log("Receive flow from shadowsocks: ($this->shadowsocksType)\n" . print_r($realFlow, true), 'info');
            $this->set('lastFlow', $flow);

            // Обрабатываем данные потока
            $insertFlow = array_filter(array_map(function ($m) use ($realFlow) {
                return [
                    'port' => intval($m),
                    'flow' => intval($realFlow[$m]),
                    'time' => time(),
                ];
            }, array_keys($realFlow)), function ($f) {
                return $f['flow'] > 0;
            });

            $accounts = Account::all()->toArray();

            if ($this->shadowsocksType === 'python' && !$this->isNewPython) {
                foreach ($insertFlow as $fe) {
                    $account = array_filter($accounts, function ($f) use ($fe) {
                        return $fe['port'] === $f['port'];
                    });
                    if (empty($account)) {
                        $this->sendMessage("remove: {\"server_port\": {$fe['port']}}");
                    }
                }
            } else {
                foreach ($this->get('portsForLibev', []) as $_ => $f) {
                    $account = array_filter($accounts, function ($a) use ($f) {
                        return $a['port'] === intval($f['server_port']);
                    });
                    $this->log('$account ' . print_r($account, true));
                    if (empty($account)) {
                        $this->sendMessage("remove: {\"server_port\": {$f['server_port']}}");
                    } else if (array_values($account)[0]['password'] !== $f['password']) {
                        $this->sendMessage("remove: {\"server_port\": {$f['server_port']}}");
                        $this->sendMessage("add: {\"server_port\": {$account[0]['port']}, \"password\": \"{$account[0]['password']}\"}");
                    }
                }
            }

            // Если есть данные для вставки, вставляем их в базу данных
            if (!empty($insertFlow)) {
                if ($this->get('firstFlow', true)) {
                    $this->set('firstFlow', false);
                } else {
                    for ($i = 0; $i < ceil(count($insertFlow) / 50); $i++) {
                        Flow::insert(array_slice($insertFlow, $i * 50, 50));
                    }
                }
            }
        }
    }

    /**
     * @param $connection
     * @param $code
     * @param $text
     * @return void
     */
    public function onError($connection, $code, $text): void
    {
        $this->log('(onError) Ошибка клиента: ' . $text, 'error');
    }

    /**
     * @param $connection
     * @return void
     */
    public function onClose($connection): void
    {
        $this->log('(onClose) Соединение закрыто', 'error');
    }

    /**
     * @return void
     * @throws Throwable
     */
    private function sendPing(): void
    {
        $this->sendMessage('ping');
        $this->sendMessage('list');
    }

    /**
     * Установка существующих портов
     *
     * @param $flow
     * @return void
     */
    private function setExistPort($flow): void
    {
        $existPort = [];
        if (array_is_list($flow)) {
            $existPort = array_map(function ($f) {
                return intval($f['server_port']);
            }, $flow);
        } else {
            foreach ($flow as $f => $value) {
                $existPort[] = intval($f);
            }
        }

        $this->set('existPort', $existPort);
        $this->set('existPortUpdatedAt', time());

        $this->log("(setExistPort) existPort:\n" . print_r($existPort, true), 'info');
    }

    /**
     * Отправка сообщения процессу ShadowSocks
     *
     * @param $message
     * @return void
     * @throws Throwable
     */
    private function sendMessage($message): void
    {
        $this->log('(sendMessage) ' . $message);
        $this->shadowsocks->send($message);
    }

    /**
     * @return void
     * @throws Throwable
     */
    private function startUp(): void
    {
        // Отправка сообщения 'ping' при установлении соединения
        $this->shadowsocks->send('ping');

        // Если тип ShadowSocks равен 'python', отправляем сообщение для удаления порта
        if ($this->shadowsocksType === 'python') {
            $this->sendMessage('remove: {"server_port": 65535}');
        }

        // Получение списка аккаунтов
        $accounts = Account::select(['port', 'password'])->get();
        foreach ($accounts as $account) {
            // Для каждого аккаунта отправляем сообщение для добавления порта и пароля
            $this->sendMessage("add: {\"server_port\": {$account->port}, \"password\": \"{$account->password}\"}");
        }
    }

    /**
     * @return void
     * @throws Throwable
     */
    private function resend(): void
    {
        // Если прошло более 180 секунд с последнего обновления порта, очищаем список портов
        if (time() - $this->get('existPortUpdatedAt', 0) >= 180) {
            $this->set('existPort', []);
        }

        // Получение списка аккаунтов
        $accounts = Account::select(['port', 'password'])->get();
        foreach ($accounts as $account) {
            // Если порт аккаунта не в списке существующих портов, отправляем сообщение для добавления порта и пароля
            if (!in_array($account->port, $this->get('existPort', []))) {
                $this->sendMessage("add: {\"server_port\": {$account->port}, \"password\": \"{$account->password}\"}");
            }
        }
        $this->log("(resend) existPort:\n" . print_r($this->get('existPort', []), true), 'info');
    }

    /**
     * Сравнение текущего трафика с последним
     *
     * @param $flow
     * @param $lastFlow
     * @return mixed
     * @throws Throwable
     */
    private function compareWithLastFlow($flow, $lastFlow): mixed
    {
        if ($this->shadowsocksType === 'python') {
            return $flow;
        }
        $realFlow = [];
        if (!$lastFlow) {
            foreach ($flow as $f => $value) {
                if ($value <= 768) {
                    unset($flow[$f]);
                }
            }
            return $flow;
        }
        foreach ($flow as $f => $value) {
            if (isset($lastFlow[$f])) {
                $realFlow[$f] = $value - $lastFlow[$f];
                $restart = $this->get('restart', []);
                if ($realFlow[$f] === 0 && $value > 5 * 1000 * 1000 * 1000) {
                    if (!isset($restart[$f])) {
                        $restart[$f] = 1;
                    }
                    if ($restart[$f] < 30) {
                        $restart[$f] += 1;
                        continue;
                    }
                    $account = Account::where('port', intval($f))->first();
                    if ($account) {
                        $this->sendMessage("remove: {\"server_port\": {$account->port}}");
                        $this->sendMessage("add: {\"server_port\": {$account->port}, \"password\": \"{$account->password}\"}");
                        unset($restart[$f]);
                    }
                } else {
                    unset($restart[$f]);
                }
                $this->set('restart', $restart);
            } else {
                $realFlow[$f] = $value;
            }
        }
        if (empty($realFlow) ?? min(array_values($realFlow)) < 0) {
            return $flow;
        }
        foreach ($realFlow as $r => $value) {
            if ($value <= 768) {
                unset($realFlow[$r]);
            }
        }
        return $realFlow;
    }

    /**
     * Получение подключенных IP-адресов
     *
     * @param $port
     * @return void
     */
    private function getConnectedIp($port): void
    {
        $ips = $this->getIp($port);
        foreach ($ips as $ip) {
            $clientIp = $this->get('clientIp', []);
            $clientIp[] = ['port' => $port, 'time' => time(), 'ip' => $ip];
            $this->set('clientIp', $clientIp);
        }
    }

    /**
     * @return void
     */
    private function getGfwStatus(): void
    {
        // Если время последней проверки статуса GFW и текущий статус GFW равен 0, и прошло менее 600 секунд с последней проверки, возвращаемся
        if ($this->get('getGfwStatusTime') && $this->get('isGfw', 0) === 0 && time() - $this->get('getGfwStatusTime', 0) < 600) {
            return;
        }
        // Обновление времени последней проверки статуса GFW
        $this->set('getGfwStatusTime', time());

        // Установка сайта для проверки
        $site = 'google.com';
        if (isset($config['isGfwUrl'])) {
            $site = $config['isGfwUrl'];
        }

        // Создание контекста потока для HTTP-запроса
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8 + $this->get('isGfw', 0) * 2,
            ]
        ]);

        // Отправка HTTP-запроса
        $fp = @fopen('https://' . $site, 'r', false, $context);

        // Если запрос успешен, устанавливаем статус GFW в 0, иначе увеличиваем его на 1
        if ($fp) {
            $this->set('isGfw', 0);
            fclose($fp);
        } else {
            $this->set('isGfw', $this->get('isGfw', 0) + 1);
        }
    }

    /**
     * Получение IP-адресов, связанных с портом
     *
     * @param $port
     * @return array
     */
    private function getIp($port): array
    {
        $cmd = "ss -an | grep ':{$port} ' | grep ESTAB | awk '{print $6}' | cut -d: -f1 | grep -v 127.0.0.1 | uniq -d";
        $output = shell_exec($cmd);
        return array_unique(array_filter(explode("\n", $output)));
    }

    /**
     * @param $message
     * @param string $level
     * @return void
     */
    private function log($message, string $level = 'debug'): void
    {
        Server::log('[ShadowSocks] ' . $message);
        //Log::$level('[ShadowSocks] ' . $message);
    }

    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    private function get($key, $default = null): mixed
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