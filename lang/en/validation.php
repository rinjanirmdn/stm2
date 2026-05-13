<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines (Override)
    |--------------------------------------------------------------------------
    |
    | Only password-related messages are overridden here.
    | All other validation messages fall back to the framework defaults.
    |
    */

    'password' => [
        'letters'  => 'The :attribute must contain at least one letter.',
        'mixed'    => 'The :attribute must contain at least one uppercase and one lowercase letter.',
        'numbers'  => 'The :attribute must contain at least one number.',
        'symbols'  => 'The :attribute must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],

    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
    ],

    'confirmed' => 'The :attribute confirmation does not match.',

];
