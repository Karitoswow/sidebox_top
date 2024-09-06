<?php

use CodeIgniter\Database\BaseConnection;

class Top_model extends CI_Model
{
    public $realm;
    private BaseConnection $connection;
    private $realmId = 1;

    /**
     * Assign the realm object to the model
     */
    public function setRealm($id)
    {
        $this->realmId = $id;
        $this->realm = $this->realms->getRealm($this->realmId);
    }

    /**
     * Connect to the character database
     */
    public function connect()
    {
        $this->realm->getCharacters()->connect();
        $this->connection = $this->realm->getCharacters()->getConnection();
    }

    public function getTopAchievementPlayers($limit)
    {
        $this->connect();

        $result = $this->connection->query("SELECT " . table("characters", $this->realmId) . "." . column("characters", "guid", false, $this->realmId) . "," . table("characters", $this->realmId) . "." . column("characters", "name", false, $this->realmId) . "," . table("characters", $this->realmId) . "." . column("characters", "gender", false, $this->realmId) . "," . table("characters", $this->realmId) . "." . column("characters", "class", false, $this->realmId) . "," . table("characters", $this->realmId) . "." . column("characters", "race", false, $this->realmId) . ", COUNT(character_achievement.achievement) AS achievement_points FROM character_achievement RIGHT JOIN " . table("characters", $this->realmId) . " ON " . table("characters", $this->realmId) . "." . column("characters", "guid", false, $this->realmId) . " = character_achievement.`guid` GROUP BY " . table("characters", $this->realmId) . "." . column("characters", "guid", false, $this->realmId) . " ORDER BY achievement_points DESC LIMIT ?", [$limit]);

        return $this->getResult($result);
    }


    public function getTopCharactersPlayTime($limit)
    {
        $this->connect();

       // $result = $this->connection->query("SELECT * FROM `characters` ORDER BY totaltime DESC LIMIT ?", [$limit]);

        $result = $this->connection->query("SELECT " . columns("characters", ["guid", "name", "level", "class", "race", "gender", "totaltime"]) . " FROM " . table("characters", $this->realmId) . " ORDER BY " . column("characters", "totaltime", false, $this->realmId) . " DESC LIMIT ?", [$limit]);


        return $this->getResult($result);
    }

    public function getTopGuild($limit)
    {
        $this->connect();

        $result = $this->connection->query('SELECT ' . table('guild', $this->realmId) . '.' . column('guild', 'guildid', false, $this->realmId) . ', ' . table('guild', $this->realmId) . '.' . column('guild', 'leaderguid', false, $this->realmId) . ', ' . table('guild', $this->realmId) . '.' . column('guild', 'name', false, $this->realmId) . ', COUNT(character_achievement.achievement) AS achievement_points FROM character_achievement LEFT JOIN ' . table('guild_member', $this->realmId) . ' ON ' . table('guild_member', $this->realmId) . '.' . column('guild_member', 'guid', false, $this->realmId) . ' = character_achievement.guid LEFT JOIN ' . table('guild', $this->realmId) . ' ON ' . table('guild', $this->realmId) . '.' . column('guild', 'guildid', false, $this->realmId) . ' = ' . table('guild_member', $this->realmId) . '.' . column('guild_member', 'guildid', false, $this->realmId) . '  WHERE ' . table('guild', $this->realmId) . '.' . column('guild', 'guildid', false, $this->realmId) . " <> '' GROUP BY " . table('guild', $this->realmId) . '.' . column('guild', 'guildid', false, $this->realmId) . ' ORDER BY achievement_points DESC LIMIT ?', [$limit]);

        if ($result && $result->getNumRows() > 0) {
            $results = $result->getResultArray();

            foreach ($results as $key => $guild) {
                $results[$key]['leaderName'] = $this->realms->getRealm($this->realmId)->getCharacters()->getNameByGuid($guild[column('guild', 'leaderguid', false, $this->realmId)]);
                $results[$key]['faction'] = $this->realms->getRealm($this->realmId)->getCharacters()->getFaction($guild[column('guild', 'leaderguid', false, $this->realmId)]);
                $results[$key]['achievement_points'] = round(($guild['achievement_points'] / $this->getMembersGuild($guild[column('guild', 'guildid', false, $this->realmId)])));
            }

            return $this->addrank($this->orderBy($results, 'achievement_points', 'desc'));
        }

        return false;
    }

    public function addrank($results)
    {
        // Add rank
        $i = 1;
        foreach ($results as $key => $Guild) {
            $results[$key]['rank'] = $i;
            $i++;
        }
        return $results;
    }

