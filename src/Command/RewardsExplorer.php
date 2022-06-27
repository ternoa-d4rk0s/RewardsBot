<?php

namespace D4rk0s\RewardsExplorer\Command;

use D4rk0s\RewardsExplorer\Service\RewardsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RewardsExplorer extends Command
{
    protected static string $defaultName = "rewards_explorer";
    private const WALLET_ARGUMENT = 'wallet';

    public function execute(InputInterface $input, OutputInterface $output)
    {
       $wallet = $input->getArgument(self::WALLET_ARGUMENT);
       $data = RewardsService::getRewards($wallet);
       $totalRewards = 0;

       foreach($data as $reward) {
           echo $reward->date." : ".$reward->amount. "\n";
           $totalRewards += $reward->amount;
       }
       echo "\nTotal Rewards :".$totalRewards."\n";

       return Command::SUCCESS;
    }

    public function configure(): void
    {
        $this->addArgument(self::WALLET_ARGUMENT, InputArgument::REQUIRED, 'Wallet address on Ternoa Blockchain');
    }
}