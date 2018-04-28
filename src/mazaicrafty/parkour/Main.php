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
use mazaicrafty\parkour\Main;
use pocketmine\scheduler\PluginTask;

class Main extends PluginBase implements Listener{

    public function onEnable(): void{
        Server::getInstance()->getPluginManager()->registerEvents($this, $this);
        $dir = $this->getDataFolder();
        $this->startPos = new Config($dir . "StartPos.yml", Config::YAML);
        $this->time = new Config($dir . "time.yml", Config::YAML);
    }

    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        //if ($event->getBlock()->getId() === 1){
            //$sign = $player->getLevel()->getTile($event->getBlock());
            //if (!($sign instanceof Sign)){
			//	return;
			//}
            //$player->getLevel()->addSound(new ClickSound($player->getPosition(), [$player]));
            switch ($event->getBlock()->getId()){
                case 1:
                $this->removeTime($player);
                $this->saveStartPos($player);
                $this->getServer()->getScheduler()->scheduleRepeatingTask(new TimeMeasurement($this, $player), 20);
                break;

                case 2:
                //$instance = new TimeMeasurement
                $countTime = TimeMeasurement::getInstance()->stopCount($player);
                var_dump($countTime);
                $hms = $this->s2h($countTime);
                $player->sendMessage(
                    "Your finish time is:\n".
                    $hms
                );
                break;
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

    public function s2h(int $seconds){
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;
        
        $hms = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
        return $hms;
    }

    public function removeTime(Player $player){
        $this->time->remove($player->getName());
    }

    public function saveTime(Player $player, int $count){
        $this->time->set($player->getName(), $count);
        $this->time->save();
    }

    public function getTime(Player $player){
        $data = $this->time->get($player->getName());
        return $data;
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
        $data = $this->startPos->get($player->getName());
        return $data;
    }
}

class TimeMeasurement extends PluginTask{

    private $main;
    private $player;

    static $instance;

    public function __construct($main, $player){
        parent::__construct($main);
        $this->player = $player;
        $this->count = 0;
    }

    public function onRun(int $ticks){
        $player = $this->player;
        $time = $this->count++;
        $this->getOwner()->saveTime($player, $time);
    }

    public function stopCount(Player $player){
        $this->getOwner()->getServer()->getScheduler()->cancelTask($this->getTaskId());
        $time = $this->getOwner()->getTime($player);
        return $time;
    }

    public static function getInstance(){
        return self::$instance;
    }
}
