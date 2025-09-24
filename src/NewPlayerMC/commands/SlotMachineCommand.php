<?php

namespace NewPlayerMC\commands;

use NewPlayerMC\menus\BetCreateForm;
use NewPlayerMC\menus\SlotMachineInventory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\Player;

class SlotMachineCommand extends Command
{

    public function __construct()
    {
        parent::__construct("slot", "Open the slot machine", "slot", ['slotmachine']);
        $this->setPermission(DefaultPermissionNames::GROUP_USER);
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) return;

        $menu = new SlotMachineInventory();
        $menu->send($sender);
        /*$form = new BetCreateForm();
        $form->send($sender);*/
    }
}