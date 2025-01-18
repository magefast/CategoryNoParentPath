<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

declare(strict_types=1);

namespace Strekoza\CategoryNoParentPath\Plugin;

use Magento\CatalogUrlRewrite\Model\ProductScopeRewriteGenerator;

class ProductScopeRewriteGeneratorPlugin
{
    /**
     * @param ProductScopeRewriteGenerator $subject
     * @param $result
     * @return bool
     */
    public function afterIsCategoryProperForGenerating(ProductScopeRewriteGenerator $subject, $result): bool
    {
        return false;
    }
}