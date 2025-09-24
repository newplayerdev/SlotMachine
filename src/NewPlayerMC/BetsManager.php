<?php

namespace NewPlayerMC;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

class BetsManager
{
    use SingletonTrait;

    /** playerName => bet */
    private array $bets = [];

    /** @var Item[] */
    private array $slotItems = [];

    public function __construct()
    {
        self::$instance = $this;

        $this->addItem(VanillaItems::GOLD_INGOT());
        $this->addItem(VanillaItems::EMERALD());
        $this->addItem(VanillaItems::DIAMOND());
        $this->addItem(VanillaItems::PAPER());

        $this->addItem(VanillaBlocks::DIRT());
        $this->addItem(VanillaBlocks::DIAMOND());
        $this->addItem(VanillaBlocks::GOLD());
        $this->addItem(VanillaBlocks::EMERALD());
    }

    private function addItem(Block|Item $item): void {
        $this->slotItems[] = $item instanceof Block ? $item->asItem() : $item;
    }

    private array $runningSlots = []; // [playerName => true]

    public function setSlotRunning(string $playerName, bool $running): void {
        if ($running) {
            $this->runningSlots[$playerName] = true;
        } else {
            unset($this->runningSlots[$playerName]);
        }
    }

    public function isSlotRunning(string $playerName): bool {
        return isset($this->runningSlots[$playerName]) && $this->runningSlots[$playerName] === true;
    }

    public function getSlotItems(): array {
        return $this->slotItems;
    }

    public function playerHasBet(Player $player): bool {
        return isset($this->bets[$player->getName()]);
    }

    public function getPlayerBet(Player $player): ?Bet {
        return $this->bets[$player->getName()] ?? null;
    }

    public function openBet(Player $player, int $bet): void {
        $this->bets[$player->getName()] = new Bet($player, $bet);
    }

    public function removeBet(Player $player): void {
        unset($this->bets[$player->getName()]);
    }

    public function getBets(): array {
        return $this->bets;
    }

    public function calculateReward(int $bet, Item $a, Item $b, Item $c): int {
        if ($a->equalsExact($b) && $b->equalsExact($c)) {
            return $bet * 10;
        } elseif ($a->equalsExact($b) || $b->equalsExact($c) || $a->equalsExact($c)) {
            return $bet * 2; //
        } else {
            return 0;
        }
    }

    public function giveReward(Bet $bet): void {
        list ($a, $b, $c) = $bet->getResult();
        $reward = $this->calculateReward($bet->getBet(), $a, $b, $c);
        $player = $bet->getBettor();
        $this->removeBet($player);
        if ($reward > 0) {
            $player->sendMessage("§aCongratulations ! You won §e\${$reward}§a !");
            /*BedrockEconomyAPI::legacy()->addToPlayerBalance($player->getName(), $reward, function(bool $success) use ($player, $reward): void {
                if ($success) {
                }
            });*/
        } else {
            $player->sendMessage("§cYou lost...");
        }
    }

}