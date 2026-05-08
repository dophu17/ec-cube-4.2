<?php

namespace Customize\Command;

use Customize\Repository\SubscriptionRepository;
use Customize\Service\Subscription\SubscriptionCancellationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SubscriptionCancelCommand extends Command
{
    protected static $defaultName = 'subscription:cancel';

    private SubscriptionRepository $subscriptionRepository;
    private SubscriptionCancellationService $subscriptionCancellationService;

    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        SubscriptionCancellationService $subscriptionCancellationService
    ) {
        parent::__construct();
        $this->subscriptionRepository = $subscriptionRepository;
        $this->subscriptionCancellationService = $subscriptionCancellationService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Hủy subscription nếu chưa pre-bill thành công cho kỳ hiện tại.')
            ->addArgument('subscription_id', InputArgument::REQUIRED, 'ID subscription cần hủy');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (int) $input->getArgument('subscription_id');
        $subscription = $this->subscriptionRepository->find($id);
        if (!$subscription) {
            $output->writeln('<error>Không tìm thấy subscription #'.$id.'</error>');

            return Command::FAILURE;
        }

        try {
            $this->subscriptionCancellationService->cancel($subscription);
            $output->writeln('<info>Đã hủy subscription #'.$id.'</info>');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }
}
