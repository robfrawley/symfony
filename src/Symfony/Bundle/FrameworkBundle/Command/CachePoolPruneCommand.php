<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Cache pool pruner command.
 */
final class CachePoolPruneCommand extends Command
{
    /**
     * @var \IteratorAggregate
     */
    private $pools;

    /**
     * @param \IteratorAggregate $pools
     */
    public function __construct($pools)
    {
        parent::__construct();

        $this->pools = $pools;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:pool:prune')
            ->setDescription('Prune cache pools')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command prunes all or the given cache pools.

    %command.full_name%
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        foreach ($this->pools as $name => $pool) {
            $io->comment(sprintf('Pruning cache pool: <info>%s</info>', $name));
            $pool->prune();
        }

        $io->success('Successfully pruned cache pool(s).');
    }
}
