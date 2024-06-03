<?php

namespace app\command;

use localzet\Console\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateKeys extends Command
{
    protected static string $defaultName = 'generate:keys';
    protected static string $defaultDescription = 'Генерация ключей шифрования';

    /**
     * Конфигурация аргументов команды
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('algorithm', InputArgument::OPTIONAL, 'Алгоритм', 'ec');
        $this->addArgument('private_key_bits', InputArgument::OPTIONAL, 'Для RSA, DSA и DH', 2048);
        $this->addArgument('digest_alg', InputArgument::OPTIONAL, 'Для DSA', 'sha512');
        $this->addArgument('curve_name', InputArgument::OPTIONAL, 'Для ECDSA', 'prime256v1');
    }

    /**
     * Выполнение команды
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!extension_loaded('openssl')) {
            $output->writeln("Нет расширения OpenSSL");
            return self::FAILURE;
        }

        $algorithm = $input->getArgument('algorithm');
        $private_key_bits = $input->getArgument('private_key_bits');
        $digest_alg = $input->getArgument('digest_alg');
        $curve_name = $input->getArgument('curve_name');

        $path = runtime_path('keys');
        if (!is_dir($path)) {
            if (!create_dir($path)) {
                $output->writeln("Не удалось создать директорию: $path");
                return self::FAILURE;
            }
        }

        switch ($algorithm) {
            case 'rsa':
                $res = openssl_pkey_new(array(
                    "private_key_bits" => $private_key_bits,
                    "private_key_type" => OPENSSL_KEYTYPE_RSA,
                ));
                break;

            case 'dsa':
                $res = openssl_pkey_new(array(
                    "digest_alg" => $digest_alg,
                    "private_key_bits" => $private_key_bits,
                    "private_key_type" => OPENSSL_KEYTYPE_DSA,
                ));
                break;

            case 'dh':
                $res = openssl_pkey_new(array(
                    "private_key_bits" => $private_key_bits,
                    "private_key_type" => OPENSSL_KEYTYPE_DH,
                ));
                break;

            case 'ec':
                $res = openssl_pkey_new(array(
                    "curve_name" => $curve_name,
                    "private_key_type" => OPENSSL_KEYTYPE_EC,
                ));

                break;

        }

        if (!$res) {
            $output->writeln("Не удалось создать ключи");
            return self::FAILURE;
        }

        openssl_pkey_export($res, $private);
        $public = openssl_pkey_get_details($res)["key"];

        do {
            $filename = sprintf("$algorithm-%04x%04x%04x", mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        } while (file_exists("$path/$filename.crt") || file_exists("$path/$filename.key"));

        if (file_put_contents("$path/$filename.key", $private) === false || file_put_contents("$path/$filename.crt", $public) === false) {
            $output->writeln("Не удалось записать ключи в файлы: $path/$filename.key и $path/$filename.crt");
            return self::FAILURE;
        }

        $output->writeln("Ключи успешно сгенерированы и сохранены в: $path/$filename.key и $path/$filename.crt");
        return self::SUCCESS;
    }
}
