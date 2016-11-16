<?php

namespace Stp\CrontabBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Command
 */
abstract class BaseCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var SymfonyStyle */
    protected $io;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * Initialize
     *
     * @param InputInterface  $input  input
     * @param OutputInterface $output output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->io = new SymfonyStyle($input, $output);
        $this->logger = $this->container->get($this->container->getParameter('stp_crontab.logger_name'));
    }

    /**
     * Require field
     *
     * @param string $fieldName  field name
     * @param mixed  $fieldValue field value
     *
     * @throws RuntimeException
     */
    protected function requireField($fieldName, $fieldValue)
    {
        if (empty($fieldValue)) {
            throw new RuntimeException('The "' . $fieldName . '" option cannot be empty');
        }
    }

    /**
     * Get array as string
     *
     * @param array $items items
     *
     * @return string
     */
    protected function getArrayAsString(array $items)
    {
        $array = [];

        foreach ($items as $key => $value) {
            $array[] = $key . '=' . $value;
        }
        $string = implode(', ', $array);

        return $string;
    }
}
