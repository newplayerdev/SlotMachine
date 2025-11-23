<?php

namespace NewPlayerMC\menus;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use NewPlayerMC\Bet;
use NewPlayerMC\BetsManager;
use NewPlayerMC\Main;
use NewPlayerMC\task\SlotTask;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class SlotMachineInventory
{
    public const FIRST_SLOT = 12;
    public const SECOND_SLOT = 13;
    public const THIRD_SLOT = 14;

    public const COLUMN1 = [3, 12, 21];
    public const COLUMN2 = [4, 13, 22];
    public const COLUMN3 = [5, 14, 23];

    public const BET = 18;
    public const LAUNCH = 26;
    public const HISTORY = 0;

    public InvMenu $menu;

    public function __construct()
    {
        $this->menu = $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->setName("Slot Machine");
    }

    public function send(Player $player): void {
        $menu = $this->menu;
        $inv = $menu->getInventory();

        for ($i = 0; $i < $inv->getSize(); $i++) {
            $inv->setItem($i, VanillaBlocks::STAINED_GLASS_PANE()->asItem());
        }

        $historyItem = VanillaItems::CLOCK();
        $historyItem->setCustomName("§r§eHistory");
        $historyItem->setLore(BetsManager::getInstance()->getLoreHistory($player));
        $inv->setItem(self::HISTORY, $historyItem);

        $inv->setItem(11, VanillaBlocks::END_ROD()->asItem());
        $inv->setItem(15, VanillaBlocks::END_ROD()->asItem());

        $inv->setItem(self::FIRST_SLOT, VanillaItems::GOLD_INGOT());
        $inv->setItem(self::SECOND_SLOT, VanillaItems::GOLD_INGOT());
        $inv->setItem(self::THIRD_SLOT, VanillaItems::GOLD_INGOT());

        $betsManager = BetsManager::getInstance();
        $bet = $betsManager->getPlayerBet($player);

        $betItem = $bet instanceof Bet
            ? VanillaItems::DIAMOND()->setCustomName("§r§bYour bet: §3" . $bet->getBet() . "$")
            : VanillaItems::PAPER()->setCustomName("Place a bet");

        $inv->setItem(self::BET, $betItem);

        if ($bet instanceof Bet) {
            $inv->setItem(self::LAUNCH, VanillaItems::EMERALD()->setCustomName("§rLaunch"));
        }

        $menu->send($player);

        $menu->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use ($player, $menu, $bet, $betsManager) {
            $slot = $transaction->getAction()->getSlot();

            if ($betsManager->isSlotRunning($player->getName())) {
                $player->sendMessage("§cYou already have a bet going..");
                return;
            }

            switch ($slot) {
                case self::BET:
                    $player->removeCurrentWindow();
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player) {
                        $form = new BetCreateForm();
                        $form->send($player);
                    }), 20);
                    break;

                case self::LAUNCH:
                    $betsManager->setSlotRunning($player->getName(), true);

                    $menu->getInventory()->setItem(self::BET, VanillaBlocks::STAINED_GLASS_PANE()->asItem());
                    $menu->getInventory()->setItem(self::LAUNCH, VanillaBlocks::STAINED_GLASS_PANE()->asItem());

                    Main::getInstance()->getScheduler()->scheduleRepeatingTask(
                        new SlotTask($player, $bet),
                        5
                    );
                    break;
                default: break;
            }
        }));
    }

    public function getInventory(): Inventory {
        return $this->menu->getInventory();
    }
}