<?php

namespace app\command;

use localzet\Console\Commands\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class InitShadowsocks extends Command
{
    protected static string $defaultName = 'init:sahdowsocks';
    protected static string $defaultDescription = 'Запуск службы Shadowsocks-libev';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('port', InputArgument::OPTIONAL, 'Порт внутреннего адреса', 6000);
        $this->addArgument('method', InputArgument::OPTIONAL, 'Метод шифрования', 'chacha20-ietf-poly1305');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!shell_exec('which ss-server')) {
            shell_exec('sudo apt-get install shadowsocks-libev');
        }

        $port = $input->getArgument('port');
        $method = $input->getArgument('method');
        $password = generateId();

        $content = <<<CONF
{
    "server": "127.0.0.1",
    "server_port": $port,
    "password": "$password",
    "timeout": 60,
    "method": "$method"
}
CONF;

        $file = '/etc/shadowsocks-libev/config.json';
        if (file_put_contents($file, $content) === false) {
            $output->writeln("Ошибка при записи в файл $file");
            return self::FAILURE;
        }

        shell_exec('sudo systemctl enable shadowsocks-libev');
        shell_exec('sudo systemctl restart shadowsocks-libev');

        $output->writeln('Служба Shadowsocks-libev запущена');
        return self::SUCCESS;
    }
}
