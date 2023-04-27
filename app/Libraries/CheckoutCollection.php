<?php

declare(strict_types=1);

namespace App\Libraries;

use Illuminate\Support\Collection;

class CheckoutCollection extends Collection
{
    public function __construct($items = [])
    {
        parent::__construct($items);
    }

    public function getRecursive(string $keys = '')
    {
        if ($keys === '' || !isset($keys)) {
            return $this;
        }

        $val = $this;
        $xpl = explode('.', $keys);
        foreach ($xpl as $key) {
            if (method_exists($val, 'get') && !empty($val->get($key))) {
                $val = is_array($val->get($key)) ? collect($val->get($key)) : $val->get($key);
            } else {
                return null;
            }
        }

        return $val;
    }
}
