<?php

namespace Finwo\Debug;

class Debug
{


    public static $dump_max_depth  = 8;
    public static $dump_max_length = 48;
    public static $output = null;

    /**
     * Prints data & overrules all output after the first call.
     *
     * Arguments: see sprintf documentation
     */
    static function printf()
    {

        // Sorry folks, this only works in development mode
        if (!isset($_ENV['IS_DEVEL']) || !$_ENV['IS_DEVEL']) {
            return;
        }

        if (isset($_SERVER['argc'])) {
            // Command line
            call_user_func_array('printf', func_get_args());
        } else {
            // Probably web instance

            // Init output overruling
            if (is_null(self::$output)) {
                self::$output = '';
                ob_start(function ($buffer) {
                    header("Content-Type: text/html");

                    return self::$output;
                });
            }

            self::$output .= call_user_func_array('sprintf', func_get_args());
        }
    }

    /**
     * @param array   $data
     * @param boolean $html
     */
    static function consoleTable($data, $html = null)
    {
        $html = is_null($html) ? !isset($_SERVER['argc']) : !!$html;

        if ($html) {
            self::printf("<pre>");
        }
        $edges = array(
            "top-left"     => "┌─",
            "top"          => "─┬─",
            "top-right"    => "─┐",
            "right"        => "─┤",
            "bottom-right" => "─┘",
            "bottom"       => "─┴─",
            "bottom-left"  => "└─",
            "left"         => "├─",
            "horizontal"   => "─",
            "vertical"     => " │ ",
            "middle"       => "─┼─",
        );

        $column = array();

        // Fetch columns & their max length
        foreach ($data as &$row) {
            foreach ($row as $key => &$value) {
                if (!isset($column[$key])) $column[$key] = max(4,strlen($key));
                if (is_array($value)) $value = implode(',',array_values($value));
                $column[$key] = max($column[$key], strlen($value));
            }
        }

        // Print header
        self::printf($edges['top-left']);
        self::printf(implode($edges['top'], array_map(function($length) use ($edges) {
            return str_repeat($edges['horizontal'], $length);
        },$column)));
        self::printf($edges['top-right']);
        self::printf("\n");
        self::printf(ltrim($edges['vertical']) . implode($edges['vertical'],array_map(function($length, $key) use ($edges) {
                return $key . str_repeat(' ', $length-strlen($key));
            }, $column, array_keys($column))) . rtrim($edges['vertical']));
        self::printf("\n");

        // Print rows
        foreach ($data as $row) {
            // Seperator
            self::printf($edges['left']);
            self::printf(implode($edges['middle'], array_map(function($length) use ($edges) {
                return str_repeat($edges['horizontal'], $length);
            },$column)));
            self::printf($edges['right']);
            self::printf("\n");

            // Data.php
            self::printf(ltrim($edges['vertical']));
            self::printf(implode($edges['vertical'], array_map(function($length, $key) use ($row) {
                $data = isset($row[$key]) ? $row[$key] : 'NULL';
                if (is_numeric($data)) {
                    return str_repeat(' ', $length-strlen($data)) . $data;
                } else {
                    return $data . str_repeat(' ', $length-strlen($data));
                }
            },$column,array_keys($column))));
            self::printf(rtrim($edges['vertical']));
            self::printf("\n");
        }

        // Closing
        self::printf($edges['bottom-left']);
        self::printf(implode($edges['bottom'], array_map(function($length) use ($edges) {
            return str_repeat($edges['horizontal'], $length);
        },$column)));
        self::printf($edges['bottom-right']);
        self::printf("\n");

        if ($html) {
            self::printf("</pre>");
        }
    }

