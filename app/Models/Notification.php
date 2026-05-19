<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification as BaseNotification;

class Notification extends BaseNotification
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_notifications';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if ($key === 'id') {
            $key = 'id_notifications';
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if ($key === 'id') {
            $key = 'id_notifications';
        }

        return parent::getAttribute($key);
    }
}
