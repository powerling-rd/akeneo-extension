<?php

namespace Pim\Bundle\PowerlingBundle\Project;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Pim\Bundle\PowerlingBundle\Project\Exception\RuntimeException;
use Pim\Component\Catalog\AttributeTypes;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\ValueInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    /**
     * @param ConfigManager   $configManager
     * @param LoggerInterface $logger
     */
    public function __construct(ConfigManager $configManager, LoggerInterface $logger)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve([]);
        $this->configManager = $configManager;
        $this->logger = $logger;
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
     */
    public function createDocumentData(ProductInterface $product, $sourceLocale)
    {
        $productValues = $product->getValues();
        $originalContent = [];
        $wysiwyg = false;
        foreach ($productValues as $productValue) {
            /** @var ValueInterface $productValue */
            if ($this->isValidForTranslation($productValue->getAttribute()) && $sourceLocale === $productValue->getLocale()) {
                $key = $this->createProductValueKey($productValue);
                $originalPhrase = trim($productValue->getData());
                if ($productValue->getAttribute()->isWysiwygEnabled()) {
                    $wysiwyg = true;
                }
                if (!empty($originalPhrase)) {
                    $originalContent[$key]['original_phrase'] = $originalPhrase;
                }
            }
        }

        if (empty($originalContent)) {
            return null;
        }

        $documentData = [
            'title'              => $product->getIdentifier(),
            'original_content'   => $originalContent,
            'markup_in_content'  => $wysiwyg
        ];

        $this->logger->debug(sprintf('Create document data: %s', json_encode($documentData)));

        return $documentData;
    }

    /**
     * Create the document key for a product value
     *
     * @param ValueInterface $productValue
     *
     * @return string
     */
    public function createProductValueKey(ValueInterface $productValue)
    {
        $attribute = $productValue->getAttribute();
        $key = $attribute->getCode();

        if ($attribute->isScopable()) {
            $key = sprintf('%s-%s', $attribute->getCode(), $productValue->getScope());
        }

        return $key;
    }

    /**
     * @param AttributeInterface $attribute
     *
     * @return bool
     */
    protected function isValidForTranslation(AttributeInterface $attribute)
    {
        $attributesSetting = $this->configManager->get('pim_powerling.attributes');

        if (empty($attributesSetting)) {
            throw new RuntimeException('No attributes configured for translation');
        }

        $attributeCodes = explode(',', $attributesSetting);

        if (!in_array($attribute->getCode(), $attributeCodes)) {
            return false;
        }

        $isText = AttributeTypes::TEXT === $attribute->getType() ||
            AttributeTypes::TEXTAREA === $attribute->getType();

        return $isText && $attribute->isLocalizable();
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'ctype' => 'translation',
        ]);
    }
}
