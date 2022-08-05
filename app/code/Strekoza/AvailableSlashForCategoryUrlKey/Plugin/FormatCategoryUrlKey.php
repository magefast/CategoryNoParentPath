<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

declare(strict_types=1);

namespace Strekoza\AvailableSlashForCategoryUrlKey\Plugin;

use Magento\Catalog\Model\Category;
use Strekoza\AvailableSlashForCategoryUrlKey\Service\TranslitUrl;

class FormatCategoryUrlKey
{
    /**
     * @var TranslitUrl
     */
    private $translitUrl;

    /**
     * @param TranslitUrl $translitUrl
     */
    public function __construct(TranslitUrl $translitUrl)
    {
        $this->translitUrl = $translitUrl;
    }

    /**
     * @param Category $subject
     * @param callable $proceed
     * @param $str
     * @return mixed
     */
    public function aroundFormatUrlKey(Category $subject, callable $proceed, $str)
    {
        return $this->translitUrl->filter($str);
    }
}