<?php

namespace App\Library\Helpers;

class Helpers
{
    /**
     * Flatten the multidimensional array
     *
     * @param $array
     * @return array|false
     */
    public static function array_flatten($array) {
        if (!is_array($array)) {
            return FALSE;
        }
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::array_flatten($value));
            }
            else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Count of finished items
     *
     * @param $array
     * @return int
     */
    public static function getProcessedItemCount($array)
    {
        $total = 0;
        foreach ($array as $item) {
            if (str_contains($item,'finished and placed')){
                $total++;
            }
        }
        return $total;
    }

}
