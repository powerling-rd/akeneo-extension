<?php

namespace Pim\Bundle\PowerlingBundle\Project;

use Akeneo\Pim\Enrichment\Component\Product\Model\EntityWithValuesInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModel;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Pim\Structure\Bundle\Doctrine\ORM\Repository\AttributeRepository;
use Akeneo\Tool\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use Doctrine\Common\Util\ClassUtils;
use Exception;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Pim\Bundle\PowerlingBundle\Project\Exception\RuntimeException;
use Akeneo\Pim\Structure\Component\AttributeTypes;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Akeneo\Pim\Structure\Component\Repository\AttributeRepositoryInterface;

/**
 * Powerling builder.
 * Can build project and document payload from PIM data
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class Builder implements BuilderInterface
{
    /** @var array */
    protected $options = [];

    /** @var ConfigManager */
    protected $configManager;

    /** @var LoggerInterface */
    protected $logger;

    /** @var array */
    protected $powerlingAttributes;

    /** @var array */
    protected $availableAttributes = [];

    /** @var ObjectDetacherInterface */
    protected $objectDetacher;

    /** @var AttributeRepository */
    protected $attributeRepository;

    /** @var array */
    protected $attributes = [];

    /**
     * @param ConfigManager $configManager
     * @param ObjectDetacherInterface $objectDetacher
     * @param LoggerInterface $logger
     * @throws Exception
     */
    public function __construct(ConfigManager $configManager, ObjectDetacherInterface $objectDetacher, LoggerInterface $logger, AttributeRepositoryInterface $attributeRepository)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve([]);
        $this->configManager = $configManager;
        $this->objectDetacher = $objectDetacher;
        $this->logger = $logger;
        $this->attributeRepository  = $attributeRepository;
    }

    /**
     * @inheritdoc
     */
    public function createProjectData(ProjectInterface $project)
    {
        $data = [
            'name'            => $project->getName(),
            'lang_association_id' => $project->getLangAssociationId(),
        ];

        $this->logger->debug(sprintf('Create project data: %s', json_encode($data)));

        return $data;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function createDocumentData($product, $localeCode)
    {
        $docData = $this->getProductValuesTitle($product);
        $originalContent = [];
        $wysiwyg         = false;

        /** @var ValueInterface $productValue */
        foreach ($docData['product_values'] as $productValue) {
            $code = $productValue->getAttributeCode();
            $attribute = $this->getAttributeByCode($code);

            if (
                $this->isValidForTranslation($attribute)
                && $localeCode === $productValue->getLocaleCode()
            ) {
                $key            = $this->createProductValueKey($productValue);
                $originalPhrase = trim($productValue->getData());

                if ($attribute->isWysiwygEnabled()) {
                    $wysiwyg = true;
                }
                if (!empty($originalPhrase)) {
                    $originalContent[$key]['original_phrase'] = $originalPhrase;
                }
            }
        }
        $documentData = [
            'title'              => $docData['title'],
            'original_content'   => $originalContent,
            'markup_in_content'  => $wysiwyg,
        ];

        if (empty($originalContent)) {
            return null;
        }

        $this->logger->debug(sprintf('Create document data: %s', json_encode($documentData)));
        return $documentData;
    }

    /**
     * getProductValuesTitle
     *
     * @param EntityWithValuesInterface $product
     *
     * @return array
     * @throws \Exception
     */
    private function getProductValuesTitle(EntityWithValuesInterface $product): array
    {
        if ($product instanceof ProductInterface) {
            $title = $product->getIdentifier();
        } elseif ($product instanceof ProductModel) {
            $title = sprintf('product_model|%s', $product->getCode());
        } else {
            throw new Exception(
                sprintf(
                    'Processed item must implement ProductInterface or Product Model, %s given',
                    ClassUtils::getClass($product)
                )
            );
        }

        $productValues       = [];
        $availableAttributes = $this->getAvailableAttributes($product);

        /** @var ValueInterface $productValue */
        foreach ($product->getValues() as $productValue) {
            if (in_array($productValue->getAttributeCode(), $availableAttributes)) {
                $productValues[] = $productValue;
            }
        }

        return ['product_values' => $productValues, 'title' => $title];
    }

    /**
     * Retrieve available attribute codes.
     *
     * @param EntityWithValuesInterface $product
     *
     * @return array
     */
    protected function getAvailableAttributes(EntityWithValuesInterface $product): array
    {
        $availableAttributes = array_intersect($this->getPowerlingAttributes(), $product->getUsedAttributeCodes());

        if ($product instanceof ProductModelInterface) {
            $familyVariantCode = $product->getFamilyVariant()->getCode();
            if (0 === $product->getLevel()) {
                $this->availableAttributes[$familyVariantCode] = $product->getUsedAttributeCodes();
            }
            if (1 === $product->getLevel()) {
                if (!isset($this->availableAttributes[$familyVariantCode])) {
                    $this->availableAttributes[$familyVariantCode] = $this->getAvailableAttributes(
                        $product->getParent()
                    );
                    $this->objectDetacher->detach($product->getParent());
                }
                $availableAttributes = array_diff(
                    $availableAttributes,
                    $this->availableAttributes[$familyVariantCode]
                );
            }
        }

        return $availableAttributes;
    }

    /**
     * Retrieve available attributes from product.
     *
     * @param EntityWithValuesInterface $product
     *
     * @return array
     */
    protected function getAvailableAttributesFromProduct(EntityWithValuesInterface $product)
    {
        return array_intersect($this->getPowerlingAttributes(), $product->getUsedAttributeCodes());
    }

    /**
     * Create the document key for a product value
     *
     * @param ValueInterface $productValue
     *
     * @return string
     */
    public function createProductValueKey(ValueInterface $productValue): string
    {
        $attributeCode = $productValue->getAttributeCode();

        if ($productValue->isScopable()) {
            $attributeCode = sprintf('%s-%s', $attributeCode, $productValue->getScopeCode());
        }

        return $attributeCode;
    }

    /**
     * getPowerlingAttributes
     *
     * @return string[]
     */
    protected function getPowerlingAttributes(): array
    {
        if (null === $this->powerlingAttributes) {
            $attributeStr = $this->configManager->get('pim_powerling.attributes');
            $this->powerlingAttributes = explode(',', $attributeStr);

            if (empty($attributeStr) || empty($this->powerlingAttributes)) {
                throw new RuntimeException('No attributes configured for translation');
            }
        }

        return $this->powerlingAttributes;
    }

    /**
     * @param AttributeInterface $attribute
     *
     * @return bool
     */
    protected function isValidForTranslation(AttributeInterface $attribute): bool
    {
        if (!in_array($attribute->getCode(), $this->getPowerlingAttributes())) {
            return false;
        }

        $isText = AttributeTypes::TEXT === $attribute->getType()
            || AttributeTypes::TEXTAREA === $attribute->getType();

        return $isText && $attribute->isLocalizable();
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'ctype' => 'translation',
            ]
        );
    }

    /**
     * getAttributeByCode
     *
     * @param string $code
     */
    protected function getAttributeByCode($code)
    {
        if (!array_key_exists($code, $this->attributes)) {
            $this->attributes[$code] = $this->attributeRepository->findOneByIdentifier($code);
        }

        return $this->attributes[$code];
    }
}
