<?php

namespace Pim\Bundle\PowerlingBundle\Project\Form;

use Pim\Bundle\PowerlingBundle\Api\WebApiRepository;
use Pim\Bundle\PowerlingBundle\MassAction\Operation\CreateProjects;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Project form type
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class CreateProjectType extends AbstractType
{
    /** @var LocaleRepositoryInterface */
    protected $localeRepository;

    /** @var WebApiRepository */
    protected $apiRepository;

    /** @var array */
    protected $options;

    /**
     * @param LocaleRepositoryInterface $localeRepository
     * @param WebApiRepository          $apiRepository
     * @param array                     $options
     */
    public function __construct(
        LocaleRepositoryInterface $localeRepository,
        WebApiRepository $apiRepository,
        array $options
    ) {
        $this->localeRepository = $localeRepository;
        $this->apiRepository = $apiRepository;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', [
            'required'    => true,
            'constraints' => new NotBlank(),
        ]);

        $builder->add('lang_associations', 'choice', [
            'required'    => true,
            'choices'     => $this->getLangAssociationsChoices(),
            'select2'     => true,
            'multiple'    => true,
            'constraints' => new NotBlank(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            ['data_class' => CreateProjects::class]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'powerling_create_projects';
    }

    /**
     * @return string[]
     */
    protected function getLangAssociationsChoices()
    {
        $langAssociations = $this->apiRepository->getLangAssociations();
        $choices = [];

        foreach ($langAssociations as $id => $data) {
            $choices[$id] = sprintf(
                '["%s" to "%s"]',
                $data['language_from'],
                $data['language_to']
            );
        }

        return $choices;
    }
}
