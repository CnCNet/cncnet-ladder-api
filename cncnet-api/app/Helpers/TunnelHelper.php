<?php

namespace App\Helpers;

class TunnelHelper
{
    public static function getTunnelNameByIpHost($ipHost)
    {
        $tunnels = [
            "United States CnCNet Singapore - rowsnet.com [Official]	v2"                 => "139.180.185.106:50000",
            "United States [FC] EU Tunnel - https://fortuneschaos.com [Official]	v2"     =>    "52.232.96.199:50000",
            "Germany CnCnet Europe - rowsnet.com [Official]	v2"                             => "162.55.221.83:50000",
            "Germany CnCnet Europe - rowsnet.com [Official]	v3"                             => "162.55.221.83:50001",
            "United States CnCNet Singapore - rowsnet.com [Official] v3"                    => "139.180.185.106:50001",
            "Japan CnCNet Japan - rowsnet.com [Official] v2"                                => "45.32.30.135:50000",
            "Japan CnCNet Japan - rowsnet.com [Official] v3"                                => "45.32.30.135:50001",
            "Australia CnCNet Australia - rowsnet.com [Official] v3"                        => "45.63.24.182:50001",
            "Australia CnCNet Australia - rowsnet.com [Official] v2"                        => "45.63.24.182:50000",
            "United States [FC] EU Tunnel - https://fortuneschaos.com [Community] v3"       => "52.232.96.199:50001",
            "United States US - Miami [Community] v3"                                       => "8.6.193.74:50001",
            "Australia WormsRulez [Community] v2"                                           => "202.61.248.165:50000",
            "United States [DE] Clan-Server.EU [3] [Community] v2"                          => "168.119.232.39:50000",
            "Russian Federation [DE] Clan-Server.EU [2] [Community]	v2"                     => "195.201.29.215:50000",
            "United States AUNZ Kippage Gaming [Community] v2"                              => "139.99.178.157:50000",
            "Germany United-Forum.de [Community] v2"                                        => "82.165.113.214:50000",
        ];
        foreach ($tunnels as $name => $ip)
        {
            if ($ip == $ipHost)
            {
                return $name;
            }
        }
        return null;
    }

    public static function getTunnelsFromStats($connectionStats)
    {
        $tunnels = [];
        foreach ($connectionStats as $connectionStat)
        {
            $name = TunnelHelper::getTunnelNameByIpHost($connectionStat->ipAddress->address . ':' . $connectionStat->port);

            if (!in_array($name, $tunnels))
                $tunnels[] = $name;
        }
        
        return $tunnels;
    }
}
