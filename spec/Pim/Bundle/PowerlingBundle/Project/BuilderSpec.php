<?php

namespace spec\Pim\Bundle\PowerlingBundle\Project;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\PowerlingBundle\Project\BuilderInterface;
use Pim\Bundle\PowerlingBundle\Project\Exception\RuntimeException;
use Pim\Bundle\PowerlingBundle\Project\ProjectInterface;
use Akeneo\Pim\Structure\Component\AttributeTypes;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Psr\Log\LoggerInterface;
use Akeneo\Tool\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use Symfony\Component\DependencyInjection\Container;
use Akeneo\Pim\Structure\Bundle\Doctrine\ORM\Repository\AttributeRepository;

/**
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class BuilderSpec extends ObjectBehavior
{
    function let(ConfigManager $configManager, ObjectDetacherInterface $objectDetacher, LoggerInterface $logger, Container $container)
    {
        $this->beConstructedWith($configManager, $objectDetacher, $logger, $container, []);
    }

    function it_is_initializable()
    {
        $this->shouldImplement(BuilderInterface::class);
    }

    function it_creates_project_data(
        ProjectInterface $project
    ) {
        $project->getName()->willReturn('fooname');
        $project->getLangAssociationId()->willReturn('fr_FRen_US');

        $expected = [
            'name'            => 'fooname',
            'lang_association_id' => 'fr_FRen_US',
        ];

        $this->createProjectData($project)
            ->shouldReturnThisArray($expected);
    }

    function it_checks_configuration(
        ProductInterface $product,
        ValueInterface $productValue1,
        AttributeInterface $attribute1,
        $configManager
    ) {
        $configManager->get('pim_powerling.attributes')
            ->willReturn('');
        $localeCode = 'en_US';

        $productValue1->getLocaleCode()->willReturn($localeCode);
        $productValue1->getScopeCode()->willReturn('ecommerce');
        $productValue1->getData()->willReturn('lorem ipsum');
        $productValue1->getAttributeCode()->willReturn('att1');
        $productValue1->isScopable()->willReturn(true);

        $attribute1->getCode()->willReturn('att1');
        $attribute1->getType()->willReturn(AttributeTypes::TEXT);
        $attribute1->isLocalizable()->willReturn(true);
        $attribute1->isWysiwygEnabled()->willReturn(true);

	$product->getUsedAttributeCodes()->willReturn(['att1']);
        $product->getValues()->willReturn([
            $productValue1,
        ]);
        $product->getIdentifier()->willReturn('fooSku');

        $this->shouldThrow(new RuntimeException('No attributes configured for translation'))
            ->during('createDocumentData', [$product, $localeCode]);
    }

    function it_creates_document_data(
        ProductInterface $product,
        ValueInterface $productValue1,
        AttributeInterface $attribute1,
        ValueInterface $productValue2,
        ValueInterface $productValue3,
        AttributeInterface $attribute3,
        ValueInterface $productValue4,
        AttributeInterface $attribute4,
        ValueInterface $productValue5,
        AttributeInterface $attribute5,
	AttributeRepository $attributeRepository,
	$configManager,
	$container
    ) {
        $configManager->get('pim_powerling.attributes')
            ->willReturn('att1,att2,att5');
        $localeCode = 'en_US';

        $productValue1->getLocaleCode()->willReturn($localeCode);
        $productValue1->getScopeCode()->willReturn('ecommerce');
        $productValue1->getData()->willReturn('lorem ipsum');
        $productValue1->getAttributeCode()->willReturn('att1');
        $productValue1->isScopable()->willReturn(true);

        $attribute1->getCode()->willReturn('att1');
        $attribute1->getType()->willReturn(AttributeTypes::TEXT);
        $attribute1->isLocalizable()->willReturn(true);
        $attribute1->isWysiwygEnabled()->willReturn(true);

        $productValue2->getLocaleCode()->willReturn($localeCode);
        $productValue2->getScopeCode()->willReturn('mobile');
        $productValue2->getData()->willReturn('foobar foobaz');
        $productValue2->getAttributeCode()->willReturn('att1');
        $productValue2->isScopable()->willReturn(true);

        $productValue3->getLocaleCode()->willReturn($localeCode);
        $productValue3->getAttributeCode()->willReturn('att3');
        $productValue3->isScopable()->willReturn(true);

        $attribute3->getCode()->willReturn('att3');
        $attribute3->getType()->willReturn(AttributeTypes::BOOLEAN);
        $attribute3->isLocalizable()->willReturn(true);
        $attribute3->isWysiwygEnabled()->willReturn(true);

        $productValue4->getLocaleCode()->willReturn($localeCode);
        $productValue4->getAttributeCode()->willReturn('att4');
        $productValue4->isScopable()->willReturn(true);

        $attribute4->getCode()->willReturn('att4');
        $attribute4->getType()->willReturn(AttributeTypes::TEXT);
        $attribute4->isLocalizable()->willReturn(false);
        $attribute4->isWysiwygEnabled()->willReturn(true);

        $productValue5->getLocaleCode()->willReturn($localeCode);
        $productValue5->getAttributeCode()->willReturn('att5');
        $productValue5->getData()->willReturn('attribute5 data');
        $productValue5->isScopable()->willReturn(false);

        $attribute5->getCode()->willReturn('att5');
        $attribute5->getType()->willReturn(AttributeTypes::TEXT);
        $attribute5->isLocalizable()->willReturn(true);
        $attribute5->isWysiwygEnabled()->willReturn(true);

	$product->getUsedAttributeCodes()->willReturn(['att1', 'att2', 'att5']);
	$attributeRepository->findOneByIdentifier('att1')->willReturn($attribute1);
	$attributeRepository->findOneByIdentifier('att3')->willReturn($attribute3);
	$attributeRepository->findOneByIdentifier('att4')->willReturn($attribute4);
	$attributeRepository->findOneByIdentifier('att5')->willReturn($attribute5);
	$container->get('pim_catalog.repository.attribute')->willReturn($attributeRepository);

        $product->getValues()->willReturn([
            $productValue1,
            $productValue2,
            $productValue3,
            $productValue4,
            $productValue5,
        ]);
        $product->getIdentifier()->willReturn('fooSku');

        $expected = [
            'title'              => 'fooSku',
            'original_content'   => [
                'att1-ecommerce' => ['original_phrase' => 'lorem ipsum'],
                'att1-mobile'    => ['original_phrase' => 'foobar foobaz'],
                'att5'           => ['original_phrase' => 'attribute5 data'],
            ],
            'markup_in_content'  => true,
        ];

        $this->createDocumentData($product, $localeCode)
            ->shouldReturnThisArray($expected);
    }

    function it_does_not_creates_document_data_with_empty_content(
        ProductInterface $product,
        ValueInterface $productValue1,
        AttributeInterface $attribute1,
        ValueInterface $productValue2,
        ValueInterface $productValue3,
        AttributeInterface $attribute3,
	AttributeRepository $attributeRepository,
        $configManager,
        $container
    ) {
        $configManager->get('pim_powerling.attributes')
            ->willReturn('att1,att2,att5');
        $localeCode = 'en_US';

        $productValue1->getLocaleCode()->willReturn($localeCode);
        $productValue1->getScopeCode()->willReturn('ecommerce');
        $productValue1->getData()->willReturn(null);
        $productValue1->getAttributeCode()->willReturn('att1');
        $productValue1->isScopable()->willReturn(true);

        $attribute1->getCode()->willReturn('att1');
        $attribute1->getType()->willReturn(AttributeTypes::TEXT);
        $attribute1->isLocalizable()->willReturn(true);
        $attribute1->isWysiwygEnabled()->willReturn(false);

        $productValue2->getLocaleCode()->willReturn($localeCode);
        $productValue2->getScopeCode()->willReturn('mobile');
        $productValue2->getData()->willReturn(null);
        $productValue2->getAttributeCode()->willReturn('att1');
        $productValue2->isScopable()->willReturn(true);

        $productValue3->getLocaleCode()->willReturn($localeCode);
        $productValue3->getAttributeCode()->willReturn('att3');
        $productValue3->isScopable()->willReturn(true);

        $attribute3->getCode()->willReturn('att3');
        $attribute3->getType()->willReturn(AttributeTypes::BOOLEAN);
        $attribute3->isLocalizable()->willReturn(true);

	$product->getUsedAttributeCodes()->willReturn(['att1', 'att3']);

        $product->getValues()->willReturn([
            $productValue1,
            $productValue2,
            $productValue3,
        ]);
        $product->getIdentifier()->willReturn('fooSku');
	$attributeRepository->findOneByIdentifier('att1')->willReturn($attribute1);
	$attributeRepository->findOneByIdentifier('att3')->willReturn($attribute3);
	$container->get('pim_catalog.repository.attribute')->willReturn($attributeRepository);

        $this->createDocumentData($product, $localeCode)
            ->shouldReturn(null);
    }

    public function getMatchers(): array
    {
        return [
            'returnThisArray' => function ($subject, $expected) {
                return $subject == $expected;
            },
        ];
    }
}
