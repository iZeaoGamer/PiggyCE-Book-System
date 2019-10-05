<?php

declare(strict_types=1);

namespace Bronzehail\BookSystem;

use pocketmine\plugin\PluginBase;
use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\command\{
    Command, CommandSender
};

use DaPigGuy\PiggyCustomEnchants\CustomEnchants\CustomEnchants;

class Main extends PluginBase implements Listener{

    /* @var Config*/
    public $config;

    public function onLoad(){
        @mkdir($this->getDataFolder());
    }

    public function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("config.yml");
        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML);
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
        $formapi = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $ce = $this->getServer()->getPluginManager()->getPlugin("PiggyCustomEnchants");
        $form = $formapi->createSimpleForm(function ($sender, $data){
            if($data !== null){
                $this->Confirm($sender, $data);
            }
        });

        $form->setTitle("§l§3BookShop");

        $form->addButton($ce->getRarityColor(10) . $this->getNameByData(0));
        $form->addButton($ce->getRarityColor(5) . $this->getNameByData(1));
        $form->addButton($ce->getRarityColor(2) . $this->getNameByData(2));
        $form->addButton($ce->getRarityColor(1) . $this->getNameByData(3));
        $form->sendToPlayer($sender);
        return true;
    }

    public function getNameByData(int $data, $id = true): string{
        if($id){
            switch($data){
                case 0:
                    return "§aCommon";
                case 1:
                    return "§eUncommon";
                case 2:
                    return "§6Rare";
                case 3:
                    return "§bMythic";
            }
        }else{
            switch($data){
                case 0:
                    return "10";
                case 1:
                    return "5";
                case 2:
                    return "2";
                case 3:
                    return "1";
            }
        }
    }

    /**
     * @param int $data
     * @return bool|mixed
     */
    public function getCost(int $data){
        switch($data){
            case 0:
                return $this->config->get("Common");
            case 1:
                return $this->config->get("Uncommon");
            case 2:
                return $this->config->get("Rare");
            case 3:
                return $this->config->get("Mythic");
        }
        return true;
    }

    public function Confirm($sender, int $dataid): void{
        $formapi = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $ce = $this->getServer()->getPluginManager()->getPlugin("PiggyCustomEnchants");
        $form = $formapi->createCustomForm(function ($sender, $data) use($dataid, $ce){
            if($data !== null){
                if(EconomyAPI::getInstance()->myMoney($sender) < $this->getCost($dataid)){
                    $sender->sendMessage(C::RED . "You don't have enough money!");
                    return;
                }

                $item = Item::get(340);
                $nbt = $item->getNamedTag();
                $nbt->setString("ceid", (string)$dataid);
                $item->setCustomName($ce->getRarityColor((int)$this->getNameByData($dataid, false)) . $this->getNameByData($dataid) . C::RESET . C::YELLOW . " Book");
                foreach($ce->enchants as $id => $data){
                    if($id == $dataid) $item->setLore([$data[5], "\n" . " \n" . C::GRAY . "§bTap ground to get random custom enchantment"]);
                }
                $sender->getInventory()->addItem($item);
                EconomyAPI::getInstance()->takeMoney($sender->getName(), $this->getCost($dataid));
            }
        });

        $form->setTitle($ce->getRarityColor((int)$this->getNameByData($dataid, false)) . $this->getNameByData($dataid));
        $form->addLabel("Cost: $" . $this->getCost($dataid));
        $form->sendToPlayer($sender);
    }

    public function onInteract(PlayerInteractEvent $e): void{
        $player = $e->getPlayer();
        $item = $e->getItem();
        $ce = $this->getServer()->getPluginManager()->getPlugin("PiggyCustomEnchants");

        if($item->getId() == 340){
            if($item->getNamedTag()->hasTag("ceid", StringTag::class)){
                $e->setCancelled();

                $id = $item->getNamedTag()->getString("ceid");

                foreach($ce->enchants as $eid => $data){
                    if($data[3] == $this->getNameByData((int)$id)){
                        switch($id){
                            case 0: //Common
                                $enchs = [114, 101, 109, 601, 100, 405];
                                break;
                            case 1: //Uncommon
                                $enchs = [122, 120, 309, 113, 801, 412, 408, 206, 202, 401, 209, 208, 603, 500, 402, 207, 210, 312, 504, 602, 304, 211, 104, 403, 203, 406, 201, 502, 421, 111, 305, 115];
                                break;
                            case 2: //Rare
                                $enchs = [420, 411, 311, 416, 102, 410, 409, 804, 200, 404, 313, 310, 422, 600, 204, 315, 400, 303, 307, 423, 308, 803, 205, 805, 316];
                                break;
                            case 3: //Mythic
                                $enchs = [604, 306, 212, 419, 314, 301];
                                break;
                        }
                        $enchanted = false;

                        if($enchanted == false){
                            $enchanted = true;
                            $info["ench"] = $enchs[array_rand($enchs)];
                            $ench = CustomEnchants::getEnchantment($info["ench"]);
                            $info["lvl"] = mt_rand(1, $ce->getEnchantMaxLevel($ench));
                            $book = Item::get(Item::ENCHANTED_BOOK);
                            $player->getInventory()->setItemInHand($ce->addEnchantment($player->getInventory()->getItemInHand(), $info["ench"], $info["lvl"], $player->hasPermission("piggycustomenchants.overridecheck") ? false : true, $player));
                            return;
                        }
                    }
                }
            }
        }
    }
}
