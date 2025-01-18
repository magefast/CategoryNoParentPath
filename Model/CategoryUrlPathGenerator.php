<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

declare(strict_types=1);

namespace Strekoza\Model;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator as Magento_CategoryUrlPathGenerator;
use Magento\Framework\Model\AbstractModel;

class CategoryUrlPathGenerator extends Magento_CategoryUrlPathGenerator
{
    /**
     * @param CategoryInterface|AbstractModel $category
     * @param null $parentCategory
     * @return string|null
     */
    public function getUrlPath($category, $parentCategory = null)
    {
        if (in_array($category->getParentId(), [Category::ROOT_CATEGORY_ID, Category::TREE_ROOT_ID])) {
            return '';
        }

        $path = $category->getUrlPath();
        if ($path !== null && !$category->dataHasChangedFor('url_key') && !$category->dataHasChangedFor('parent_id')) {
            return $path;
        }

        $path = $category->getUrlKey();
        if ($path === false) {
            return $category->getUrlPath();
        }

        return $path;
    }
}