<?php

namespace Pim\Bundle\PowerlingBundle\Project;

use Pim\Component\Catalog\Model\ProductInterface;

/**
 * Powerling builder.
 * Can build project and document payload from PIM data
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
interface BuilderInterface
{
    /**
     * @param ProjectInterface $project
     *
     * @return array
     */
    public function createProjectData(ProjectInterface $project);

    /**
     * @param ProductInterface $product
     * @param $sourceLocale
     * @return array
     */
    public function createDocumentData(ProductInterface $product, $sourceLocale);
}
