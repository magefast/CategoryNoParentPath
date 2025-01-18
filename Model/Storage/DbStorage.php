<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

declare(strict_types=1);

namespace Strekoza\CategoryNoParentPath\Model\Storage;

use Exception;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException;
use Magento\UrlRewrite\Model\Storage\DbStorage as Magento_DbStorage;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use function in_array;

class DbStorage extends Magento_DbStorage
{
    /**
     * @inheritDoc
     */
    protected function doReplace(array $urls): array
    {
        $this->connection->beginTransaction();

        try {
            $this->deleteOldUrls($urls);

            $data = [];
            $storeId_requestPaths = [];
            foreach ($urls as $url) {
                $storeId = $url->getStoreId();
                $requestPath = $url->getRequestPath();

                // Skip if is exist in the database
                $sql = "SELECT * FROM url_rewrite where store_id = $storeId and request_path = '$requestPath'";
                $exists = $this->connection->fetchOne($sql);

                if ($exists) continue;

                $storeId_requestPaths[] = $storeId . '-' . $requestPath;
                $data[] = $url->toArray();
            }

            // Remove duplication data;
            $n = count($storeId_requestPaths);
            for ($i = 0; $i < $n - 1; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    if ($storeId_requestPaths[$i] == $storeId_requestPaths[$j]) {
                        unset($data[$j]);
                    }
                }
            }
            $this->insertMultiple($data);

            $this->connection->commit();
            // @codingStandardsIgnoreStart
        } catch (AlreadyExistsException $e) {
            // @codingStandardsIgnoreEnd
            $this->connection->rollBack();

            /** @var UrlRewrite[] $urlConflicted */
            $urlConflicted = [];
            foreach ($urls as $url) {
                $urlFound = $this->doFindOneByData(
                    [
                        UrlRewrite::REQUEST_PATH => $url->getRequestPath(),
                        UrlRewrite::STORE_ID => $url->getStoreId(),
                    ]
                );
                if (isset($urlFound[UrlRewrite::URL_REWRITE_ID])) {
                    $urlConflicted[$urlFound[UrlRewrite::URL_REWRITE_ID]] = $url->toArray();
                }
            }
            if ($urlConflicted) {
                throw new UrlAlreadyExistsException(
                    __('URL key for specified store already exists.'),
                    $e,
                    $e->getCode(),
                    $urlConflicted
                );
            } else {
                throw $e->getPrevious() ?: $e;
            }
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }

        return $urls;
    }

    /**
     * Delete old URLs from DB.
     *
     * @param UrlRewrite[] $urls
     * @return void
     */
    private function deleteOldUrls(array $urls): void
    {
        $oldUrlsSelect = $this->connection->select();
        $oldUrlsSelect->from(
            $this->resource->getTableName(self::TABLE_NAME)
        );

        $uniqueEntities = $this->prepareUniqueEntities($urls);
        foreach ($uniqueEntities as $storeId => $entityTypes) {
            foreach ($entityTypes as $entityType => $entities) {
                $oldUrlsSelect->orWhere(
                    $this->connection->quoteIdentifier(
                        UrlRewrite::STORE_ID
                    ) . ' = ' . $this->connection->quote($storeId, 'INTEGER') .
                    ' AND ' . $this->connection->quoteIdentifier(
                        UrlRewrite::ENTITY_ID
                    ) . ' IN (' . $this->connection->quote($entities, 'INTEGER') . ')' .
                    ' AND ' . $this->connection->quoteIdentifier(
                        UrlRewrite::ENTITY_TYPE
                    ) . ' = ' . $this->connection->quote($entityType)
                );
            }
        }

        // prevent query locking in a case when nothing to delete
        $checkOldUrlsSelect = clone $oldUrlsSelect;
        $checkOldUrlsSelect->reset(Select::COLUMNS);
        $checkOldUrlsSelect->columns('count(*)');
        $hasOldUrls = (bool)$this->connection->fetchOne($checkOldUrlsSelect);

        if ($hasOldUrls) {
            $this->connection->query(
                $oldUrlsSelect->deleteFromSelect(
                    $this->resource->getTableName(self::TABLE_NAME)
                )
            );
        }
    }

    /**
     * Prepare array with unique entities
     *
     * @param UrlRewrite[] $urls
     * @return array
     */
    private function prepareUniqueEntities(array $urls): array
    {
        $uniqueEntities = [];
        /** @var UrlRewrite $url */
        foreach ($urls as $url) {
            $entityIds = (!empty($uniqueEntities[$url->getStoreId()][$url->getEntityType()])) ?
                $uniqueEntities[$url->getStoreId()][$url->getEntityType()] : [];

            if (!in_array($url->getEntityId(), $entityIds)) {
                $entityIds[] = $url->getEntityId();
            }
            $uniqueEntities[$url->getStoreId()][$url->getEntityType()] = $entityIds;
        }

        return $uniqueEntities;
    }
}
