<?php

namespace App\Helpers;

use App\Models\Front\Catalog\Options\Options;

class OptionHelper
{

    /**
     * @param Options|null $option
     *
     * @return string
     */
    public static function getStyle(Options $option = null): string
    {
        if ($option->value_opt) {
            return 'background: linear-gradient(45deg, ' . $option->value . ' 50%, ' . $option->value_opt . ' 50%);';
        }

        return 'background-color:' . ($option->value ?? '');
    }
}
