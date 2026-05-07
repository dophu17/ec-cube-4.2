<?php

namespace Customize\Command;

use Customize\Repository\SubscriptionRepository;
use Customize\Service\Subscription\SubscriptionBillingRunner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SubscriptionRunCommand extends Command
{
    protected static $defaultName = 'subscription:run';

    private SubscriptionRepository $subscriptionRepository;
    private SubscriptionBillingRunner $subscriptionBillingRunner;
    private EntityManagerInterface $entityManager;
    private bool $subscriptionEnabled;

    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        SubscriptionBillingRunner $subscriptionBillingRunner,
        EntityManagerInterface $entityManager,
        bool $subscriptionEnabled
    ) {
        parent::__construct();
        $this->subscriptionRepository = $subscriptionRepository;
        $this->subscriptionBillingRunner = $subscriptionBillingRunner;
        $this->entityManager = $entityManager;
        $this->subscriptionEnabled = $subscriptionEnabled;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Xử lý gia hạn subscription đến hạn (GMO).')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Số subscription xử lý tối đa', 50)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Không charge, chỉ log nhận batch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->subscriptionEnabled) {
            $output->writeln('<comment>SUBSCRIPTION_ENABLED=0 — bỏ qua.</comment>');

            return Command::SUCCESS;
        }

        $limit = max(1, (int) $input->getOption('limit'));
        $dryRun = (bool) $input->getOption('dry-run');
        $now = new \DateTime('now');

        $due = $this->subscriptionRepository->findDueActive($now, $limit);
        $output->writeln(sprintf('Tìm thấy %d subscription đến hạn.', count($due)));

        foreach ($due as $subscription) {
            try {
                $this->entityManager->beginTransaction();
                $this->subscriptionBillingRunner->execute($subscription, $dryRun);
                if ($this->entityManager->isOpen() && $this->entityManager->getConnection()->isTransactionActive()) {
                    $this->entityManager->commit();
                }
            } catch (\Throwable $e) {
                if ($this->entityManager->isOpen() && $this->entityManager->getConnection()->isTransactionActive()) {
                    $this->entityManager->rollback();
                }
                $output->writeln('<error>Subscription #'.$subscription->getId().': '.$e->getMessage().'</error>');
            }
        }

        return Command::SUCCESS;
    }
}
