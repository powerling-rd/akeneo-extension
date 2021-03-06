<?php

namespace Pim\Bundle\PowerlingBundle\MassAction;

use Akeneo\Tool\Component\Batch\Item\ExecutionContext;
use Akeneo\Tool\Component\Batch\Model\StepExecution;
use Akeneo\Tool\Component\StorageUtils\Saver\SaverInterface;
use Pim\Bundle\PowerlingBundle\Api\WebApiRepository;
use Pim\Bundle\PowerlingBundle\Entity\Project;
use Pim\Bundle\PowerlingBundle\Project\BuilderInterface;
use Pim\Bundle\PowerlingBundle\Project\ProjectInterface;
use Akeneo\Channel\Component\Repository\LocaleRepositoryInterface;
use Akeneo\Tool\Component\Connector\Step\TaskletInterface;

/**
 * Create the project entity and put it in job context
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class CreateProjectsTasklet implements TaskletInterface
{
    const PROJECTS_CONTEXT_KEY = 'powerling_projects';

    /**
     * @var string Default category
     */
    const PROJECTS_DEFAULT_CATEGORY = 'C033';

    /** @var StepExecution */
    protected $stepExecution;

    /** @var LocaleRepositoryInterface */
    protected $localeRepository;

    /** @var SaverInterface */
    protected $saver;

    /** @var BuilderInterface */
    protected $builder;

    /** @var WebApiRepository */
    protected $apiRepository;

    /**
     * @param LocaleRepositoryInterface $localeRepository
     * @param BuilderInterface          $builder
     * @param WebApiRepository          $apiRepository
     * @param SaverInterface            $saver
     */
    public function __construct(
        LocaleRepositoryInterface $localeRepository,
        BuilderInterface $builder,
        WebApiRepository $apiRepository,
        SaverInterface $saver
    ) {
        $this->localeRepository = $localeRepository;
        $this->saver = $saver;
        $this->builder = $builder;
        $this->apiRepository = $apiRepository;
    }

    /**
     * @inheritdoc
     */
    public function getConfigurationFields()
    {
        return [];
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
        $actions = $this->getConfiguredActions();

        if (null === $actions) {
            $this->stepExecution->addError('No actions found.');

            return;
        }

        $projectCode = $actions[0]['name'];
        $langAssociationIds = explode(',', $actions[0]['langAssociations']);
        $username = $actions[0]['username'];

        $projects = [];
        foreach ($langAssociationIds as $langAssociationId) {
            $project = $this->createProject(
                $projectCode,
                $langAssociationId,
                $username
            );
            $this->sendProject($project);
            $this->saver->save($project);
            $projects[] = $project;
            $this->stepExecution->incrementSummaryInfo('process');
        }
        $this->addProjectsToContext($projects);
    }

    /**
     * @param string      $name
     * @param string      $langAssociationId
     * @param string|null $username
     *
     * @return ProjectInterface
     */
    protected function createProject(
        $name,
        $langAssociationId,
        $username = null
    ) {
        $project = new Project();
        $project->setName($name);
        $project->setLangAssociationId($langAssociationId);
        $project->setUsername($username);

        return $project;
    }

    /**
     * @param ProjectInterface $project
     *
     * @return array
     */
    protected function sendProject(ProjectInterface $project)
    {
        $data = $this->builder->createProjectData($project);
        $result = $this->apiRepository->createProject($data);
        $project->setCode((string)$result['orderid']);

        return $result;
    }

    /**
     * @param ProjectInterface[] $projects
     */
    protected function addProjectsToContext(array $projects)
    {
        $this->getJobContext()->put(static::PROJECTS_CONTEXT_KEY, $projects);
    }

    /**
     * @return ExecutionContext
     */
    protected function getJobContext()
    {
        $jobExecution = $this->stepExecution->getJobExecution();
        $context = $jobExecution->getExecutionContext();

        if (null === $context) {
            $context = new ExecutionContext();
            $jobExecution->setExecutionContext($context);
        }

        return $context;
    }

    /**
     * @return array|null
     */
    protected function getConfiguredActions()
    {
        $jobParameters = $this->stepExecution->getJobParameters();

        return $jobParameters->get('actions');
    }
}
