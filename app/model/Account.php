<?php

namespace app\model;

use Triangle\Engine\Database\Model;

/**
 * account
 * @property integer $port (primary)
 * @property string $password
 */
class Account extends Model
{
    /**
     * Соединение для модели
     *
     * @var string|null
     */
    protected $connection = 'mysql';

    /**
     * Таблица, связанная с моделью.
     *
     * @var string
     */
    protected $table = 'account';

    /**
     * Первичный ключ, связанный с таблицей.
     *
     * @var string
     */
    protected $primaryKey = 'port';

    /**
     * Указывает, должна ли модель быть временной меткой.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = ['port', 'password'];
}
