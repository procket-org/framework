<?php

namespace Pocket\Framework\Commands;

use Pocket\Framework\Pocket;
use Pocket\Framework\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class CliRequestCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(
            'cli:request'
        )->addArgument(
            'query',
            InputArgument::OPTIONAL,
            'URL query string'
        )->setDescription(
            'Make an application request'
        );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $httpRequest = Pocket::instance()->getHttpRequest();
            $queryStr = $input->getArgument('query');
            parse_str($queryStr, $queries);
            // Set query string input parameters
            $httpRequest->query->add($queries);
            // Set request input parameters
            $httpRequest->merge($queries);
            $content = Pocket::instance()->callServiceApi();
            if ($content instanceof SymfonyResponse) {
                $outputContent = $content->getContent();
            } else {
                $outputContent = Str::shouldBeJson($content) ? Str::morphToJson($content) : $content;
            }
            $output->writeln($outputContent);
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $msg = '<error>' . $e->getMessage() . '</error>';
            $output->writeln($msg);
            return Command::FAILURE;
        }
    }
}