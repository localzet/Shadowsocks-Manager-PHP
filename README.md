<p align="center"><a href="https://www.localzet.com" target="_blank">
  <img src="https://cdn.localzet.com/assets/media/logos/ZorinProjectsSP.svg" width="400">
</a></p>

<p align="center">
  <a href="https://packagist.org/packages/localzet/ssmgr">
  <img src="https://img.shields.io/packagist/dt/localzet/ssmgr?label=%D0%A1%D0%BA%D0%B0%D1%87%D0%B8%D0%B2%D0%B0%D0%BD%D0%B8%D1%8F" alt="Скачивания">
</a>
  <a href="https://github.com/localzet/SSManager-S">
  <img src="https://img.shields.io/github/commit-activity/t/localzet/SSManager-S?label=%D0%9A%D0%BE%D0%BC%D0%BC%D0%B8%D1%82%D1%8B" alt="Коммиты">
</a>
  <a href="https://packagist.org/packages/localzet/ssmgr">
  <img src="https://img.shields.io/packagist/v/localzet/ssmgr?label=%D0%92%D0%B5%D1%80%D1%81%D0%B8%D1%8F" alt="Версия">
</a>
  <a href="https://packagist.org/packages/localzet/ssmgr">
  <img src="https://img.shields.io/packagist/dependency-v/localzet/ssmgr/php?label=PHP" alt="Версия PHP">
</a>
  <a href="https://github.com/localzet/SSManager-S">
  <img src="https://img.shields.io/github/license/localzet/SSManager-S?label=%D0%9B%D0%B8%D1%86%D0%B5%D0%BD%D0%B7%D0%B8%D1%8F" alt="Лицензия">
</a>
</p>

# SSManager-S

SSManager-S - это мощный и гибкий инструмент для управления серверами, построенный на базе Triangle-org/Web и работающий на базе localzet/server. Он предоставляет удобный и безопасный доступ к API с использованием токенов localzet/lwt, обеспечивая высокую производительность и надежность.

## Установка

### Установка Redis

Redis используется для кэширования данных. Для установки выполните следующие команды:

```bash
sudo apt update
sudo apt install redis-server
```

Проверьте статус Redis:

```bash
sudo systemctl status redis
```

### Установка Supervisor

Supervisor используется для автозапуска процессов. Для установки выполните следующие команды:

```bash
sudo apt-get install supervisor
sudo service supervisor restart
```

## Настройка проекта

### Вариант 1: Установка через Composer

Если в вашей системе уже установлены PHP v8.3 и Composer, вы можете использовать Composer для установки проекта:

1. Создание проекта

   ```bash
   composer create-project localzet/ssmgr
   ```

2. Переход в папку проекта

   ```bash
   cd ssmgr
   ```

3. Установка зависимостей

   ```bash
   composer install
   ```

4. Инициализация master

   ```bash
   php master init
   ```

5. Включение master

   ```bash
   php master enable
   ```

### Вариант 2: Клонирование репозитория

1. Клонирование репозитория

   ```bash
   git clone <URL репозитория>
   ```

2. Переход в папку репозитория

   ```bash
   cd <имя репозитория>
   ```

3. Установка прав на исполнение для php-8.3

   ```bash
   chmod +x ./php-8.3
   ```

4. Установка зависимостей с помощью Composer

   ```bash
   ./php-8.3 composer.phar install
   ```

5. Инициализация master

   ```bash
   ./php-8.3 master init
   ```

6. Включение master

   ```bash
   ./php-8.3 master enable
   ```

## Использование API

По умолчанию для доступа к API требуются токены `localzet/lwt`, разделенные на сегменты:

- `header` в заголовке `X-LWT-Header`
- `payload` в POST-параметре `data`
- `signature` в заголовке `X-LWT-Signature`

Ключи для LWT будут находиться в директории `./runtime/keys` после команды `init`

Если вы по какой-то причине не можете использовать LWT - отключить их использование можно переменной `LWT_ENABLE` в файле `.env`

### Конечные точки

| PATH       | METHOD | METHOD |
|------------|--------|--------|
| /user      | GET    | GET    |
| /user      | POST   | POST   |
| /user/{id} | GET    | GET    |
| /user/{id} | PUT    | PUT    |
| /user/{id} | DELETE | DELETE |
| /flow      | GET    | GET    |
| /version   | GET    | {"version": "v4.2.11", "isGfw": true}    |
