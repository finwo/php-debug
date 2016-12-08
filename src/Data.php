<?php

namespace Finwo\Debug;

class Data
{
    /**
     * @param mixed $input
     *
     * @return array
     */
    static function x2array($input, $depth = -1)
    {
        if ($depth === 0) {
            return 'Too deep';
        }
        $output = $input;
        switch (gettype($output)) {
            case 'object':
                if (method_exists($output,'toArray')) {
                    $output = $output->toArray();
                } else {
                    $a      = (array)$input;
                    $b      = get_object_vars($input);
                    $output = (count($a)>count($b))?$a:$b;
                }
            case 'array':
                foreach ($output as $key => $value) {
                    $output[$key] = self::x2array($value, $depth-1);
                }
            default:
                return $output;
                break;
        }
    }

    /**
     * @param string       $path
     * @param array|object $data
     * @param string|null  $default
     *
     * @return mixed
     */
    public static function sget($path, $data, $default = null)
    {
        // Fetch required variables
        $data = self::x2array($data);
        $keys = explode('.',$path);

        // Follow the keys
        foreach ($keys as $key) {
            if (!isset($data[$key])) return $default;
            $data = $data[$key];
        }

        // Return the data we've found
        return $data;
    }

    /**
     * Returns true if at least one of the values is true-like
     *
     * @param array $permissions
     * @param array $data
     *
     * @return bool
     */
    public static function sHasOne(array $permissions, $data)
    {
        foreach ($permissions as $permission) {
            if (self::sget($permission, $data)) return true;
        }
        return false;
    }

    /**
     * @param string      $path
     * @param object|null $data
     * @param string|null $default
     *
     * @return mixed
     */
    protected function get($path, $data = null, $default = null)
    {
        // Allow inserting data, default to $this
        if (is_null($data)) {
            $data = self::x2array(get_object_vars($this));
        } else {
            $data = self::x2array($data);
        }

        return self::sget($path, $data, $default);
    }

    /**
     * @param array $numbers
     *
     * @return float|int
     */
    public static function sum( $numbers = array() )
    {
        $total = 0;
        foreach ($numbers as $number) {
            $total += floatval($number);
        }
        return $total;
    }

    /**
     * @param array $numbers
     *
     * @return float|int
     */
    public static function avg( $numbers = array() )
    {
        $count = count($numbers);
        if (!$count) {
            return 0;
        }
        return self::sum($numbers) / count($numbers);
    }

    /**
     * @param array $strings
     *
     * @return int
     */
    public static function maxLength( $strings = array() )
    {
        $length = 0;
        foreach ($strings as $string) {
            $length = max(strlen($string),$length);
        }
        return $length;
    }
}
