<?php

namespace Pim\Bundle\PowerlingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Retrieve all Powerling lang assocs
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class ListLangAssocCommand extends ContainerAwareCommand
{
    /** @var OutputInterface */
    private $output;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:powerling:list-lang-assoc')
            ->setDescription('Fetch projects via Powerling API call');
    }

    /**
     * {@inheritdoc}
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->writeMessage('<info>List Powerling lang assocs</info>');

        $assocs = $this->getLangAssocs();
        foreach ($assocs as $assoc) {
            $this->writeMessage(sprintf(
                '<info>Lang Assoc %s [%s]: %s</info>',
                $assoc['id'],
                $assoc['language_from'],
                $assoc['language_to']
            ));
        }
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getLangAssocs()
    {
        $webApiRepository = $this->getContainer()->get('pim_powerling.repository.webapi');
        $assocs = $webApiRepository->getLangAssociations();

        return $assocs;
    }

    /**
     * @param string $message
     */
    private function writeMessage($message)
    {
        $this->output->writeln(sprintf('%s - %s', date('Y-m-d H:i:s'), trim($message)));
    }
}
