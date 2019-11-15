<?php

namespace Pim\Bundle\PowerlingBundle\Command;

use Exception;
use Pim\Bundle\PowerlingBundle\Project\ProjectInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Retrieve translations and update products
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class RetrieveTranslationsCommand extends ContainerAwareCommand
{
    /** @var OutputInterface */
    private $output;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:powerling:retrieve-translations')
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
        $webApiRepository = $this->getContainer()->get('pim_powerling.repository.webapi');

        $langAssociation = $webApiRepository->getLangAssociations()[$project->getLangAssociationId()];
        $localeCode = $langAssociation['language_to'];

        try {
            list($documents, $translationComplete) = $webApiRepository->getDocuments($project->getCode());

            if (!count($documents)) {
                return $project;
            }

            $project->setUpdatedAt();
            $updater = $this->getContainer()->get('pim_powerling.document.updater');
            $products = [];

            foreach ($documents as $documentId => $document) {
                $product = $updater->update($documentId, $document, $localeCode);
                $this->writeMessage(sprintf(
                    'Updated document %s for locale %s',
                    $documentId,
                    $localeCode
                ));
                $products[] = $product;
            }

            $saver = $this->getContainer()->get('pim_catalog.saver.product');
            $saver->saveAll($products);

            if ($translationComplete) {
                $remover = $this->getContainer()->get('pim_powerling.remover.project');
                $remover->remove($project);
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
        $projectRepository = $this->getContainer()->get('pim_powerling.repository.project');
        $projects = $projectRepository->findAll();

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
