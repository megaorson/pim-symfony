<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Product\Flat\ProductFlatReindexService;
use App\Service\Product\Flat\ProductFlatTableRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:product-flat:reindex')]
final class ProductFlatReindexCommand extends Command
{
    public function __construct(
        private readonly ProductFlatReindexService $reindexService,
        private readonly ProductFlatTableRegistry $tableRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Rebuild product_flat index using blue/green strategy')
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL,
                'Limit number of products to reindex'
            )
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_REQUIRED,
                'Batch size',
                '500'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what will be done without executing'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $limit = $input->getArgument('limit');
        $limit = $limit !== null ? (int) $limit : null;

        $batchSize = max(1, (int) $input->getOption('batch-size'));
        $dryRun = (bool) $input->getOption('dry-run');

        $active = $this->tableRegistry->getActiveTable();
        $target = $this->tableRegistry->getStandbyTable();

        $io->section('Product Flat Reindex');
        $io->text([
            sprintf('Active table: <info>%s</info>', $active),
            sprintf('Target table: <comment>%s</comment>', $target),
            sprintf('Limit: %s', $limit ?? 'ALL'),
            sprintf('Batch size: %d', $batchSize),
        ]);

        if ($dryRun) {
            $io->warning('Dry run mode enabled. No changes will be applied.');

            return Command::SUCCESS;
        }

        $total = $this->reindexService->countProducts($limit);
        $io->progressStart($total);

        $start = microtime(true);

        try {
            $this->reindexService->rebuild(
                limit: $limit,
                batchSize: $batchSize,
                onProgress: static function (int $processed) use ($io): void {
                    $io->progressAdvance($processed);
                }
            );

            $io->progressFinish();

            $duration = microtime(true) - $start;
            $newActive = $this->tableRegistry->getActiveTable();

            $io->success([
                'Reindex completed successfully',
                sprintf('New active table: %s', $newActive),
                sprintf('Time: %.2f sec', $duration),
                $duration > 0.0
                    ? sprintf('Speed: %.2f items/sec', $total / $duration)
                    : 'Speed: n/a',
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error([
                'Reindex failed',
                $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
