<?php

namespace Pim\Bundle\PowerlingBundle\Product;

use Akeneo\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Exception;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Repository\ProductRepositoryInterface;

/**
 * Update Akeneo product from Powerling data
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class Updater implements UpdaterInterface
{
    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var ObjectUpdaterInterface */
    protected $productUpdater;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ObjectUpdaterInterface     $productUpdater
     */
    public function __construct(ProductRepositoryInterface $productRepository, ObjectUpdaterInterface $productUpdater)
    {
        $this->productRepository = $productRepository;
        $this->productUpdater    = $productUpdater;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function update($documentId, $document, $localeCode)
    {
        $product = $this->findRelatedProduct($documentId);

        $data = [];

        foreach ($document as $key => $content) {
            list($attributeCode, $channelCode) = $this->extractAttributeAndChannel($key);
            $data[$attributeCode][] = [
                'locale' => $localeCode,
                'scope'  => $channelCode,
                'data'   => $content,
            ];
        }

        $this->productUpdater->update($product, ['values' => $data]);

        return $product;
    }

    /**
     * Find product from a document
     *
     * @param $document
     *
     * @return null|ProductInterface
     */
    protected function findRelatedProduct($sku)
    {
        $repo = $this->productRepository;

        return $repo->findOneByIdentifier($sku);
    }

    /**
     * Extract attribute code and locale from a document key
     *
     * @param string $powerlingKey
     *
     * @return string[]
     * @throws \Exception
     */
    protected function extractAttributeAndChannel($powerlingKey)
    {
        if (!preg_match('/^([^-]+)(?:-([^-]+))?$/', $powerlingKey, $matches)) {
            throw new Exception(
                sprintf('Cannot extract attribute code and channel from key %s', $powerlingKey)
            );
        }
        $attributeCode = $matches[1];
        $channelCode   = isset($matches[2]) ? $matches[2] : null;

        return [
            $attributeCode,
            $channelCode,
        ];
    }
}
