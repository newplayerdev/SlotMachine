<?php

namespace NewPlayerMC\task;

use muqsit\invmenu\InvMenu;
use NewPlayerMC\Bet;
use NewPlayerMC\BetsManager;
use NewPlayerMC\menus\SlotMachineInventory;
use pocketmine\inventory\Inventory;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class SlotTask extends Task
{
    private array $reels = [];
    private int $timer = 5 * 10;
    private int $phase = 0;

    private Inventory $inventory;
    private BetsManager $betsManager;

    public function __construct(
        protected Player $player,
        protected Bet $bet
    ) {
        $this->betsManager = BetsManager::getInstance();
        $items = $this->betsManager->getSlotItems();

        $this->inventory = $this->player->getCurrentWindow();

        $reel1 = $items;
        $reel2 = $items;
        $reel3 = $items;

        shuffle($reel1);
        shuffle($reel2);
        shuffle($reel3);

        $this->reels = [
            $reel1,
            $reel2,
            $reel3
        ];
    }

    public function onRun(): void
    {
        if ($this->timer-- <= 0 || !$this->player->isOnline()) {
            $this->getHandler()?->cancel();
            return;
        }

        $inv = $this->inventory;

        if ($this->timer % 20 === 0 && $this->phase < 3) {
            $this->phase++;
        }

        for ($col = 0; $col < 3; $col++) {
            if ($col < $this->phase) continue;

            $item = array_shift($this->reels[$col]);
            $this->reels[$col][] = $item;

            $columnSlots = match ($col) {
                0 => SlotMachineInventory::COLUMN1,
                1 => SlotMachineInventory::COLUMN2,
                2 => SlotMachineInventory::COLUMN3,
            };

            foreach ($columnSlots as $i => $slot) {
                $inv->setItem($slot, $this->reels[$col][$i]);
            }
        }
    }

    public function onCancel(): void
    {
        $this->bet->setResult([
            $this->inventory->getItem(SlotMachineInventory::FIRST_SLOT),
            $this->inventory->getItem(SlotMachineInventory::SECOND_SLOT),
            $this->inventory->getItem(SlotMachineInventory::THIRD_SLOT)
        ]);

        $this->betsManager->giveReward($this->bet);

        $this->betsManager->removeBet($this->player);
        $this->betsManager->setSlotRunning($this->player->getName(), false);
        $this->player->removeCurrentWindow();
    }
}