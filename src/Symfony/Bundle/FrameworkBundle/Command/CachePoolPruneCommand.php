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

use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
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
            ->addArgument('pools', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'A list of cache pool service ids')
            ->setDescription('Prune cache pools')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command prunes all or the given cache pools.

    %command.full_name% [<cache pool service id 1> [...<cache pool service id N>]]
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

        if (0 === count($pools = $this->getMatchingPools($input))) {
            throw new RuntimeException('No pruneable cache pools found.');
        }

        foreach ($pools as $name => $pool) {
            $io->comment(sprintf('Pruning cache pool: <info>%s</info>', $name));
            $pool->prune();
        }

        $io->success('Cache was successfully pruned.');
    }

    /**
     * @param InputInterface $input
     *
     * @return PruneableInterface[]
     */
    private function getMatchingPools(InputInterface $input)
    {
        $pools = iterator_to_array($this->pools);
        $names = $input->getArgument('pools');

        if (0 === count($names)) {
            return $pools;
        }

        $matches = array();

        foreach ($input->getArgument('pools') as $name) {
            if (!isset($pools[$name])) {
                throw new InvalidArgumentException(sprintf('The "%s" pool does not exist or is not pruneable.', $name));
            }

            $matches[$name] = $pools[$name];
        }

        return $matches;
    }
}
