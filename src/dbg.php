<?php

/*
 * Quick & dirty debugging tool
 */

class dbg
{
    /**
     * @param              $haystack
     * @param string|array $needle
     *
     * @return array
     */
    public static function _searchValue($haystack, $needle) {

        // Handle array needles
        if (is_array($needle)) {
            $result = $haystack;
            foreach ($needle as $str) {
                $result = self::_searchValue($result, $str);
            }
            return $result;
        }

        // Default behavior
        $result = array();
        $search = (array)$haystack;
        foreach ($search as $key => $value) {
            if (is_string($value)&&strpos(strtolower($value), strtolower($needle))!==false) {
                $result[$key] = $value;
                continue;
            }
            if (is_array($value)||is_object($value)) {
                $tmp = self::_searchValue($value, $needle);
                if (count($tmp)) {
                    $result[$key] = $tmp;
                }
            }
        }
        return $result;
    }

    public static function dump($input, $singleline = true, $depth = 0) {

        if ($depth==0) {
            print('<div style="font-size:12px;font-family:Menlo,Monaco,Consolas,&quot;Courier New&quot;,monospace;' .
                (($singleline)?('white-space:nowrap;'):('')).
                '">');
        }

        printf("<b>%s</b>", $type=gettype($input));

        if ($type=='object') {
            printf(" <i>%s</i>", get_class($input));
        }

        $colors = array(
            'indent' => '#DDD',
            'string' => array(
                'sys'    => '#000',
                'length' => '#88F',
                'value'  => '#D00',
            ),
            'integer' => array(
                'value'  => '#880',
            ),
            'float' => array(
                'value'  => '#D44',
            ),
            'double' => array(
                'value'  => '#D0D',
            ),
        );

        switch($type) {
            case 'array':
            case 'object':
                printf(" (%s)", count($input));
                foreach($input as $key => $value) {
                    print("<br />\n");
                    print(str_repeat(sprintf(
                        '<p style="display:inline;color:%s;">|&nbsp;</p>',
                        $colors['indent']
                    ), $depth+1));
                    printf("[%s] => ", $key);
                    self::dump($value, $singleline, $depth+1);
                }
                break;
            case 'string':
                $input = htmlspecialchars($input, ENT_QUOTES);
                $input = str_replace(" ", "&nbsp;", $input);
                $input = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $input);
                $input = str_replace("\r\n", "\n", $input);
                $input = str_replace("\r", "\n", $input);
                if ($singleline) {
                    $input = str_replace("\n", sprintf("<b style='color:%s;'>&crarr;</b>", $colors['string']['sys']), $input);
                } else {
                    $input = str_replace("\n", "<br />\n", $input);
                }
                printf(
                    ':<p style="display:inline;color:%s;">%d</p> <p style="display:inline;color:%s;">%s</p>',
                    $colors['string']['length'],
                    strlen($input),
                    $colors['string']['value'],
                    $input
                );
                break;
            case 'integer':
            case 'float':
            case 'double':
                printf(
                    ' <p style="display:inline;color:%s;">%'.(($type=='integer')?('d'):('.3f')).'</p>',
                    $colors[$type]['value'],
                    $input
                );
                break;
        }

        if ($depth==0) {
            print('</div>');
        }
    }
}
