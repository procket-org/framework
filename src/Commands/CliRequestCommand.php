<?php

namespace Procket\Framework\Commands;

use Procket\Framework\Procket;
use Procket\Framework\Str;
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
            'route',
            InputArgument::OPTIONAL,
            'The route string'
        )->addArgument(
            'params',
            InputArgument::OPTIONAL,
            'Action parameters (http query string style)'
        )->addArgument(
            'constructor-params',
            InputArgument::OPTIONAL,
            'Constructor parameters (http query string style)'
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
            $route = $input->getArgument('route');
            parse_str((string)$input->getArgument('params'), $params);
            parse_str((string)$input->getArgument('constructor-params'), $constructorParams);
            $content = Procket::instance()->callServiceApi($route, $params, $constructorParams);
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