<?php

namespace Pim\Bundle\PowerlingBundle\MassAction;

use Akeneo\Tool\Component\Batch\Item\ExecutionContext;
use Akeneo\Tool\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use Doctrine\Common\Util\ClassUtils;
use Exception;
use Akeneo\Pim\Enrichment\Component\Product\Connector\Processor\MassEdit\AbstractProcessor;
use Pim\Bundle\PowerlingBundle\Api\WebApiRepository;
use Pim\Bundle\PowerlingBundle\Project\BuilderInterface;
use Pim\Bundle\PowerlingBundle\Project\ProjectInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Psr\Log\LoggerInterface;

/**
 * Create Powerling document from product
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class AddDocumentsProcessor extends AbstractProcessor
{
    /** @var BuilderInterface */
    protected $projectBuilder;

    /** @var ObjectDetacherInterface */
    protected $detacher;

    /** @var WebApiRepository */
    protected $apiRepository;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        BuilderInterface $projectBuilder,
        ObjectDetacherInterface $detacher,
        WebApiRepository $apiRepository,
        LoggerInterface $logger
    ) {
        $this->projectBuilder = $projectBuilder;
        $this->detacher = $detacher;
        $this->apiRepository = $apiRepository;
        $this->logger = $logger;
    }

    /**
     * @param ProductInterface $product
     *
     * @return array
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function process($product)
    {
        if (!$product instanceof ProductInterface) {
            throw new Exception(
                sprintf('Processed item must implement ProductInterface, %s given', ClassUtils::getClass($product))
            );
        }

        $projects = $this->getProjects();
        $langAssociations = $this->apiRepository->getLangAssociations();

        foreach ($projects as $project) {
            $this->logger->debug(sprintf('Processing project %s', $project->getCode()));
            $langAssociation = $langAssociations[$project->getLangAssociationId()];
            $fromLocale = $langAssociation['language_from'];
            $this->logger->debug(sprintf('Lang association: %s', json_encode($langAssociation)));
            $this->logger->debug(sprintf('PIM locale code: %s', $fromLocale));
            $attributesToTranslate = $this->projectBuilder->createDocumentData($product, $fromLocale);

            if (null === $attributesToTranslate) {
                $this->stepExecution->incrementSummaryInfo('no_translation');
            } else {
                $project->addDocument($attributesToTranslate);
                $this->stepExecution->incrementSummaryInfo('documents_added');
            }
            $this->logger->debug(
                sprintf('Add %d documents to project %s', count($project->getDocuments()), $project->getCode())
            );
        }

        $this->detacher->detach($product);

        return $projects;
    }

    /**
     * @return ExecutionContext
     */
    protected function getJobContext()
    {
        return $this->stepExecution->getJobExecution()->getExecutionContext();
    }

    /**
     * @return ProjectInterface[]
     */
    protected function getProjects()
    {
        return (array)$this->getJobContext()->get(CreateProjectsTasklet::PROJECTS_CONTEXT_KEY);
    }
}
