<?php

namespace app\command;

use localzet\Console\Commands\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
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
        $this->runCommand(new InitShadowsocks(), ['port' => $port, 'method' => $method], $output);
        $this->runCommand(new InitManager(), ['port' => $port + 1, 'method' => $method], $output);

        // Реконфигурация ядра
        $this->runCommand(new InitSysctl(), [], $output);

        // Генерация ключей
        $keysEC = $this->runCommand(new GenerateKeys(), ['algorithm' => 'ec', 'curve_name' => 'sect571k1'], $output);
        $keysRSA = $this->runCommand(new GenerateKeys(), ['algorithm' => 'rsa', 'private_key_bits' => 4096], $output);

        $this->runCommand(new GenerateEnv(), [
            'port_manager' => $port + 1,
            'port_manager_api' => $port + 2,
            'key_public' => $keysEC->getPublicKeyPath(),
            'key_private' => $keysRSA->getPrivateKeyPath(),
        ], $output);

        $output->writeln('Все компоненты успешно инициализированы');
        return self::SUCCESS;
    }

    private function runCommand($command, array $arguments, OutputInterface $output)
    {
        $command->run(new ArrayInput($arguments), $output);
        return $command;
    }
}
