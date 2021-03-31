<?php

namespace Pim\Bundle\PowerlingBundle\Command;

use Exception;
use Akeneo\Tool\Component\StorageUtils\Saver\BulkSaverInterface;
use Akeneo\Tool\Bundle\StorageUtilsBundle\Doctrine\Common\Remover\BaseRemover;
use Pim\Bundle\PowerlingBundle\Project\ProjectInterface;
use Pim\Bundle\PowerlingBundle\Api\WebApiRepositoryInterface;
use Pim\Bundle\PowerlingBundle\Project\ProjectRepository;
use Pim\Bundle\PowerlingBundle\Product\UpdaterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Retrieve translations and update products
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class RetrieveTranslationsCommand extends Command
{
    /** @var OutputInterface */
    private $output;

    private $webApiRepository;

    private $projectRepository;

    private $updater;

    private $remover;

    private $productSaver;

    protected static $defaultName = 'pim:powerling:retrieve-translations';

    public function __construct(WebApiRepositoryInterface $webApiRepository, ProjectRepository $projectRepository, UpdaterInterface $updater, BaseRemover $remover, BulkSaverInterface $productSaver)
    {
        parent::__construct();

        $this->webApiRepository = $webApiRepository;
        $this->projectRepository = $projectRepository;
        $this->updater = $updater;
        $this->remover = $remover;
        $this->productSaver = $productSaver;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Fetch translations via Powerling API call');
    }

    /**
     * {@inheritdoc}
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $this->writeMessage('Check Powerling projects');

        $pimProjects = $this->getPimProjects();

        foreach ($pimProjects as $project) {
            $this->writeMessage(sprintf('Update products for project %s', $project->getCode()));
            $this->updateProducts($project);
        }
    }

    /**
     * @param ProjectInterface $project
     *
     * @return ProjectInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function updateProducts(ProjectInterface $project): ProjectInterface
    {
        $langAssociation = $this->webApiRepository->getLangAssociations()[$project->getLangAssociationId()];
        $localeCode = $langAssociation['language_to'];

        try {
            list($documents, $translationComplete) = $this->webApiRepository->getDocuments($project->getCode());

            if (!count($documents)) {
                return $project;
            }

            $project->setUpdatedAt();
            $products = [];

            foreach ($documents as $documentId => $document) {
                $product = $this->updater->update($documentId, $document, $localeCode);
                $this->writeMessage(sprintf(
                    'Updated document %s for locale %s',
                    $documentId,
                    $localeCode
                ));
                $products[] = $product;
            }

            $this->productSaver->saveAll($products);

            if ($translationComplete) {
                $this->remover->remove($project);
                $this->writeMessage(sprintf('<info>Project %s is completed and got removed</info>', $project->getCode()));
            }
        } catch (Exception $e) {
            $this->writeMessage(
                sprintf(
                    '<error>Unable to update products for project %s</error> %s',
                    $project->getCode(),
                    $e->getMessage()
                )
            );
        }

        return $project;
    }

    /**
     * Retrieve PIM translation projects
     *
     * @return ProjectInterface[]
     */
    protected function getPimProjects()
    {
        $projects = $this->projectRepository->findAll();

        return $projects;
    }

    /**
     * @param string $message
     */
    private function writeMessage($message)
    {
        $this->output->writeln(sprintf('%s - %s', date('Y-m-d H:i:s'), trim($message)));
    }
}
