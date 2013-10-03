<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Util;

/**
 * Helpful string class
 */
class String
{
    /**
     *
     */
    private static $plural = [
        '/(quiz)$/i'                     => '$1zes',
        '/^(ox)$/i'                      => '$1en',
        '/([m|l])ouse$/i'                => '$1ice',
        '/(matr|vert|ind)ix|ex$/i'       => '$1ices',
        '/(x|ch|ss|sh)$/i'               => '$1es',
        '/([^aeiouy]|qu)y$/i'            => '$1ies',
        '/(hive)$/i'                     => '$1s',
        '/(?:([^f])fe|([lr])f)$/i'       => '$1$2ves',
        '/(shea|lea|loa|thie)f$/i'       => '$1ves',
        '/sis$/i'                        => 'ses',
        '/([ti])um$/i'                   => '$1a',
        '/(tomat|potat|ech|her|vet)o$/i' => '$1oes',
        '/(bu)s$/i'                      => '$1ses',
        '/(alias)$/i'                    => '$1es',
        '/(octop)us$/i'                  => '$1i',
        '/(ax|test)is$/i'                => '$1es',
        '/(us)$/i'                       => '$1es',
        '/s$/i'                          => 's',
        '/$/'                            => 's',
    ];

    /**
     *
     */
    private static $singular = [
        '/(quiz)zes$/i'                 => '$1',
        '/(matr)ices$/i'                => '$1ix',
        '/(vert|ind)ices$/i'            => '$1ex',
        '/^(ox)en$/i'                   => '$1',
        '/(alias)es$/i'                 => '$1',
        '/(octop|vir)i$/i'              => '$1us',
        '/(cris|ax|test)es$/i'          => '$1is',
        '/(shoe)s$/i'                   => '$1',
        '/(o)es$/i'                     => '$1',
        '/(bus)es$/i'                   => '$1',
        '/([m|l])ice$/i'                => '$1ouse',
        '/(x|ch|ss|sh)es$/i'            => '$1',
        '/(m)ovies$/i'                  => '$1ovie',
        '/(s)eries$/i'                  => '$1eries',
        '/([^aeiouy]|qu)ies$/i'         => '$1y',
        '/([lr])ves$/i'                 => '$1f',
        '/(tive)s$/i'                   => '$1',
        '/(hive)s$/i'                   => '$1',
        '/(li|wi|kni)ves$/i'            => '$1fe',
        '/(shea|loa|lea|thie)ves$/i'    => '$1f',
        '/(^analy)ses$/i'               => '$1sis',
        '/([ti])a$/i'                   => '$1um',
        '/(n)ews$/i'                    => '$1ews',
        '/(h|bl)ouses$/i'               => '$1ouse',
        '/(corpse)s$/i'                 => '$1',
        '/(us)es$/i'                    => '$1',
        '/(us|ss)$/i'                   => '$1',
        '/s$/i'                         => '',

        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i'
                                        => '$1$2sis',
    ];

    private static $irregular = [
        '/move$/i'                      => 'moves',
        '/foot$/i'                      => 'feet',
        '/goose$/i'                     => 'geese',
        '/sex$/i'                       => 'sexes',
        '/child$/i'                     => 'children',
        '/man$/i'                       => 'men',
        '/tooth$/i'                     => 'teeth',
        '/person$/i'                    => 'people',
    ];

    /**
     *
     */
    private static $uncountable = [
        'sheep',
        'fish',
        'deer',
        'series',
        'species',
        'money',
        'rice',
        'information',
        'equipment',
    ];

    /**
     *
     */
    public static function pluralize($str)
    {
        // save some time in the case that singular and plural are the same
        if (in_array(strtolower($str), self::$uncountable)) {
            return $str;
        }

        // check for irregular singular forms
        foreach (self::$irregular as $pattern => $result) {
            if (preg_match($pattern, $str)) {
                return preg_replace($pattern, $result, $str);
            }
        }

        // check for matches using regular expressions
        foreach (self::$plural as $pattern => $result) {
            if (preg_match($pattern, $str)) {
                return preg_replace($pattern, $result, $str);
            }
        }

        return $str;
    }

    /**
     *
     */
    public static function singularize($str)
    {
        // save some time in the case that singular and plural are the same
        if (in_array(strtolower($str), self::$uncountable)) {
            return $str;
        }

        // check for irregular plural forms
        foreach (self::$irregular as $pattern => $result) {
            if (preg_match($pattern, $str)) {
                return preg_replace($pattern, $result, $str);
            }
        }

        // check for matches using regular expressions
        foreach (self::$singular as $pattern => $result) {
            if (preg_match($pattern, $str)) {
                return preg_replace($pattern, $result, $str);
            }
        }

        return $str;
    }

