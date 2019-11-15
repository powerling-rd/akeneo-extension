<?php

namespace spec\Pim\Bundle\PowerlingBundle\Product;

use PhpSpec\ObjectBehavior;
use Pim\Bundle\PowerlingBundle\Product\UpdaterInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Repository\ProductRepositoryInterface;
use Pim\Component\Catalog\Updater\ProductUpdater;

/**
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class UpdaterSpec extends ObjectBehavior
{
    function let(ProductRepositoryInterface $productRepository, ProductUpdater $productUpdater)
    {
        $this->beConstructedWith($productRepository, $productUpdater);
    }

    function it_is_initializable()
    {
        $this->shouldImplement(UpdaterInterface::class);
    }

    function it_updates_a_document(
        ProductInterface $product,
        $productRepository,
        $productUpdater
    )
    {
        $localeCode = 'en_US';

        $productRepository->findOneByIdentifier('product-sku')->willReturn($product);

        $document = [
            'foo-ecommerce' => 'foo ecommerce translation',
            'foo-mobile'    => 'foo mobile translation',
            'bar-ecommerce' => 'bar ecommerce translation',
        ];

        $data = [
            'foo' => [
                ['locale' => 'en_US', 'scope' => 'ecommerce', 'data' => 'foo ecommerce translation'],
                ['locale' => 'en_US', 'scope' => 'mobile', 'data' => 'foo mobile translation'],
            ],
            'bar' => [
                ['locale' => 'en_US', 'scope' => 'ecommerce', 'data' => 'bar ecommerce translation'],
            ],
        ];

        $productUpdater->update($product, ['values' => $data])->shouldBeCalled();
        $this->update('product-sku', $document, $localeCode);
    }
}
