<?php

namespace D4rk0s\RewardsExplorer\BotCommand;

use D4rk0s\RewardsExplorer\Service\RewardsService;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;

class GetRewards
{
    private const COMMAND_NAME = 'all_rewards';
    private const STEP1 = 'step1';

    private array $followUp = [];
    private TgLog $tgLog;

    public function __construct(TgLog $tgLog)
    {
        $this->tgLog = $tgLog;
    }

    public function do(Update $updateEvent) : PromiseInterface
    {
        return new Promise(function() use ($updateEvent) {
                $chatId = $updateEvent->message->chat->id;
                $commandParts = explode(" ", $updateEvent->message->text);

                // Est ce que l'on a déjà un suivi au niveau de ce chat ?
                if(array_key_exists($chatId, $this->followUp)) {
                    switch($this->followUp[$chatId]) {
                        case self::STEP1:
                            return $this->step1($updateEvent);
                        default:
                            throw new \Exception("Step non valide : ".$this->followUp[$chatId]);
                    }
                }

                // Est ce que la commande est pour moi ?
                if(current($commandParts) !== "/".self::COMMAND_NAME) {
                    throw new \Exception("Not for me");
                }

                // Invite de bienvenue
                return $this->sendMessageToChat($chatId, "
                    Bienvenue sur ma fonctionnalité de récupération des rewards.\nAfin que je puisse vous répondre, pourriez-vous me donner l'adresse de votre stash ?
                ")->then(
                    function() use ($chatId) {
                        $this->followUp[$chatId] = self::STEP1;
                    }
                );
            });
    }

    public function step1(Update $updateEvent) : PromiseInterface
    {
        return new Promise(function() use ($updateEvent) {
                $chatId = $updateEvent->message->chat->id;
                $commandParts = explode(" ", $updateEvent->message->text);

                if(preg_match('/^[A-Z0-9]{48}$/i',$commandParts[0],$matches) !== 1) {
                    // Ce n'est pas une adresse de wallet valide, on informe l'utilisateur et on le supprime du followUp
                    return $this->sendMessageToChat($chatId, "
                        L'adresse de wallet $commandParts[0] ne semble pas valide.\nMerci de retenter la commande.
                    ") ->then(
                        function() use ($chatId) {
                            unset($this->followUp[$chatId]);
                        });
                }

                $walletAddress = current($matches);
                $rewards = RewardsService::getRewards($walletAddress);
                if(empty($rewards)) {
                    return $this->sendMessageToChat($chatId, "
                        Je n'ai aucune information sur ce wallet ($walletAddress) sur la blockchain Ternoa.
                    ");
                }

                $totalRewards = gmp_init(0);
                $message = "";

                foreach ($rewards as $reward) {
                    $amount = (float)(substr($reward->amount, 0, -18) . "." . substr($reward->amount, -18,3));
                    $message .= $reward->date." : ".$amount. "\n";
                    $totalRewards += gmp_init($reward->amount);
                }
                $totalRewards = substr(gmp_strval($totalRewards), 0, -18) . "." . substr(gmp_strval($totalRewards), -18, 3);

                $message .= "\nTotal Rewards :" . $totalRewards . "\n";

                unset($this->followUp[$chatId]);

               return $this->sendMessageToChat($chatId, $message);
            });
    }

    private function sendMessageToChat(int $chatId, string $message) : PromiseInterface
    {
        $sendMessage = new SendMessage();
        $sendMessage->chat_id = $chatId;
        $sendMessage->text = $message;

        return $this->tgLog->performApiRequest($sendMessage);
    }
}