<?php

namespace app\command;

use localzet\Console\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateEnv extends Command
{
    protected static string $defaultName = 'generate:env';
    protected static string $defaultDescription = 'Генерация окружения';

    protected function configure()
    {
        $this->addArgument('port_manager', InputArgument::OPTIONAL, 'Порт внутреннего адреса', 6000);
        $this->addArgument('port_manager_api', InputArgument::OPTIONAL, 'Порт внутреннего адреса', 6001);
        $this->addArgument('key_public', InputArgument::OPTIONAL, 'Путь до публичного ключа ECDSA', 'keys/ec.crt');
        $this->addArgument('key_private', InputArgument::OPTIONAL, 'Путь до приватного ключа RSA', 'keys/rsa.key');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $port_manager = $input->getArgument('port_manager');
        $port_manager_api = $input->getArgument('port_manager_api');
        $key_public = $input->getArgument('key_public');
        $key_private = $input->getArgument('key_private');

        $content = <<<CONF
# App
APP_DEBUG=true
APP_NAME='Triangle App'

APP_TIMEZONE='Europe/Moscow'
APP_URL='http://localhost'
ASSET_URL=''

CONTROLLER_REUSE=true
CONTROLLER_SUFFIX=''

# LWT Middleware
LWT_ENABLE=true
LWT_ENCRYPTION='ES512'
LWT_ENCRYPTION_KEY='$key_private'
LWT_SIGNATURE_KEY='$key_public'

# Service (container)
APP_SERVICES=false

# Database
DB_CONNECTION='mysql'
DB_HOST='127.0.0.1'
DB_PORT=3306
DB_DATABASE='triangle'
DB_USERNAME='root'
DB_PASSWORD=''

# Log
LOG_FILE_NAME='logs/triangle.log'
LOG_FILE_COUNT=7
LOG_FILE_LEVEL='debug'
LOG_FILE_FORMAT=null
LOG_FILE_DATE_FORMAT='Y-m-d H:i:s'
LOG_FILE_INLINE_BREAKS=true

# File Monitor
PROCESS_FILE_MONITOR=true

# Redis
REDIS_CLIENT='predis'
REDIS_CLUSTER='redis'

REDIS_PREFIX='triangle_'
REDIS_PREFIX_SESSION='triangle_session_'

REDIS_HOST='127.0.0.1'
REDIS_PORT=6379
REDIS_PASSWORD=null

REDIS_DB=0
REDIS_DB_CACHE=1
REDIS_DB_SESSION=2

# Server
SHADOWSOCKS_LISTEN='udp://127.0.0.1:$port_manager'
MANAGER_LISTEN='udp://127.0.0.1:$port_manager_api'
SERVER_LISTEN='http://0.0.0.0:8000'
SERVER_TRANSPORT='tcp'
SERVER_COUNT
SERVER_USER=''
SERVER_GROUP=''
SERVER_REUSE_PORT=false
SERVER_STOP_TIMEOUT=2

SERVER_SSL_CERT='/etc/letsencrypt/live/example.com/fullchain.pem'
SERVER_SSL_CERT_KEY='/etc/letsencrypt/live/example.com/privkey.pem'
SERVER_SSL_VERIFY_PEER=false

SERVER_FILE_PID='triangle.pid'
SERVER_FILE_STATUS='triangle.status'
SERVER_FILE_STDOUT='logs/stdout.log'
SERVER_FILE_LOG='logs/server.log'

# Session
SESSION_TYPE='file'
SESSION_LIFETIME=604800
SESSION_AUTO_UPDATE=false
SESSION_COOKIE_NAME='PHPSID'
SESSION_COOKIE_LIFETIME=31536000
SESSION_COOKIE_PATH='/'
SESSION_COOKIE_DOMAIN=''
SESSION_COOKIE_HTTP_ONLY=true
SESSION_COOKIE_SECURE=false
SESSION_COOKIE_SAME_SITE=''

# Static
STATIC_ENABLE=true

# View
VIEW_HANDLER='raw'
VIEW_SUFFIX='phtml'
CONF;

        if (file_put_contents(run_path('.env'), $content) === false) {
            $output->writeln('Ошибка при записи в файл ' . run_path('.env'));
            return self::FAILURE;
        }

        $output->writeln('Файл ' . run_path('.env') . ' создан');
        return self::SUCCESS;
    }
}
