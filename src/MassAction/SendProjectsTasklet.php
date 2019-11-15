<?php

namespace Pim\Bundle\PowerlingBundle\MassAction;

use Akeneo\Tool\Component\Batch\Item\ExecutionContext;
use Akeneo\Tool\Component\Batch\Model\StepExecution;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Pim\Bundle\PowerlingBundle\Api\WebApiRepository;
use Pim\Bundle\PowerlingBundle\Project\ProjectInterface;
use Akeneo\Tool\Component\Connector\Step\TaskletInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Send previously built projects
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class SendProjectsTasklet implements TaskletInterface
{
    const STATUS_MAX_TRY = 3;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var WebApiRepository */
    protected $apiRepository;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param WebApiRepository    $apiRepository
     * @param ConfigManager       $configManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        WebApiRepository $apiRepository,
        ConfigManager $configManager,
        TranslatorInterface $translator
    ) {
        $this->apiRepository = $apiRepository;
        $this->configManager = $configManager;
        $this->translator = $translator;
    }

    /**
     * @inheritdoc
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $projects = $this->getProjects();

        foreach ($projects as $project) {
            $documents = $project->getDocuments();
            if (null !== $documents) {
                $this->apiRepository->sendProjectDocuments($project);
                $this->stepExecution->incrementSummaryInfo('projects_sent', 1);
                $this->stepExecution->incrementSummaryInfo('documents_added', count($documents));
            }
        }
    }

    /**
     * @return ProjectInterface[]
     */
    protected function getProjects()
    {
        return (array)$this->getJobContext()->get(CreateProjectsTasklet::PROJECTS_CONTEXT_KEY);
    }

    /**
     * @return ExecutionContext
     */
    protected function getJobContext()
    {
        return $this->stepExecution->getJobExecution()->getExecutionContext();
    }
}
