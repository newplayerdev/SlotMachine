<?php

namespace NewPlayerMC\menus;

use NewPlayerMC\BetsManager;
use pocketmine\player\Player;
use NewPlayerMC\RandomHeartAttack\libs\jojoe77777\FormAPI\CustomForm;

class BetCreateForm
{
    public function send(Player $player): void {
        $menu = new CustomForm(function (Player $player, array $result = null) {
            if ($result === null) return;
            $bet = $result['bet'];
            if (!is_numeric($bet)) {
                $player->sendMessage("Bet must be a number");
                return;
            }

            if ($bet < 100) {
                $player->sendMessage("Bet must be higher than 100$");
                return;
            }

            BetsManager::getInstance()->openBet($player, $bet);
            $player->sendMessage("You succesfully opened a bet of {$bet}$");
            $inv = new SlotMachineInventory();
            $inv->send($player);
        });
        $menu->setTitle("Bet Creating");
        $menu->addInput("Bet", "Minimum 100$", 100, label: 'bet');
        $player->sendForm($menu);
    }

}