<?php

namespace app\command;

use localzet\Console\Commands\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitSysctl extends Command
{
    protected static string $defaultName = 'init:sysctl';
    protected static string $defaultDescription = 'Реконфигурация ядра Linux';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $content = <<<CONF
# kernel.domainname = net.zorin.space

fs.file-max = 2097152
# fs.file-max = 51200

net.core.rmem_max = 16777216
net.core.wmem_max = 16777216
# net.core.rmem_max = 67108864
# net.core.wmem_max = 67108864

# Оптимизация TCP/IP стека
net.ipv4.ip_forward = 1
net.core.netdev_max_backlog = 4096
# net.core.netdev_max_backlog = 250000
net.core.somaxconn = 4096
net.ipv4.tcp_syncookies = 1
net.ipv4.tcp_tw_reuse = 1
# net.ipv4.tcp_tw_recycle = 0
net.ipv4.tcp_fin_timeout = 30
net.ipv4.tcp_keepalive_time = 1200
net.ipv4.ip_local_port_range = 10000 65000
net.ipv4.tcp_max_syn_backlog = 4096
# net.ipv4.tcp_max_syn_backlog = 8192
net.ipv4.tcp_max_tw_buckets = 5000
net.ipv4.tcp_fastopen = 3
net.ipv4.tcp_mem = 25600 51200 102400
net.ipv4.tcp_rmem = 4096 87380 51200
net.ipv4.tcp_wmem = 4096 16384 51200
# net.ipv4.tcp_rmem = 4096 87380 67108864
# net.ipv4.tcp_wmem = 4096 65536 67108864
net.ipv4.tcp_mtu_probing = 1

# Оптимизация IPv6
net.ipv6.conf.all.forwarding = 1
net.ipv6.conf.all.accept_ra = 2
net.ipv6.conf.all.accept_source_route = 0
net.ipv6.conf.all.accept_redirects = 0

# Настройки ICMP
net.ipv4.icmp_echo_ignore_all = 1

# Настройки безопасности
net.ipv4.conf.all.log_martians = 1
net.ipv4.conf.all.accept_source_route = 0
net.ipv4.conf.all.send_redirects = 0
net.ipv4.conf.all.accept_redirects = 0
net.ipv4.conf.default.rp_filter = 1
net.ipv4.conf.all.rp_filter = 1

# Настройка алгоритма контроля перегрузки TCP
net.ipv4.tcp_congestion_control = bbr

# Дополнительные настройки
net.ipv4.tcp_sack = 1
net.ipv4.tcp_timestamps = 1
net.ipv4.tcp_window_scaling = 1
CONF;

        $file = '/etc/sysctl.conf';
        if (file_put_contents($file, $content) === false) {
            $output->writeln("Ошибка при записи в файл $file");
            return self::FAILURE;
        }

        shell_exec('sudo sysctl -p');

        $output->writeln('Реконфигурация ядра Linux выполнена успешно');
        return self::SUCCESS;
    }
}