    public function orderBy($items, $attr, $order)
    {
        $sortedItems = [];
        foreach ($items as $item) {
            $key = is_object($item) ? $item->{$attr} : $item[$attr];
            $sortedItems[$key] = $item;
        }
        if ($order === 'desc') {
            krsort($sortedItems);
        } else {
            ksort($sortedItems);
        }

        return array_values($sortedItems);
    }

    public function getYesterdayPlayers($limit)
    {
        $this->connect();

        $result = $this->connection->query("SELECT " . columns("characters", ["guid", "name", "level", "class", "race", "gender", "yesterdayKills"]) . " FROM " . table("characters", $this->realmId) . " WHERE " . column("characters", "yesterdayKills", false, $this->realmId) . " > 0 and " . column("characters", "name", false, $this->realmId) . " != '' ORDER BY " . column("characters", "yesterdayKills", false, $this->realmId) . " DESC LIMIT ?", [$limit]);

        return $this->getResult($result);
    }

    public function getTotalPlayers($limit)
    {
        $this->connect();

        $result = $this->connection->query("SELECT " . columns("characters", ["guid", "name", "level", "class", "race", "gender", "totalKills"]) . " FROM " . table("characters", $this->realmId) . " WHERE " . column("characters", "totalKills", false, $this->realmId) . " > 0 and " . column("characters", "name", false, $this->realmId) . " != '' ORDER BY " . column("characters", "totalKills", false, $this->realmId) . " DESC LIMIT ?", [$limit]);

        return $this->getResult($result);
    }

    public function getKillTodayPlayers($limit)
    {
        $this->connect();

        $result = $this->connection->query("SELECT " . columns("characters", ["guid", "name", "level", "class", "race", "gender", "todayKills"]) . " FROM " . table("characters", $this->realmId) . " WHERE " . column("characters", "todayKills", false, $this->realmId) . " > 0 and " . column("characters", "name", false, $this->realmId) . " != '' ORDER BY " . column("characters", "todayKills", false, $this->realmId) . " DESC LIMIT ?", [$limit]);

        return $this->getResult($result);
    }

    public function getGuildName($guid)
    {
        $this->connect();

        $query = $this->connection->query('SELECT ' . table('guild', $this->realmId) . '.' . column('guild', 'name', false, $this->realmId) . ' FROM ' . table('guild_member', $this->realmId) . ' RIGHT JOIN ' . table('guild', $this->realmId) . ' ON ' . table('guild_member', $this->realmId) . '.' . column('guild_member', 'guildid', false, $this->realmId) . ' = ' . table('guild', $this->realmId) . '.' . column('guild', 'guildid', false, $this->realmId) . ' WHERE guild_member.guid = ?', [$guid]);

        if ($query && $query->getNumRows() > 0) {
            $row = $query->getResultArray();

            return $row[0]['name'];
        } else {
            $query2 = $this->connection->query('SELECT ' . column('guild', 'name', false, $this->realmId) . ' FROM ' . table('guild', $this->realmId) . ' WHERE ' . column('guild', 'leaderguid', false, $this->realmId) . ' = ?', [$guid]);

            if ($query2 && $query2->getNumRows() > 0) {
                $row2 = $query2->getResultArray();

                return $row2[0]['name'];
            } else {
                return false;
            }
        }
    }

    public function getMembersGuild($guildid)
    {
        $this->connect();

        $query = $this->connection->query('SELECT ' . column('guild_member', 'guildid', false, $this->realmId) . ', COUNT(' . column('guild_member', 'guildid', false, $this->realmId) . ') AS Members FROM ' . table('guild_member', $this->realmId) . ' WHERE ' . column('guild_member', 'guildid', false, $this->realmId) . ' = ?', [$guildid]);

        if ($query && $query->getNumRows() > 0) {
            $row = $query->getResultArray();

            return $row[0]['Members'];
        } else {
            return false;
        }
    }

    /**
     * @param $result
     * @return array|false
     */
    private function getResult($result): array|false
    {
        if ($result && $result->getNumRows() > 0) {
            $players = $result->getResultArray();

            // Add rank
            $i = 1;
            foreach ($players as $key => $player) {
                $players[$key]['rank'] = $i;
                $players[$key]['guild'] = $this->getGuildName($player[column("characters", "guid", false, $this->realmId)]);
                $i++;
            }

            return $players;
        }

        return false;
    }
}
