<?php

namespace D4rk0s\RewardsExplorer\Command;

use D4rk0s\RewardsExplorer\BotCommand\GetRewards;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use unreal4u\TelegramAPI\Abstracts\TraversableCustomType;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetUpdates;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;
use function React\Promise\all;
use function React\Promise\any;

class TernoaRewardsBot extends Command
{
    protected static string $defaultName = "ternoa_bot";
    private TgLog $tgLog;
    private LoopInterface $loop;
    private GetUpdates $getUpdates;
    private array $botCommands = [];

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__."/../../.env");
        $this->loop = Loop::get();
        $this->tgLog = new TgLog($_ENV['BOT_TOKEN'], new HttpClientRequestHandler($this->loop));
        $this->getUpdates = new GetUpdates();

        $this->registerBotCommands();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loop->addPeriodicTimer(1, function() {
             $this->eventLoop();
        });

        $this->loop->run();

        return Command::SUCCESS;
    }

    private function eventLoop() : PromiseInterface
    {
        return $this->tgLog->performApiRequest($this->getUpdates)
            ->then( function(TraversableCustomType $updatesArray) {
                $updateEventPromises = [];
                foreach ($updatesArray as $update) {
                    $updateEventPromises[] = $this->manageUpdateEvent($update);
                    $this->getUpdates->offset = $update->update_id+1;
                }

                return all($updateEventPromises);
            });
    }

    private function manageUpdateEvent(Update $update) : PromiseInterface
    {
        $botCommandsPromises = array_map(
            function($botCommand) use ($update) {
                return $botCommand->do($update);
            }, $this->botCommands
        );

        return any($botCommandsPromises);
    }


    private function registerBotCommands()
    {
        $this->botCommands[] = new GetRewards($this->tgLog);
    }
}