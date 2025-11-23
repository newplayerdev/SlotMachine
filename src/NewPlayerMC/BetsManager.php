<?php

namespace NewPlayerMC;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

class BetsManager
{
    use SingletonTrait;

    /** playerName => bet */
    private array $bets = [];

    /** @var Item[] */
    private array $slotItems = [];

    /**
     * playerName => [bet1 (int $), result1 (bool)], [bet2, result2], ..., [bet10, result10]
     * @var array
     */
    private array $playersSave = [];

    private Config $saveFile;

    public function __construct()
    {
        self::$instance = $this;

        $this->saveFile = new Config(Main::getInstance()->getDataFolder() . "saves.json", Config::JSON);

        $this->loadHistory();

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

    public function saveBet(Player $player, Bet $bet): void {
        $this->playersSave[$player->getName()][] = [$bet->getBet(), $bet->isWon(), $bet->getReward()];
        if (count($this->playersSave[$player->getName()]) > 10) array_shift($this->playersSave[$player->getName()]);
    }

    /**
     * Returns every bets opened
     * @return array
     */
    public function getBets(): array {
        return $this->bets;
    }

    public function loadHistory(): void {
        $this->playersSave = $this->getSave()->getAll();
    }

    public function saveHistory(): void {
        $this->getSave()->setAll($this->playersSave);
        $this->getSave()->save();
    }

    public function getSave(): Config { // forgot how to do config
        return $this->saveFile;
    }

    /**
     * Returns player's bets history
     * Need to be optimized
     * @param Player $player
     * @return array
     */
    public function getPlayerHistory(Player $player): array {
        return isset($this->playersSave[$player->getName()]) ? $this->playersSave[$player->getName()] : [];
    }

    /** Return player's history under a lore format to be displayed in the menu */
    public function getLoreHistory(Player $player): array {
        $history = $this->getPlayerHistory($player);
        if (!empty($history)) {
            $i = 0;
            $lore = [];
            foreach ($history as $betEntry) {
                if ($i >= 10) {
                    break;
                }

                $result = $betEntry[1] ? "§aWon" : "§cLost";
                $lore[] = "§r$result §f- Bet: $betEntry[0]$ - Prize: {$betEntry[2]}$";

                $i++;
            }

            return $lore;
        } else return ["No bet launched"];
    }

    // Need some optimization i think
    public function calculateReward(Bet $bet): int {
        $prize = $bet->getBet();
        $bet->setWon();
        list($a, $b, $c) = $bet->getResult();
        if ($a->equalsExact($b) && $b->equalsExact($c)) {
            $prize *= 10;
        } elseif ($a->equalsExact($b) || $b->equalsExact($c) || $a->equalsExact($c)) {
            $prize *= 2;
        } else {
            $bet->setWon(false);
            $prize = 0;
        }
        $bet->setReward($prize);
        return $prize;
    }

    public function giveReward(Bet $bet): void {
        $reward = $this->calculateReward($bet);
        $player = $bet->getBettor();
        $this->saveBet($player, $bet);
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