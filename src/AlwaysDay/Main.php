<?php

namespace AlwaysDay;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\World;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class Main extends PluginBase {

    /** @var array<string, bool> */
    private array $enabledWorlds = [];

    private Config $config;

    protected function onEnable(): void {
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . "enabled_worlds.yml", Config::YAML, []);
        $this->enabledWorlds = $this->config->getAll();

        foreach ($this->enabledWorlds as $worldName => $enabled) {
            if ($enabled) {
                $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
                if ($world !== null) {
                    $world->setTime(World::TIME_DAY);
                    $world->stopTime();
                }
            }
        }

        $this->getLogger()->info("AlwaysDay plugin enabled.");

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            foreach ($this->enabledWorlds as $worldName => $enabled) {
                if ($enabled) {
                    $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
                    if ($world !== null) {
                        $world->setTime(World::TIME_DAY);
                        $world->stopTime();
                    }
                }
            }
        }), 100); // setiap 5 detik
    }

    protected function onDisable(): void {
        $this->config->setAll($this->enabledWorlds);
        $this->config->save();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        $name = strtolower($command->getName());

        if ($name === "setalwaysday") {
            if (count($args) !== 2) {
                $sender->sendMessage("§cUsage: /setalwaysday <world> <on|off>");
                return true;
            }

            [$worldName, $option] = $args;

            $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);

            if ($world === null) {
                $sender->sendMessage("§cWorld '$worldName' not found or not loaded.");
                return true;
            }

            if ($option === "on") {
                $this->enabledWorlds[$worldName] = true;
                $world->setTime(World::TIME_DAY);
                $world->stopTime();
                $sender->sendMessage("§aAlways day enabled for world '$worldName'.");
            } elseif ($option === "off") {
                $this->enabledWorlds[$worldName] = false;
                $world->startTime();
                $sender->sendMessage("§eAlways day disabled for world '$worldName'.");
            } else {
                $sender->sendMessage("§cInvalid option. Use 'on' or 'off'.");
            }

            $this->config->setAll($this->enabledWorlds);
            $this->config->save();

            return true;
        }

        if ($name === "alwaysday") {
            if (count($args) === 0) {
                $sender->sendMessage("§aAvailable Commands:");
                $sender->sendMessage("§7/alwaysday info - Plugin information");
                $sender->sendMessage("§7/alwaysday dev - Show developer name");
                $sender->sendMessage("§7/setalwaysday <world> <on|off> - Enable/disable always day");
                return true;
            }

            if (strtolower($args[0]) === "info") {
                $sender->sendMessage("§b--- AlwaysDay Plugin Info ---");
                $sender->sendMessage("§fPlugin: §aAlwaysDay");
                $sender->sendMessage("§fVersion: §a1.1.0");
                $sender->sendMessage("§fAuthor: §aZakiGamestop");
                $sender->sendMessage("§fDeveloper: §aGamestopDev");
                $sender->sendMessage("§fCommand: §a/setalwaysday <world> <on|off>");
                return true;
            }

            if (strtolower($args[0]) === "dev") {
                $sender->sendMessage("§bDeveloper: §aGamestopDev");
                return true;
            }

            $sender->sendMessage("§cUnknown command, use /alwaysday to view the command");
            return true;
        }

        return false;
    }
}
