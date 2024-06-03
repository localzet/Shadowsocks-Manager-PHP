<?php

namespace app\model;

use Triangle\Engine\Database\Model;

/**
 * flow 
 * @property integer $port 
 * @property integer $flow 
 * @property integer $time
 */
class Flow extends Model
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
    protected $table = 'flow';

    /**
     * Первичный ключ, связанный с таблицей.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Указывает, должна ли модель быть временной меткой.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = ['port', 'flow', 'time'];
}
