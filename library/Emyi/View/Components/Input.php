<?php
/*
 * Emyi
 *
 * @link http://github.com/douggr/Emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\View\Components;

use Emyi\Util\String;

/**
 * A simple class to build and output HTML5 inputs
 */
class Input extends Element
{
    /**
     *
     */
    public static $mapping = [
        'text' => [
            'class' => '',
            'attributes' => [
                'type'          => 'text',
                'autocomplete'  => 'off',
            ]
        ],

        'password' => [
            'class' => '',
            'attributes' => [
                'type'          => 'password',
                'autocomplete'  => 'off',
            ]
        ],

        'file' => [
            'class' => '',
            'attributes' => [
                'type'          => 'file',
                'autocomplete'  => 'off',
            ]
        ],

        'search' => [
            'class' => '',
            'attributes' => [
                'type'          => 'search',
                'autocomplete'  => 'off',
            ]
        ],

        'email' => [
            'class' => '',
            'attributes' => [
                'type'          => 'email',
                'autocomplete'  => 'off',
            ]
        ],

        'url' => [
            'class' => '',
            'attributes' => [
                'type'          => 'url',
                'autocomplete'  => 'off',
            ]
        ],

        'tel' => [
            'class' => '',
            'attributes' => [
                'type'          => 'tel',
                'autocomplete'  => 'off',
            ]
        ],

        'number' => [
            'class' => '',
            'attributes' => [
                'type'          => 'number',
                'autocomplete'  => 'off',
            ]
        ],

        'range' => [
            'class' => '',
            'attributes' => [
                'min'           => 0,
                'max'           => 100,
                'type'          => 'range',
                'autocomplete'  => 'off',
            ]
        ],

        'date' => [
            'class' => '',
            'attributes' => [
                'type'          => 'date',
                'autocomplete'  => 'off',
            ]
        ],

        'month' => [
            'class' => '',
            'attributes' => [
                'type'          => 'month',
                'autocomplete'  => 'off',
            ]
        ],

        'week' => [
            'class' => '',
            'attributes' => [
                'type'          => 'week',
                'autocomplete'  => 'off',
            ]
        ],

        'time' => [
            'class' => '',
            'attributes' => [
                'type'          => 'time',
                'autocomplete'  => 'off',
            ]
        ],

        'datetime' => [
            'class' => '',
            'attributes' => [
                'type'          => 'datetime',
                'autocomplete'  => 'off',
            ]
        ],

        'datetimeLocal' => [
            'class' => '',
            'attributes' => [
                'type'          => 'datetime-local',
                'autocomplete'  => 'off',
            ]
        ],

        'color' => [
            'class' => '',
            'attributes' => [
                'type'          => 'color',
                'autocomplete'  => 'off',
            ]
        ],
    ];

    /**
     * Create a new Element instance statically using the called method as
     * the input type and arguments as attributes.
     *
     * @param string tag name
     * @param array attributes to set
     * @return Emyi\View\Components\Input
     * @internal
     */
    final public static function __callStatic($method, array $attributes = [])
    {
        if (!array_key_exists($method, static::$mapping)) {
            return new static('input');
        }

        $data  = static::$mapping[$method];
        $input = (new static('input'))
            ->setAttribute($data['attributes'])
            ->addClass($data['class']);

        return $input;
    }
}
