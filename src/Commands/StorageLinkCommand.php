<?php

namespace Procket\Framework\Commands;

use Illuminate\Filesystem\Filesystem;
use Procket\Framework\Procket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StorageLinkCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(
            'storage:link'
        )->addArgument(
            'links',
            InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            'Symbolic link and target pairs. For example: link1 target1 link2 target2 ...'
        )->addOption(
            'relative',
            null,
            InputOption::VALUE_NONE,
            'Create the symbolic link using relative paths'
        )->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Recreate existing symbolic links'
        )->setDescription(
            'Create the symbolic links configured for the application'
        );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $relative = $input->getOption('relative');
        $force = $input->getOption('force');
        $inputLinks = (array)$input->getArgument('links');

        $inputLinkChunks = array_chunk($inputLinks, 2);
        $inputLinkPairs = [];
        foreach ($inputLinkChunks as $chunk) {
            $inputLink = $chunk[0] ?? null;
            $inputTarget = $chunk[1] ?? null;
            if ($inputLink && $inputTarget) {
                $inputLinkPairs[$inputLink] = $inputTarget;
            }
        }

        foreach ($this->links($inputLinkPairs) as $link => $target) {
            if (file_exists($link) && !$this->isRemovableSymlink($link, $force)) {
                $output->writeln("<error>The [$link] link already exists</error>");
                continue;
            }

            if (is_link($link)) {
                $this->filesystem()->delete($link);
            }

            if ($relative) {
                $this->filesystem()->relativeLink($target, $link);
            } else {
                $this->filesystem()->link($target, $link);
            }

            $output->writeln("<info>The [$link] link has been connected to [$target]</info>");
        }

        return Command::SUCCESS;
    }

    /**
     * Get the filesystem
     *
     * @return Filesystem
     */
    protected function filesystem(): Filesystem
    {
        return Procket::instance()->getFilesystem();
    }

    /**
     * Get the symbolic links that are configured for the application.
     *
     * @param array $inputLinkPairs
     * @return array
     */
    protected function links(array $inputLinkPairs = []): array
    {
        $configLinks = (array)Procket::instance()->getConfig('storage.links', []);

        return array_merge($configLinks, $inputLinkPairs);
    }

    /**
     * Determine if the provided path is a symlink that can be removed.
     *
     * @param string $link
     * @param bool $force
     * @return bool
     */
    protected function isRemovableSymlink(string $link, bool $force): bool
    {
        return is_link($link) && $force;
    }
}