    /**
     * Returns given string as a php_ized word.
     *
     * @param string
     * @return string
     */
    public static function phpize($str, $replacement = '_')
    {
        return preg_replace_callback(
            '/([A-Z-\s]+)/',
            function ($match) use ($replacement) {
                return $replacement . strtolower($match[1]);
            }, lcfirst($str)
        );
    }

    /**
     * Returns given string as a camelCased word.
     *
     * @param string
     * @param boolean wheter to uppercase the first character
     * @return string
     */
    public static function camelize($str, $ucfirst = false)
    {
        $replace = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', strtolower($str))));
        return !$ucfirst ? lcfirst($replace) : $replace;
    }

    /**
     *
     */
    public static function isupper($string)
    {
        return strtoupper($string) === $string;
    }

    /**
     *
     */
    public static function islower($string)
    {
        return strtolower($string) === $string;
    }

    /**
     * Quotes the string so that it can be used as Javascript string constants
     * for example.
     *
     * @param string
     * @return string
     */
    public static function escape($value)
    {
        return strtr($value, ["\r" => '\r',
                              "\n" => '\n',
                              "\'" => '\'',
                              "\t" => '\t',
                              "\'" => '\\\'',
                              "\\" => '\\\\']);
    }

    /**
     * Wrapper around htmlspecialchars() needed to use the charset option
     *
     * @param string
     * @return string
     */
    public static function htmlspecialchars($value)
    {
        return htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
    }

    /**
     * Wrapper around htmlentities() needed to use the charset option
     *
     * @param string
     * @return string
     */
    public static function htmlentities($value)
    {
        return htmlentities($value, ENT_COMPAT, 'utf-8');
    }

    /**
     * Replace all ponctuation from the given string
     *
     * @param string
     * @return string
     */
    public static function removeponctuation($str, $replacement = '')
    {
        return preg_replace('@(\xBB|\xAB|!|\xA1|%|,|:|;|\(|\)|\&|"|\'|\.|-|\/|\?|\\\)@', $replacement, $str);
    }

    /**
     * Translate characters to match \w regex
     *
     * @param string
     * @return string
     */
    public static function accentremove($str)
    {
        return str_replace(['Á','á','à','À','â','Â','ä','Ä','ã','Ã','å','Å','ð'
                           ,'é','É','È','è','Ê','ê','Ë','ë','í','Í','ì','Ì','î'
                           ,'Î','ï','Ï','ñ','Ñ','ó','Ó','Ò','ò','Ô','ô','Ö','ö'
                           ,'õ','Õ','Ú','ú','ù','Ù','û','Û','ü','Ü','ý','Ý','ÿ'
                           ,'Ç','ç'],
 
                           ['A','a','a','A','a','A','a','A','a','A','a','A','o'
                           ,'e','E','E','e','E','e','E','e','i','I','i','I','i'
                           ,'I','i','I','n','N','o','O','O','o','O','o','O','o'
                           ,'o','O','U','u','u','U','u','U','u','U','y','Y','y'
                           ,'C','c'],

                           $str);
    }

    /**
     * Returns the length of the given string. PHP does not play nicelly with
     * accents :/
     *
     * @param string The string being measured for length.
     * @return int The length of the string 
     */
    public static function strlen($str)
    {
        return strlen(static::accentremove($str));
    }

    /**
     * Calculate the similarity between two strings
     *
     * This calculates the similarity between two strings as described in
     * Oliver [1993]. This implementation does not use a stack as in Oliver's
     * pseudo code, but recursive calls which may or may not speed up the whole
     * process. 
     *
     * @param string
     * @param string
     * @return integer similarity in percent
     */
    public static function similar($str1, $str2)
    {
        $return = 0;
        similar_text($str1, $str2, $return);

        return $return;
    }

    /**
     * Returns a random string with the given length and given string of
     * allowed characters.
     *
     * @param integer Password length
     * @param string Allowed chars
     * @return string
     */
    public static function random(
        $length = 8,
        $allowed_chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'
    ) {
        $str = '';
        $len = strlen($allowed_chars) - 1;

        for (;$length > 0; $length--) {
            $str .= $allowed_chars{rand(0, $len)};
        }

        return str_shuffle($str);
    }

    /**
     *
     */
    public static function className($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        return substr(strrchr($class, '\\'), 1) ?: $class;
    }
}
