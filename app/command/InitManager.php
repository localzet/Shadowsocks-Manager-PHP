<?php

namespace app\command;

use localzet\Console\Commands\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class InitManager extends Command
{
    protected static string $defaultName = 'init:manager';
    protected static string $defaultDescription = 'Запуск службы Shadowsocks-manager';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('port', InputArgument::OPTIONAL, 'Порт внутреннего адреса', 6001);
        $this->addArgument('method', InputArgument::OPTIONAL, 'Метод шифрования', 'chacha20-ietf-poly1305');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $port = $input->getArgument('port');
        $method = $input->getArgument('method');

        $content = <<<CONF
[Unit]
Description=Shadowsocks-manager
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
CapabilityBoundingSet=CAP_NET_BIND_SERVICE
AmbientCapabilities=CAP_NET_BIND_SERVICE
LimitNOFILE=32768
ExecStart=/usr/bin/ss-manager -m $method -u --manager-address 127.0.0.1:$port

[Install]
WantedBy=multi-user.target
CONF;

        $file = '/etc/systemd/system/shadowsocks-manager.service';
        if (file_put_contents($file, $content) === false) {
            $output->writeln("Ошибка при записи в файл $file");
            return self::FAILURE;
        }

        $commands = [
            'sudo systemctl daemon-reload',
            'sudo systemctl enable shadowsocks-manager',
            'sudo systemctl restart shadowsocks-manager'
        ];
        foreach ($commands as $command) {
            $result = shell_exec($command);
            if ($result === null) {
                $output->writeln("Ошибка при выполнении команды: $command");
                return self::FAILURE;
            }
        }

        $output->writeln('Служба Shadowsocks-manager запущена');
        return self::SUCCESS;
    }
}
