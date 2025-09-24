<?php

namespace NewPlayerMC;

use pocketmine\item\Item;
use pocketmine\player\Player;

class Bet
{
    /** @var Item[] */
    private array $result = [];

    private bool $hasStarted = false;

    public function __construct(
        protected Player $bettor,
        protected int $bet
    )
    {
    }

    /**
     * @return int
     */
    public function getBet(): int
    {
        return $this->bet;
    }

    /**
     * @return Player
     */
    public function getBettor(): Player
    {
        return $this->bettor;
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * @param array $result
     */
    public function setResult(array $result): void
    {
        $this->result = $result;
    }

    /**
     * @return bool
     */
    public function hasStarted(): bool
    {
        return $this->hasStarted;
    }

}