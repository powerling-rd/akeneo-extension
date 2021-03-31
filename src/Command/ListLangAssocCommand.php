<?php

namespace Pim\Bundle\PowerlingBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pim\Bundle\PowerlingBundle\Api\WebApiRepositoryInterface;

/**
 * Retrieve all Powerling lang assocs
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class ListLangAssocCommand extends Command
{
    /** @var OutputInterface */
    private $output;
    
    private $webApiRepository;

    protected static $defaultName = 'pim:powerling:list-lang-assoc';

    public function __construct(WebApiRepositoryInterface $webApiRepository)
    {
        parent::__construct();

        $this->webApiRepository = $webApiRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
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
        $assocs = $this->webApiRepository->getLangAssociations();

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