    /**
     * Displays a semi-readable version of a variable.
     *
     * @param        $input
     * @param bool   $singleline
     * @param string $indent
     */
    static function dump($input, $singleline = true, $indent = '')
    {
        $html = !isset($_SERVER['argc']);

        $colors = array(
            'indent'  => '#CCC',
            'string'  => array(
                'sys'    => '#000',
                'length' => '#88F',
                'value'  => '#D00',
            ),
            'integer' => array( 'value' => '#880' ),
            'float'   => array( 'value' => '#D44' ),
            'double'  => array( 'value' => '#D44' ),
            'boolean' => array( 'value' => '#D0D' )
        );

        $t = array(
            "fg" => array(
                "black"   => "\e[30m",
                "red"     => "\e[31m",
                "green"   => "\e[32m",
                "yellow"  => "\e[33m",
                "blue"    => "\e[34m",
                "magenta" => "\e[35m",
                "cyan"    => "\e[36m",
                "white"   => "\e[37m",
                "default" => "\e[39m",
            ),
            "bg" => array(
                "black"   => "\e[40m",
                "red"     => "\e[41m",
                "green"   => "\e[42m",
                "yellow"  => "\e[43m",
                "blue"    => "\e[44m",
                "magenta" => "\e[45m",
                "cyan"    => "\e[46m",
                "white"   => "\e[47m",
                "default" => "\e[49m",
            ),
        );

        $template = array(
            "header"        => $html ? '<div style="font-size:12px;font-family:Menlo,Monaco,Consolas,&quot;Courier New&quot;,monospace;'.(($singleline)?('white-space:nowrap;'):('')).'">' : $t['bg']['default'].$t['fg']['default'],
            "footer"        => $html ? '</div><br />' : $t['bg']['default'].$t['fg']['default']."\n",
            "type"          => $html ? '<b>%s</b>' : $t['fg']['red'].'%s'.$t['fg']['default'],
            "class"         => $html ? ' <i>%s</i>' : $t['fg']['blue'].' %s'.$t['fg']['default'],
            "count"         => ' (%s)',
            "linebreak"     => $html ? "<br />\n" : "\n",
            "too_deep"      => ' ...',
            "too_long"      => $html ? '<i>(%s more values)</i>' : '(%s more values)',
            "key"           => $html ? sprintf('[%%s] <div style="display:inline;color:%s;">_%%s</div> => ', $colors['indent']) : '[%s] _%s => ',
            "branch"        => array(
                "default" => $html ? '<div style="display:inline;color:%s;">&nbsp;&#x251C;&#x2500;&nbsp;</div>' : ' ├─ ',
                "last"    => $html ? '<div style="display:inline;color:%s;">&nbsp;&#x2514;&#x2500;&nbsp;</div>' : ' └─ ',
            ),
            "indent"        => array(
                "default" => $html ? '<div style="display:inline;color:%s;">&nbsp;&#x2502;&nbsp;&nbsp;</div>' : ' │  ',
                "last"    => $html ? '<div style="display:inline;color:%s;">&nbsp;&nbsp;&nbsp;&nbsp;</div>'   : '    ',
            ),
            "chars"         => array(
                "space" => $html ? '&nbsp;' : ' ',
                "tab"   => $html ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '    ',
                "\r\n"  => $singleline ? ( $html ? sprintf('"<b style=\'color:%s;\'>&crarr;</b>"',$colors['string']['sys']) : "\\r\\n" ) : ( $html ? "<br />\n" : "\n" ),
                "\r"    => $singleline ? ( $html ? sprintf('"<b style=\'color:%s;\'>&crarr;</b>"',$colors['string']['sys']) : "\\r" ) : ( $html ? "<br />\n" : "\n" ),
                "\n"    => $singleline ? ( $html ? sprintf('"<b style=\'color:%s;\'>&crarr;</b>"',$colors['string']['sys']) : "\\n" ) : ( $html ? "<br />\n" : "\n" ),
            ),
            "string_length" => array(
                "start" => $html ? sprintf(" <p style=\"display:inline;color:%s;\">",$colors['string']['length']) : ' '.$t['fg']['yellow'],
                "value" => '%d',
                "end"   => $html ? '</p>' : $t['fg']['default']
            ),
            "string_value"  => array(
                "start" => $html ? sprintf(' <p style="display:inline;color:%s;">',$colors['string']['value']) : ' '.$t['fg']['green'],
                "value" => '%s',
                "end"   => $html ? '</p>' : $t['fg']['default'],
            ),
            "boolean_start" => $html ? sprintf(' <p style="display:inline;color:%s;">',$colors['boolean']['value']) : $t['fg']['cyan'],
            "boolean_value" => ' %s',
            "boolean_end"   => $html ? '</p>' : $t['fg']['default'],
            "float"         => array(
                "start" => $html ? ' <p style="display:inline;color:%s;">' : ' '.$t['fg']['magenta'],
                "value" => '%.3f',
                "end"   => $html ? '</p>' : $t['fg']['default'],
            ),
        );

        $template['double']  = $template['float'];
        $template['integer'] = $template['float'];
        $template['integer']['value'] = '%d';

        // Sorry folks, this only works in development mode
        if (!isset($_ENV['IS_DEVEL']) || !$_ENV['IS_DEVEL']) {
            return;
        }

        if (!strlen($indent)) {
            self::printf($template['header']);
        }
        self::printf($template['type'], $type = gettype($input));
        if ($type == 'object') {
            self::printf($template['class'], get_class($input));
            $input = Data::x2array($input, self::$dump_max_depth+1);
        }

        switch ($type) {
            case 'object':
            case 'array':
                self::printf($template['count'], count($input));
                $length    = 0;
                $keys      = array_keys($input);
                $keyLength = Data::maxLength($keys);
                while(count($keys)) {
                    $key = array_shift($keys);
                    self::printf($template['linebreak']);
                    self::printf("%s%s", $indent, sprintf($template['branch'][count($keys)?'default':'last'], $colors['indent']));
                    self::printf($template['key'], $key, str_repeat('_', $keyLength-strlen($key)));

                    if ((++$length) > self::$dump_max_length) {
                        self::printf($template['too_long'], $length - self::$dump_max_length);
                        break;
                    }

                    self::dump($input[$key], $singleline, $indent . str_replace('%s',$colors['indent'],$template['indent'][count($keys)?'default':'last']) );
                }
                break;
            case 'string':
                $input = $html ? htmlspecialchars($input, ENT_QUOTES) : $input;
                $input = str_replace(" ", $template['chars']['space'], $input);
                $input = str_replace("\t", $template['chars']['tab'], $input);
                $input = str_replace("\r\n", $template['chars']["\r\n"], $input);
                $input = str_replace("\r", $template['chars']["\r"], $input);
                $input = str_replace("\n", $template['chars']["\n"], $input);

                self::printf($template['string_length']['start']);
                self::printf($template['string_length']['value'], strlen($input));
                self::printf($template['string_length']['end']);

                self::printf($template['string_value']['start']);
                self::printf($template['string_value']['value'], $input);
                self::printf($template['string_value']['end']);

                break;
            case 'boolean':
                self::printf($template['boolean_start']);
                self::printf($template['boolean_value'], $input ? 'true' : 'false');
                self::printf($template['boolean_end']);
                break;
            case 'integer':
            case 'float':
            case 'double':
                self::printf($template[$type]['start'], $colors[$type]['value']);
                self::printf($template[$type]['value'], $input);
                self::printf($template[$type]['end']);
                break;
        }
        if (!strlen($indent)) {
            self::printf($template['footer']);
        }
    }
}
