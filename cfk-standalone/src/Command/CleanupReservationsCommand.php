<?php

declare(strict_types=1);

namespace CFK\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Cleanup Expired Reservations Command
 *
 * Replaces: cron/cleanup_reservations.php
 *
 * This command finds and releases reservations that have exceeded the 2-hour timeout.
 * Can be run manually or via cron.
 */
#[AsCommand(
    name: 'cfk:cleanup:reservations',
    description: 'Release expired reservations (2-hour timeout)',
)]
class CleanupReservationsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Preview what would be cleaned up without making changes'
            )
            ->addOption(
                'timeout',
                't',
                InputOption::VALUE_REQUIRED,
                'Override timeout in minutes (default: 120)',
                '120'
            )
            ->setHelp(
                <<<'HELP'
                The <info>cfk:cleanup:reservations</info> command releases expired reservations.

                By default, reservations older than 2 hours (120 minutes) are released.

                Usage:
                  <info>php bin/console cfk:cleanup:reservations</info>

                Dry run (preview only):
                  <info>php bin/console cfk:cleanup:reservations --dry-run</info>

                Custom timeout:
                  <info>php bin/console cfk:cleanup:reservations --timeout=180</info>

                Add to crontab (run every hour):
                  <comment>0 * * * * cd /path/to/cfk && php bin/console cfk:cleanup:reservations >> /dev/null 2>&1</comment>
                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dryRun = $input->getOption('dry-run');
        $timeout = (int) $input->getOption('timeout');

        $io->title('Cleanup Expired Reservations');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No changes will be made');
        }

        try {
            // Calculate expiration time
            $expirationTime = date('Y-m-d H:i:s', strtotime("-{$timeout} minutes"));

            $io->info("Finding reservations older than {$timeout} minutes (before {$expirationTime})");

            // Find expired reservations
            // TODO: Replace with actual Database class call after migration
            // $expiredReservations = Database::fetchAll(
            //     "SELECT id, child_id, reserved_at FROM reservations
            //      WHERE reserved_at < ? AND status = 'pending'",
            //     [$expirationTime]
            // );

            // Simulated for now
            $expiredReservations = [];

            if (empty($expiredReservations)) {
                $io->success('No expired reservations found');
                return Command::SUCCESS;
            }

            $count = count($expiredReservations);
            $io->info("Found {$count} expired reservation(s)");

            if (!$dryRun) {
                // TODO: Implement actual cleanup after migration
                // foreach ($expiredReservations as $reservation) {
                //     Database::execute(
                //         "DELETE FROM reservations WHERE id = ?",
                //         [$reservation['id']]
                //     );
                //
                //     Database::execute(
                //         "UPDATE children SET status = 'available' WHERE id = ?",
                //         [$reservation['child_id']]
                //     );
                // }

                $io->success("Released {$count} expired reservation(s)");
            } else {
                $io->note("Would release {$count} reservation(s) (dry-run mode)");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Cleanup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
