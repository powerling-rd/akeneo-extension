<?php

namespace Pim\Bundle\PowerlingBundle\Product;

use Pim\Component\Catalog\Model\ProductInterface;

/**
 * Update Akeneo product from Powerling data
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/

interface UpdaterInterface
{
    /**
     * Update a product from a document
     *
     * @param $documentId
     * @param   $document
     * @param                   $localeCode
     *
     * @return null|ProductInterface
     */
    public function update($documentId, $document, $localeCode);
}
