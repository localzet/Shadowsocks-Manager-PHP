<?php

namespace app\command;

use localzet\Console\Commands\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Init extends Command
{
    protected static string $defaultName = 'init';
    protected static string $defaultDescription = 'Инициализация всех компонентов';

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
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $port = (int)$input->getArgument('port');
        $method = $input->getArgument('method');


        // Инициализация служб
        $shadowsocks = new InitShadowsocks();
        $input->setArgument('port', $port);
        $input->setArgument('method', $method);
        $shadowsocks->run($input, $output);

        $manager = new InitManager();
        $input->setArgument('port', $port + 1);
        $input->setArgument('method', $method);
        $manager->run($input, $output);


        // Реконфигурация ядра
        $sysctl = new InitSysctl();
        $sysctl->run($input, $output);


        // Генерация ключей
        $keysEC = new GenerateKeys();
        $input->setArgument('algorithm', 'ec');
        $input->setArgument('curve_name', 'sect571k1');
        $keysEC->run($input, $output);

        $keysRSA = new GenerateKeys();
        $input->setArgument('algorithm', 'rsa');
        $input->setArgument('private_key_bits', 4096);
        $keysRSA->run($input, $output);


        $env = new GenerateEnv();
        $input->setArgument('port_manager', $port + 1);
        $input->setArgument('port_manager_api', $port + 2);
        $input->setArgument('key_public', $keysEC->getPublicKeyPath());
        $input->setArgument('key_private', $keysRSA->getPrivateKeyPath());
        $env->run($input, $output);

        $output->writeln('Все компоненты успешно инициализированы');
        return self::SUCCESS;
    }
}
