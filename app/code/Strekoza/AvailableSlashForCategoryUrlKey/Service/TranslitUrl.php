<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

declare(strict_types=1);

namespace Strekoza\AvailableSlashForCategoryUrlKey\Service;

use Magento\Framework\Filter\Translit;

class TranslitUrl extends Translit
{
    /**
     * Filter value
     *
     * @param string $string
     * @return string
     */
    public function filter($string)
    {
        $string = preg_replace('#[^0-9a-z-\/]+#i', '-', parent::filter($string));
        $string = strtolower($string);
        $string = trim($string, '-');

        return $string;
    }
}