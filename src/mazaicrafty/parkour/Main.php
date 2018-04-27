<?php

namespace mazaicrafty\parkour;

use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\Config;
use pocketmine\level\sound\ClickSound;
use pocketmine\tile\Tile;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\event\player\PlayerMoveEvent;
use mazaicrafty\parkour\TimeMeasurement;

class Main extends PluginBase implements Listener{

    public function onEnable(): void{
        Server::getInstance()->getPluginManager()->registerEvents(self, self);
        $this->saveDefaultConfig();
        $dir = $this->getDataFolder();
        $this->startPos = new Config($dir, "StartPos", Config::YAML, []);
    }

    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        if ($event->getBlock()->getId() === 63 || $event->getBlock()->getId() === 68){
            $sign = $player->getLevel()->getTile($event->getBlock());
            if (!($sign instanceof Sign)){
				return;
			}
            $player->getLevel()->addSound(new ClickSound($player->getPosition(), [$player]));
            switch ($sign->getText()->getLine(0)){
                case "[Start]":
                $this->saveStartPos($player);
                Server::getInstance()->getScheduler()->schedulerDelayedTask(new TimeMeasurement(self, $player), 20);
                break;

                case "[Finish]":
                $countTime = TimeMeasurement::stopCount($player);
                $player->sendMessage(
                    "Your finish time is:\n".
                    $countTime
                );
                break;
            }
        }
    }

    public function onVoid(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        if ($event->getTo()->getFloorY() < 3){
            $startPos = $this->getStartPos($player);
            if ($startPos === null){
            }
            
        }
    }
    public function saveStartPos(Player $player){
        $pos = [
            "x" => $player->getX(),
            "y" => $player->getY(),
            "z" => $player->getZ(),
            "level" => $player->getlevel()
        ];

        $this->startPos->set($player->getName(), $pos["x"], $pos["y"], $pos["z"], $pos["level"]);
        $this->startPos->save();
    }

    public function getStartPos(Player $player){
        $data = $this->startPos->exists($player->getName());
        return $data;
    }

}

use mazaicrafty\parkour\Main;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class TimeMeasurement extends PluginTask{

    public function __construct(Main $main, Player $player){
        $this->main = $main;
        $this->player = $player;
    }

    public function onRun(int $ticks){
        $player = $this->player;
        $this->count[$player->getName()]++ = 0;
    }

    public function stopCount(Player $player){
        $this->getHandler()->cancel();
        return $this->count[$player->getName()];
    }
}
