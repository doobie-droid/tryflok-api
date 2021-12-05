<?php

/**
 * Join paths
 *
 * source: https://stackoverflow.com/a/15575293/7715823
 */
if (!function_exists('join_path')) {
    function join_path()
    {
        $paths = array();

        foreach (func_get_args() as $arg) {
            if ($arg !== '') {
                $paths[] = $arg;
            }
        }

        $prepend = '';
        $n = strlen($paths[0]);
        $e = strpos($paths[0], '://') + 2;
        if ($e > 2) {
            $prepend = substr($paths[0], 0, $e+1);
            $paths[0] = substr($paths[0], $e+1, $n);
        }
        return $prepend . preg_replace('#/+#', '/', join('/', $paths));
    }
}
