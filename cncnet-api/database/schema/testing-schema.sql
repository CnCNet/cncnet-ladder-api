/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `achievements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `achievement_type` enum('IMMEDIATE','CAREER','MULTI') NOT NULL,
  `order` int(11) NOT NULL DEFAULT 999,
  `tag` text DEFAULT NULL,
  `ladder_id` int(10) unsigned NOT NULL,
  `achievement_name` text NOT NULL,
  `achievement_description` text NOT NULL,
  `heap_name` text DEFAULT NULL,
  `object_name` text DEFAULT NULL,
  `cameo` text DEFAULT NULL,
  `unlock_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `achievements_ladder_id_foreign` (`ladder_id`),
  CONSTRAINT `achievements_ladder_id_foreign` FOREIGN KEY (`ladder_id`) REFERENCES `ladders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `achievements_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `achievements_progress` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `achievement_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `achievement_unlocked_date` timestamp NULL DEFAULT NULL,
  `count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `achievements_progress_achievement_id_foreign` (`achievement_id`),
  KEY `achievements_progress_user_id_foreign` (`user_id`),
  CONSTRAINT `achievements_progress_achievement_id_foreign` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `achievements_progress_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `ban_type` int(10) unsigned NOT NULL,
  `internal_note` text NOT NULL,
  `plubic_reason` text NOT NULL,
  `expires` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip_address_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `short` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clan_cache_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clan_cache_updates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clan_cache_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clan_caches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clan_caches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_history_id` int(10) unsigned NOT NULL,
  `clan_id` int(10) unsigned NOT NULL,
  `clan_name` varchar(255) NOT NULL,
  `points` int(11) NOT NULL,
  `wins` int(11) NOT NULL,
  `games` int(11) NOT NULL,
  `side` int(11) DEFAULT NULL,
  `fps` int(11) NOT NULL,
  `country` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clan_caches_clan_id_foreign` (`clan_id`),
  KEY `clan_caches_ladder_history_id_clan_id_index` (`ladder_history_id`,`clan_id`),
  KEY `clan_caches_ladder_history_id_points_index` (`ladder_history_id`,`points`),
  CONSTRAINT `clan_caches_clan_id_foreign` FOREIGN KEY (`clan_id`) REFERENCES `clans` (`id`),
  CONSTRAINT `clan_caches_ladder_history_id_foreign` FOREIGN KEY (`ladder_history_id`) REFERENCES `ladder_history` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clan_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clan_invitations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clan_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `type` enum('invited','cancelled','joined','kicked','left','promoted','demoted') NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clan_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clan_players` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clan_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `clan_role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clan_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clan_ratings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clan_id` int(10) unsigned NOT NULL,
  `rating` int(11) NOT NULL,
  `peak_rating` int(11) NOT NULL,
  `rated_games` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clan_ratings_clan_id_index` (`clan_id`),
  KEY `clan_ratings_rating_index` (`rating`),
  CONSTRAINT `clan_ratings_clan_id_foreign` FOREIGN KEY (`clan_id`) REFERENCES `clans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clan_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clan_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_id` int(11) NOT NULL,
  `short` text NOT NULL,
  `name` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `avatar_path` varchar(255) DEFAULT NULL,
  `ex_player_id` int(11) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_version` (
  `version` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `format` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `platform` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countable_game_objects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countable_game_objects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `heap_name` char(3) NOT NULL,
  `heap_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `cameo` varchar(255) NOT NULL,
  `cost` int(11) NOT NULL,
  `value` int(11) NOT NULL,
  `ui_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `game_object_schema_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `countable_game_objects_heap_name_name_index` (`heap_name`,`name`),
  KEY `countable_game_objects_heap_id_index` (`heap_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countable_object_heaps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countable_object_heaps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_verifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_audit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `ladder_history_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `game_audit_game_id_foreign` (`game_id`),
  KEY `game_audit_ladder_history_id_foreign` (`ladder_history_id`),
  CONSTRAINT `game_audit_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  CONSTRAINT `game_audit_ladder_history_id_foreign` FOREIGN KEY (`ladder_history_id`) REFERENCES `ladder_history` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_object_counts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_object_counts` (
  `stats_id` int(10) unsigned NOT NULL,
  `countable_game_objects_id` int(10) unsigned NOT NULL,
  `count` int(11) NOT NULL,
  KEY `game_object_counts_stats_id_index` (`stats_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_object_schemas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_object_schemas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_reports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `player_id` int(10) unsigned NOT NULL,
  `best_report` tinyint(1) NOT NULL,
  `manual_report` tinyint(1) NOT NULL,
  `duration` int(11) NOT NULL,
  `valid` tinyint(1) NOT NULL,
  `finished` tinyint(1) NOT NULL,
  `fps` int(11) NOT NULL,
  `oos` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `pings_sent` int(11) NOT NULL DEFAULT 0,
  `pings_received` int(11) NOT NULL DEFAULT 0,
  `clan_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `game_reports_game_id_index` (`game_id`),
  KEY `game_reports_clan_id_index` (`clan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL COMMENT 'Game Id',
  `player_id` int(11) unsigned NOT NULL,
  `stats_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `id` (`id`) USING BTREE,
  KEY `gid` (`game_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_history_id` int(10) unsigned NOT NULL,
  `wol_game_id` int(11) unsigned NOT NULL,
  `bamr` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `crat` int(11) NOT NULL,
  `cred` longtext NOT NULL,
  `shrt` int(11) NOT NULL,
  `supr` int(11) NOT NULL,
  `unit` int(11) NOT NULL,
  `plrs` int(11) NOT NULL,
  `scen` varchar(255) DEFAULT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `game_report_id` int(10) unsigned DEFAULT NULL,
  `qm_match_id` int(10) unsigned DEFAULT NULL,
  `game_type` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `games_game_report_id_index` (`game_report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `games_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games_backup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_history_id` int(10) unsigned NOT NULL,
  `wol_game_id` int(10) unsigned NOT NULL,
  `afps` int(11) NOT NULL,
  `oosy` tinyint(1) NOT NULL,
  `bamr` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `crat` int(11) NOT NULL,
  `dura` longtext NOT NULL,
  `cred` longtext NOT NULL,
  `shrt` int(11) NOT NULL,
  `supr` int(11) NOT NULL,
  `unit` int(11) NOT NULL,
  `plrs` int(11) NOT NULL,
  `scen` varchar(255) NOT NULL,
  `hash` varchar(255) NOT NULL,
  `sdfx` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `games_raw`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games_raw` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hash` varchar(255) NOT NULL,
  `packet` longtext NOT NULL,
  `game_id` int(10) unsigned NOT NULL,
  `ladder_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ip_address_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ip_address_histories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip_address_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ip_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ip_addresses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `city` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `irc_associations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `irc_associations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `irc_hostmask_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ladder_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `clan_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `refreshed_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `irc_hostmasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `irc_hostmasks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `irc_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `irc_players` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `ladder_id` int(11) NOT NULL,
  `username` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` text NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ladder_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ladder_admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `admin` tinyint(1) NOT NULL,
  `moderator` tinyint(1) NOT NULL,
  `tester` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ladder_alert_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ladder_alert_players` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `ladder_alert_id` int(11) NOT NULL,
  `show` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ladder_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ladder_alerts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ladder_games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ladder_games` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `ladder_history_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ladder_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ladder_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_id` int(11) NOT NULL,
  `starts` datetime NOT NULL,
  `ends` datetime NOT NULL,
  `short` varchar(255) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ladder_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ladder_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('player','clan') NOT NULL,
  `match` enum('1vs1','2vs2','3vs3','4vs4') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ladders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ladders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `abbreviation` varchar(255) NOT NULL,
  `game` enum('ra','ts','yr') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `clans_allowed` tinyint(1) NOT NULL DEFAULT 0,
  `game_object_schema_id` int(11) NOT NULL,
  `map_pool_id` int(11) DEFAULT NULL,
  `private` tinyint(1) NOT NULL DEFAULT 0,
  `order` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `league_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `league_players` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `ladder_id` int(10) unsigned NOT NULL,
  `can_play_both_tiers` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `league_players_user_id_foreign` (`user_id`),
  KEY `league_players_ladder_id_foreign` (`ladder_id`),
  CONSTRAINT `league_players_ladder_id_foreign` FOREIGN KEY (`ladder_id`) REFERENCES `ladders` (`id`),
  CONSTRAINT `league_players_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map_headers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_headers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `startX` int(11) NOT NULL,
  `startY` int(11) NOT NULL,
  `numStartingPoints` int(11) NOT NULL,
  `map_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `map_headers_map_id_foreign` (`map_id`),
  CONSTRAINT `map_headers_map_id_foreign` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map_pools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_pools` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ladder_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map_side_strings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_side_strings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `value` blob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_tiers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `map_pool_id` int(10) unsigned NOT NULL,
  `tier` int(10) unsigned NOT NULL,
  `max_vetoes` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `map_tiers_map_pool_id_foreign` (`map_pool_id`),
  CONSTRAINT `map_tiers_map_pool_id_foreign` FOREIGN KEY (`map_pool_id`) REFERENCES `map_pools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `map_waypoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_waypoints` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bit_idx` int(10) unsigned NOT NULL,
  `x` int(10) unsigned NOT NULL,
  `y` int(10) unsigned NOT NULL,
  `map_header_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `map_waypoints_map_header_id_foreign` (`map_header_id`),
  CONSTRAINT `map_waypoints_map_header_id_foreign` FOREIGN KEY (`map_header_id`) REFERENCES `map_headers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `ladder_id` int(11) DEFAULT NULL,
  `spawn_count` int(11) NOT NULL DEFAULT 2,
  `image_path` varchar(255) NOT NULL,
  `image_hash` varchar(255) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maps_backup` (
  `id` int(11) NOT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `author_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `news_author_id_foreign` (`author_id`),
  CONSTRAINT `news_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `object_schema_managers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `object_schema_managers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_object_schema_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY `password_resets_email_index` (`email`) USING BTREE,
  KEY `password_resets_token_index` (`token`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_active_handles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_active_handles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_alerts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `seen_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_cache_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_cache_updates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_cache_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_caches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_caches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_history_id` int(10) unsigned NOT NULL,
  `player_id` int(10) unsigned NOT NULL,
  `player_name` varchar(255) NOT NULL,
  `card` int(11) DEFAULT NULL,
  `points` int(11) NOT NULL,
  `wins` int(11) NOT NULL,
  `games` int(11) NOT NULL,
  `percentile` int(11) NOT NULL,
  `side` int(11) DEFAULT NULL,
  `fps` int(11) NOT NULL,
  `country` int(11) DEFAULT NULL,
  `tier` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `player_caches_ladder_history_id_player_id_index` (`ladder_history_id`,`player_id`),
  KEY `player_caches_ladder_history_id_points_index` (`ladder_history_id`,`points`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_data_strings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_data_strings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_game_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_game_reports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `game_report_id` int(10) unsigned NOT NULL,
  `player_id` int(10) unsigned NOT NULL,
  `local_id` int(11) NOT NULL,
  `local_team_id` int(11) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `stats_id` int(11) DEFAULT NULL,
  `disconnected` tinyint(1) NOT NULL DEFAULT 0,
  `no_completion` tinyint(1) NOT NULL DEFAULT 0,
  `quit` tinyint(1) NOT NULL DEFAULT 0,
  `won` tinyint(1) NOT NULL DEFAULT 0,
  `defeated` tinyint(1) NOT NULL DEFAULT 0,
  `draw` tinyint(1) NOT NULL DEFAULT 0,
  `spectator` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `backupPts` int(11) NOT NULL DEFAULT 0,
  `spawn` int(11) DEFAULT NULL,
  `clan_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `player_game_reports_player_id_index` (`player_id`),
  KEY `player_game_reports_game_report_id_index` (`game_report_id`),
  KEY `player_game_reports_clan_id_index` (`clan_id`)
  KEY `player_game_reports_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_games` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `player_id` int(10) unsigned NOT NULL,
  `opponent_id` int(11) DEFAULT NULL,
  `result` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_histories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) unsigned NOT NULL,
  `ladder_history_id` int(10) unsigned NOT NULL,
  `tier` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `cancels` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `player_histories_player_id_index` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_points` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `points_awarded` int(11) NOT NULL,
  `game_won` tinyint(1) NOT NULL,
  `game_id` int(10) unsigned NOT NULL,
  `player_id` int(10) unsigned NOT NULL,
  `ladder_history_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_ratings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `peak_rating` int(11) NOT NULL,
  `rated_games` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `player_ratings_player_id_index` (`player_id`),
  KEY `player_ratings_rating_index` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `players` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `username` varchar(255) NOT NULL,
  `ladder_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `card_id` int(10) unsigned NOT NULL,
  `is_bot` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qm_canceled_matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qm_canceled_matches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qm_match_id` bigint(20) unsigned NOT NULL,
  `player_id` int(10) unsigned NOT NULL,
  `ladder_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qm_canceled_matches_qm_match_id_foreign` (`qm_match_id`),
  KEY `qm_canceled_matches_player_id_foreign` (`player_id`),
  KEY `qm_canceled_matches_ladder_id_foreign` (`ladder_id`),
  CONSTRAINT `qm_canceled_matches_ladder_id_foreign` FOREIGN KEY (`ladder_id`) REFERENCES `ladders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qm_canceled_matches_player_id_foreign` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qm_canceled_matches_qm_match_id_foreign` FOREIGN KEY (`qm_match_id`) REFERENCES `qm_matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qm_connection_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qm_connection_stats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qm_match_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `peer_id` int(11) NOT NULL,
  `ip_address_id` int(11) NOT NULL,
  `port` int(11) NOT NULL,
  `rtt` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `qm_connection_stats_player_id_index` (`player_id`),
  KEY `qm_connection_stats_qm_match_id_index` (`qm_match_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qm_ladder_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qm_ladder_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_id` int(11) NOT NULL,
  `player_count` int(11) NOT NULL,
  `map_vetoes` int(11) NOT NULL,
  `max_difference` int(11) NOT NULL,
  `all_sides` varchar(255) NOT NULL,
  `allowed_sides` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `bail_time` int(11) NOT NULL DEFAULT 60,
  `bail_fps` int(11) NOT NULL DEFAULT 30,
  `tier2_rating` int(11) NOT NULL DEFAULT 0,
  `rating_per_second` double NOT NULL,
  `max_points_difference` int(11) NOT NULL,
  `points_per_second` double NOT NULL,
  `use_elo_points` tinyint(1) NOT NULL DEFAULT 1,
  `wol_k` int(11) NOT NULL DEFAULT 64,
  `show_map_preview` tinyint(1) NOT NULL DEFAULT 1,
  `reduce_map_repeats` int(11) NOT NULL DEFAULT 0,
  `ladder_rules_message` text NOT NULL,
  `ladder_discord` varchar(255) NOT NULL,
  `point_filter_rank_threshold` int(11) NOT NULL DEFAULT 50,
  `match_ai_after_seconds` int(10) unsigned NOT NULL DEFAULT 180,
  `max_active_players` int(11) NOT NULL DEFAULT 1,
  `use_ranked_map_picker` text NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qm_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qm_maps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_id` int(11) NOT NULL,
  `map_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `bit_idx` int(11) NOT NULL,
  `valid` tinyint(1) NOT NULL,
  `spawn_order` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `team1_spawn_order` varchar(255) NOT NULL,
  `team2_spawn_order` varchar(255) NOT NULL,
  `allowed_sides` varchar(255) NOT NULL,
  `admin_description` varchar(255) NOT NULL,
  `map_pool_id` int(11) NOT NULL,
  `rejectable` tinyint(1) NOT NULL DEFAULT 1,
  `default_reject` tinyint(1) NOT NULL DEFAULT 0,
  `random_spawns` tinyint(1) NOT NULL DEFAULT 0,
  `map_tier` int(10) unsigned NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `qm_maps_map_id_index` (`map_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qm_maps_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qm_maps_backup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_id` int(11) NOT NULL,
  `map_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `bit_idx` int(11) NOT NULL,
  `valid` tinyint(1) NOT NULL,
  `spawn_order` varchar(255) NOT NULL,
  `speed` int(11) NOT NULL,
  `credits` int(11) NOT NULL,
  `bases` tinyint(1) NOT NULL,
  `units` int(11) NOT NULL,
  `tech` int(11) DEFAULT NULL,
  `short_game` tinyint(1) DEFAULT NULL,
  `fog` tinyint(1) DEFAULT NULL,
  `redeploy` tinyint(1) DEFAULT NULL,
  `crates` tinyint(1) DEFAULT NULL,
  `multi_eng` tinyint(1) DEFAULT NULL,
  `allies` tinyint(1) DEFAULT NULL,
  `dog_kill` tinyint(1) DEFAULT NULL,
  `bridges` tinyint(1) DEFAULT NULL,
  `supers` tinyint(1) DEFAULT NULL,
  `build_ally` tinyint(1) DEFAULT NULL,
  `spawn_preview` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `game_mode` varchar(255) DEFAULT NULL,
  `multi_factory` tinyint(1) DEFAULT NULL,
  `firestorm` tinyint(1) DEFAULT NULL,
  `ra2_mode` tinyint(1) DEFAULT NULL,
  `harv_truce` tinyint(1) DEFAULT NULL,
  `aimable_sams` tinyint(1) DEFAULT NULL,
  `attack_neutral` tinyint(1) DEFAULT NULL,
  `fix_ai_ally` tinyint(1) DEFAULT NULL,
  `ally_reveal` tinyint(1) DEFAULT NULL,
  `am_fast_build` tinyint(1) DEFAULT NULL,
  `parabombs` tinyint(1) DEFAULT NULL,
  `fix_formation_speed` tinyint(1) DEFAULT NULL,
  `fix_magic_build` tinyint(1) DEFAULT NULL,
  `fix_range_exploit` tinyint(1) DEFAULT NULL,
  `super_tesla_fix` tinyint(1) DEFAULT NULL,
  `forced_alliances` tinyint(1) DEFAULT NULL,
  `tech_center_fix` tinyint(1) DEFAULT NULL,
  `no_screen_shake` tinyint(1) DEFAULT NULL,
  `no_tesla_delay` tinyint(1) DEFAULT NULL,
  `dead_player_radar` tinyint(1) DEFAULT NULL,
  `capture_flag` tinyint(1) DEFAULT NULL,
  `slow_unit_build` tinyint(1) DEFAULT NULL,
  `shroud_regrows` tinyint(1) DEFAULT NULL,
  `ai_player_count` tinyint(1) NOT NULL DEFAULT 0,
  `team1_spawn_order` varchar(255) NOT NULL,
  `team2_spawn_order` varchar(255) NOT NULL,
  `aftermath` tinyint(1) DEFAULT NULL,
  `ore_regenerates` tinyint(1) DEFAULT NULL,
  `allowed_sides` varchar(255) NOT NULL,
  `admin_description` varchar(255) NOT NULL,
  `map_pool_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `qm_maps_map_id_index` (`map_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qm_maps_old`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qm_maps_old` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_id` int(11) NOT NULL,
  `map_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `bit_idx` int(11) NOT NULL,
  `valid` tinyint(1) NOT NULL,
  `spawn_affinity` varchar(255) NOT NULL,
  `speed` int(11) NOT NULL,
  `credits` int(11) NOT NULL,
  `bases` tinyint(1) NOT NULL,
  `units` int(11) NOT NULL,
  `tech` int(11) NOT NULL,
  `short_game` tinyint(1) NOT NULL,
  `fog` tinyint(1) NOT NULL,
  `redeploy` tinyint(1) NOT NULL,
  `crates` tinyint(1) NOT NULL,
  `multi_eng` tinyint(1) NOT NULL,
  `allies` tinyint(1) NOT NULL,
  `dog_kill` tinyint(1) NOT NULL,
  `bridges` tinyint(1) NOT NULL,
  `supers` tinyint(1) NOT NULL,
  `build_ally` tinyint(1) NOT NULL,
  `spawn_preview` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qm_match_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qm_match_players` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `waiting` tinyint(1) NOT NULL,
  `player_id` int(11) NOT NULL,
  `ladder_id` int(11) NOT NULL,
  `map_bitfield` int(11) NOT NULL,
  `chosen_side` int(11) NOT NULL,
  `actual_side` int(11) NOT NULL,
  `port` int(11) DEFAULT NULL,
  `color` int(11) DEFAULT NULL,
  `location` int(11) DEFAULT NULL,
  `qm_match_id` bigint(20) DEFAULT NULL,
  `tunnel_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ipv6_port` int(11) DEFAULT NULL,
  `lan_port` int(11) DEFAULT NULL,
  `ai_dat` tinyint(1) DEFAULT NULL,
  `ip_address_id` int(10) unsigned DEFAULT NULL,
  `ipv6_address_id` int(10) unsigned DEFAULT NULL,
  `lan_address_id` int(10) unsigned DEFAULT NULL,
  `version_id` int(10) unsigned DEFAULT NULL,
  `platform_id` int(10) unsigned DEFAULT NULL,
  `map_sides_id` int(10) unsigned DEFAULT NULL,
  `ddraw_id` int(10) unsigned DEFAULT NULL,
  `tier` int(11) NOT NULL,
  `clan_id` int(10) unsigned DEFAULT NULL,
  `is_observer` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `qm_match_players_qm_match_id_index` (`qm_match_id`),
  KEY `qm_match_players_clan_id_foreign` (`clan_id`),
  CONSTRAINT `qm_match_players_clan_id_foreign` FOREIGN KEY (`clan_id`) REFERENCES `clans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qm_match_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qm_match_states` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) unsigned NOT NULL,
  `qm_match_id` int(10) unsigned NOT NULL,
  `state_type_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qm_matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qm_matches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_id` int(11) NOT NULL,
  `qm_map_id` int(11) NOT NULL,
  `seed` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `game_id` int(10) unsigned DEFAULT NULL,
  `tier` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `qm_matches_qm_map_id_index` (`qm_map_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qm_queue_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qm_queue_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qm_match_player_id` int(10) unsigned NOT NULL,
  `ladder_history_id` int(10) unsigned NOT NULL,
  `rating` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `game_type` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qm_user_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qm_user_ids` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qm_user_id` varchar(255) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qm_user_ids_user_id_foreign` (`user_id`),
  CONSTRAINT `qm_user_ids_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sides` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ladder_id` int(11) NOT NULL,
  `local_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `spawn_option_strings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spawn_option_strings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `string` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `spawn_option_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spawn_option_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `spawn_option_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spawn_option_values` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qm_map_id` int(11) DEFAULT NULL,
  `spawn_option_id` int(11) NOT NULL,
  `value_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ladder_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `spawn_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spawn_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type_id` int(11) NOT NULL,
  `name_id` int(11) NOT NULL,
  `string1_id` int(11) NOT NULL,
  `string2_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `state_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `state_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stats2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats2` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `col` int(11) DEFAULT NULL,
  `cty` int(11) DEFAULT NULL,
  `crd` int(11) DEFAULT NULL,
  `hrv` int(11) DEFAULT NULL,
  `player_game_report_id` int(10) unsigned NOT NULL,
  KEY `stats2_id_index` (`id`),
  KEY `stats2_player_game_report_id_index` (`player_game_report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tunnels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tunnels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `countrycode` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `password` int(11) NOT NULL,
  `clients` int(11) NOT NULL,
  `maxclients` int(11) NOT NULL,
  `official` int(11) NOT NULL,
  `heartbeat` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `latitude` int(11) NOT NULL,
  `longitude` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_ratings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `rating` int(11) NOT NULL,
  `peak_rating` int(11) NOT NULL,
  `rated_games` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_ratings_user_id_index` (`user_id`),
  KEY `user_ratings_rating_index` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `enableAnonymous` tinyint(1) NOT NULL DEFAULT 0,
  `disabledPointFilter` tinyint(1) NOT NULL DEFAULT 0,
  `match_ai` tinyint(1) NOT NULL DEFAULT 1,
  `skip_score_screen` tinyint(1) NOT NULL DEFAULT 0,
  `match_any_map` tinyint(1) NOT NULL DEFAULT 0,
  `is_observer` tinyint(1) NOT NULL DEFAULT 0,
  `allow_observers` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `user_settings_user_id_foreign` (`user_id`),
  CONSTRAINT `user_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_tiers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `ladder_id` int(11) NOT NULL,
  `tier` int(11) NOT NULL,
  `both_tiers` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(60) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `group` enum('User','Moderator','Admin','God') NOT NULL DEFAULT 'User',
  `ip_address_id` int(11) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `avatar_path` varchar(255) DEFAULT NULL,
  `avatar_upload_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `discord_profile` varchar(255) DEFAULT NULL,
  `youtube_profile` varchar(255) DEFAULT NULL,
  `twitch_profile` varchar(255) DEFAULT NULL,
  `alias` varchar(255) NOT NULL,
  `chat_allowed` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `users_email_unique` (`email`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_02_04_183513_create_players_table',1);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_02_04_183529_create_ladders_table',2);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_02_04_184834_create_ladder_types_table',3);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_02_04_183523_create_games_table',4);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_02_04_183549_create_ladder_games_table',5);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_02_04_183557_create_ladder_history_table',6);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_02_04_183607_create_ladder_players_table',7);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_05_01_080800_create_games_raw_table',8);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_05_14_204749_create_player_points_table',9);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_06_03_160715_create_game_players_table',10);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_07_22_220817_create_player_ratings_table',11);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_07_23_023853_create_qm_maps_table',14);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_07_23_215433_create_qm_matches_table',15);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_07_23_220359_create_qm_match_players_table',16);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_07_25_230316_create_qm_ladder_rules_table',17);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_07_27_233514_create_sides_table',18);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_07_28_180036_edit_users_table',19);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_07_28_180037_create_ladder_history_table',20);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_08_11_202844_edit_games_table',21);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_08_11_203129_edit_ladder_games_table',21);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_08_11_203233_edit_player_points_table',21);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_80_20_180038_create_cards_table',22);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_80_20_180039_edit_player_table',22);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_08_28_223104_add_columns_qm_match_players',23);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_08_29_032500_remove_columns_qm_matches',23);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_09_02_000336_add_qm_columns',24);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_09_02_223213_SeedTSmaps',25);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_09_04_002646_correct_qm_ladder_rules_france_yuri',26);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_03_25_165908_create_tunnels_table',27);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_09_25_063257_add_columns_for_ra',27);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_09_25_104721_add_more_ra_columns',28);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_09_29_151830_create_client_version_table',29);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_09_29_164940_update_qm_match_players',29);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_10_04_023648_add_sdfx_to_games',30);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_10_05_041559_create_game_reports_table',31);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_10_05_050914_create_player_game_reports_table',31);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_10_05_053816_reformat_games_table',31);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_10_13_052207_add_mapsides_column_to_qm_player',32);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_03_215111_add_ai_dat_col_qm_match_players',33);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_08_003130_add_cols_to_qm_maps',34);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_09_204327_add_pings_to_game_reports',35);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_11_003314_add_bail_time_to_qm_rules',36);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_11_034842_add_tier2_qm_ladder',36);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_11_035830_add_tiers_to_player_rating',36);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_12_002451_change_game_reports_player_id_unsigned',37);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_12_063459_create_player_histories_table',38);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_12_095906_drop_tier_column_p_ratings',38);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_13_021807_add_admin_desc_qm_maps',39);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_13_175608_create_various_indexes',40);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_16_063435_create_countable_game_objects_table',41);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_16_072331_create_game_object_counts_table',42);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_17_011710_create_countable_object_heaps_table',42);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2017_11_17_232712_add_indexes_to_qm_cols',43);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2018_03_10_184423_create_player_caches_table',44);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2018_03_21_022214_create_bans_table',45);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2018_03_26_225823_create_ladder_admins_table',46);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2018_08_25_032614_add_ip_column',46);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2018_08_25_033011_create_ip_addresses_table',46);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2018_08_26_001039_create_qm_match_states_table',46);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2018_10_04_204402_add_game_cols_qm_game',47);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2018_10_15_022120_create_jobs_table',48);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2018_10_26_221657_create_qm_queue_entries_table',49);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2018_10_27_224007_add_filters_to_qm_rules',49);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2019_03_12_183214_remove_strings_qm_match_players',50);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2019_03_14_051349_drop_id_from_game_object_counts',50);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2019_03_15_205935_add_cty_column',50);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2019_03_16_223323_add_tier_to_player_caches',50);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2019_06_01_050818_add_verified_column_to_users',51);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2019_06_02_190859_create_email_verifications_table',51);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2019_06_12_214315_create_ip_address_histories_table',52);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2019_06_13_000917_add_city_country_columns',53);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2019_08_06_194816_create_player_active_handles',54);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_05_06_061838_add_frame_send_rate',55);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_05_06_222801_map_table_per_ladder',56);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_05_07_223132_add_option_for_elo',57);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_05_08_003944_add_elo_k_control',58);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_05_18_060611_create_player_cache_updates_table',59);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_05_30_185308_create_qm_connection_stats_table',60);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_06_28_010039_create_map_pools_table',61);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_06_30_194624_create_spawn_option_strings_table',61);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_06_30_194625_create_spawn_option_types_table',61);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_06_30_194632_create_spawn_options_table',62);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_06_30_201756_create_spawn_option_values_table',62);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_07_02_004248_delete_qm_maps_columns',62);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_07_14_102103_create_ladder_warnings_table',63);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_07_16_044508_create_player_warnings_table',63);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_07_19_013935_create_ladder_alert_players_table',64);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_07_29_024334_add_rejectectable_column',65);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_07_30_025733_remove_frame_send_rate_add_preview_map',66);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_08_09_213901_create_irc_associations_table',67);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_08_09_213913_create_irc_hostmasks_table',67);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_08_11_004633_create_irc_players_table',67);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_08_11_042648_create_clans_table',67);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_08_12_005822_create_clan_players_table',67);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_08_12_010437_create_clan_roles_table',67);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_08_13_232846_add_clans_allowed_to_ladder',67);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_08_13_234631_create_game_object_schemas_table',67);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_08_14_215448_create_object_schema_managers_table',67);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_08_15_232606_root_map_pools_at_ladder_id',67);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_08_16_024035_use_ladder_id_for_spawn_option_values',67);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_08_17_192643_allow_private_ladders',67);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_08_18_231540_create_clan_invitations_table',67);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2020_09_08_031939_add_refreshed_to_irc_associations',67);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2021_11_04_164515_seed_spawn_option_disable_sw_vs_yuri',68);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2021_11_09_174717_add_reduceMapRepeats_to_qm_ladder_rules',69);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2021_11_17_020040_add_cancel_count_player_histories',70);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2021_11_23_045941_add_old_pts_to_game_report',71);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_04_21_194132_addObjectManager',72);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_04_21_200824_createRA2Ladder',72);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_04_21_235437_CopyYrPool',73);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_04_21_235438_CopyYrPool',74);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_05_15_170713_privateYrLadder',75);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_05_15_171820_adminDevo',75);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_06_11_174646_createSFJLadder',76);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_07_23_013404_blitzLadder',77);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_09_16_004015_addOilCameo',78);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_08_29_0257565_create_user_settings_table',79);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_10_05_002314_createMissingUserSettings',80);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_10_09_104803_add_user_avatar_field',81);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_09_11_001224_createAchievementTables',82);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_09_12_233541_addCameo',83);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_10_29_003998_createAchievementTableTracker',84);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_10_31_234455_AddLadderRules',85);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_09_11_003908_createAchievementTableTracker',86);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_11_01_220002_AchievementsProgressNew',86);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_11_03_224557_CanceledMatches',87);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_11_05_171446_AchievmentsTypeDataFix',88);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_11_24_194012_CreateMapHeaderTable',89);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_11_24_194023_CreateMapWaypointTable',89);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_11_24_210045_SeedMapHeaders',89);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_11_27_144659_AddSpawnColumn',89);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_11_29_212016_add_order_index_to_ladders',89);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_12_28_101550_add_protocol_version_spawn_option',90);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_12_23_001224_createAchievementTables',91);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_12_23_003908_createAchievementTableTracker',91);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_12_28_143255_create_achievements_progress_table',92);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_12_28_001224_createAchievementTables',93);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_12_29_143255_create_achievements_progress_table',94);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_12_28_185653_AchievementMissingCameo',95);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_12_29_152526_LadderRulesPtFilterThreshold',96);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_12_29_173107_TrimMapNames',97);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2022_12_31_155928_GameAudit',98);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_01_02_165349_create_user_ratings_table',99);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_01_04_125616_QmMatchTier',100);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_01_04_235452_FixAchievmentType',101);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_01_05_105641_create_league_players_table',102);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_01_05_005961_UpdateGameAudit',103);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_01_10_223949_FixInfantryAchievements',104);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_01_15_170038_add_game_type_field',105);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_01_15_191529_add_qm_queue_entry_game_type_column',105);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_01_16_184258_add_is_bot_to_players_table',105);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_01_28_042157_AddRandomSpawnColumn',106);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_01_31_225449_AddAliasColumn',107);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_02_01_015331_PopulateMapSpawnCount',107);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_02_15_213144_add_new_spawn_ini_value',108);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_03_19_141953_FixAchievements',109);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_10_140138_add_match_ai_to_user_settings_table',110);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_10_143015_add_match_ai_seconds_column_to_qm_ladder_rules',110);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_11_184705_AddGiSpawnIniValue',111);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_29_162412_CreateRA2ClanLadder',112);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_20_122828_Migrate2v2MapstoRa2Cl',113);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_02_124639_clan_caches_table',114);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_02_125306_clan_caches_update',114);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_02_143616_seed_brutal_bot_with_clan',114);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_02_145054_add_clan_id_to_player_game_reports',114);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_02_145440_add_clan_id_to_game_reports',114);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_02_181805_add_indexes_to_clan_tables',114);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_08_135557_add_clan_id_to_qm_match_players_table',114);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_08_135558_add_clan_avatar_path_to_clans_table',114);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_17_002617_AddMaxActivePlayersRule',114);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_18_011046_AddClanExOwner',114);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_22_010937_AddMapPath',114);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_26_171645_add_qm_mode_spawn_option',114);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_26_204026_add_skip_score_screen_spawn_option',114);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_04_30_153904_AddSkipScoreScreenOption',114);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_05_26_161636_create_clan_ratings_table',115);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_05_27_144140_add_clan_bio',116);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_06_19_123243_AddQmMapDifficulty',117);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_07_01_163107_AddUserSettingAnyMap',118);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_07_09_175654_add_is_observer_column_to_qm_match_player',119);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_07_29_165952_add_observer_mode_to_user_settings',119);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_07_30_142448_user_tiers_table',120);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_07_31_171804_add_index_to_qm_connection_stats_table',121);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_08_02_125902_delete_duplicate_user_tiers',122);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_08_05_115544_create_news_table',123);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_08_25_184928_create_qm_user_ids_table',124);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_09_05_233802_AddQmMapWeight',125);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_09_16_003959_MapImageHash',126);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_09_16_143627_RA2NewMapsLadder',127);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_09_29_024121_UpdateColumnName',128);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_09_29_082222_change_type',128);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_09_31_034818_CreateMapTierTable',128);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_09_31_234511_PopulateMapTiers',128);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_09_31_236511_PopulateMapTiers',129);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_09_29_012222_change_type',130);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2023_11_03_225161_add_chat_allowed_colum_users_table',130);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2024_01_12_215824_AddBlitzSpawnOptions',131);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2024_01_14_012415_orepurifier',132);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2024_01_14_013141_orepurifierfactory',133);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2024_01_22_173754_orepurifierbaseplanningside',134);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2024_01_31_121549_grandcannon',135);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2024_02_06_213703_grandcannonrangenerf',136);
