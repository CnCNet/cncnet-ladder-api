-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Generation Time: Jun 28, 2022 at 07:25 PM
-- Server version: 8.0.29
-- PHP Version: 8.0.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cncnet_api`
--

-- --------------------------------------------------------

--
-- Table structure for table `bans`
--

CREATE TABLE `bans` (
  `id` int UNSIGNED NOT NULL,
  `admin_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `ban_type` int UNSIGNED NOT NULL,
  `internal_note` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `plubic_reason` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `expires` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip_address_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cards`
--

CREATE TABLE `cards` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `short` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clans`
--

CREATE TABLE `clans` (
  `id` int UNSIGNED NOT NULL,
  `ladder_id` int NOT NULL,
  `short` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clan_invitations`
--

CREATE TABLE `clan_invitations` (
  `id` int UNSIGNED NOT NULL,
  `clan_id` int NOT NULL,
  `author_id` int NOT NULL,
  `player_id` int NOT NULL,
  `type` enum('invited','cancelled','joined','kicked','left','promoted','demoted') CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clan_players`
--

CREATE TABLE `clan_players` (
  `id` int UNSIGNED NOT NULL,
  `clan_id` int NOT NULL,
  `player_id` int NOT NULL,
  `clan_role_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clan_roles`
--

CREATE TABLE `clan_roles` (
  `id` int UNSIGNED NOT NULL,
  `value` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_version`
--

CREATE TABLE `client_version` (
  `version` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `link` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `format` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `platform` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `countable_game_objects`
--

CREATE TABLE `countable_game_objects` (
  `id` int UNSIGNED NOT NULL,
  `heap_name` char(3) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `heap_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `cameo` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `cost` int NOT NULL,
  `value` int NOT NULL,
  `ui_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `game_object_schema_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `countable_object_heaps`
--

CREATE TABLE `countable_object_heaps` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int UNSIGNED NOT NULL,
  `ladder_history_id` int UNSIGNED NOT NULL,
  `wol_game_id` int UNSIGNED NOT NULL,
  `bamr` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `crat` int NOT NULL,
  `cred` longtext CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `shrt` int NOT NULL,
  `supr` int NOT NULL,
  `unit` int NOT NULL,
  `plrs` int NOT NULL,
  `scen` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci DEFAULT NULL,
  `hash` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci DEFAULT NULL,
  `game_report_id` int UNSIGNED DEFAULT NULL,
  `qm_match_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `games_backup`
--

CREATE TABLE `games_backup` (
  `id` int UNSIGNED NOT NULL,
  `ladder_history_id` int UNSIGNED NOT NULL,
  `wol_game_id` int UNSIGNED NOT NULL,
  `afps` int NOT NULL,
  `oosy` tinyint(1) NOT NULL,
  `bamr` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `crat` int NOT NULL,
  `dura` longtext CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `cred` longtext CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `shrt` int NOT NULL,
  `supr` int NOT NULL,
  `unit` int NOT NULL,
  `plrs` int NOT NULL,
  `scen` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `hash` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `sdfx` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `games_raw`
--

CREATE TABLE `games_raw` (
  `id` int UNSIGNED NOT NULL,
  `hash` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `packet` longtext CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `game_id` int UNSIGNED NOT NULL,
  `ladder_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `game_object_counts`
--

CREATE TABLE `game_object_counts` (
  `stats_id` int UNSIGNED NOT NULL,
  `countable_game_objects_id` int UNSIGNED NOT NULL,
  `count` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_object_schemas`
--

CREATE TABLE `game_object_schemas` (
  `id` int UNSIGNED NOT NULL,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_reports`
--

CREATE TABLE `game_reports` (
  `id` int UNSIGNED NOT NULL,
  `game_id` int UNSIGNED NOT NULL,
  `player_id` int UNSIGNED NOT NULL,
  `best_report` tinyint(1) NOT NULL,
  `manual_report` tinyint(1) NOT NULL,
  `duration` int NOT NULL,
  `valid` tinyint(1) NOT NULL,
  `finished` tinyint(1) NOT NULL,
  `fps` int NOT NULL,
  `oos` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `pings_sent` int NOT NULL DEFAULT '0',
  `pings_received` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_stats`
--

CREATE TABLE `game_stats` (
  `id` int NOT NULL,
  `game_id` int UNSIGNED NOT NULL COMMENT 'Game Id',
  `player_id` int UNSIGNED NOT NULL,
  `stats_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `ip_addresses`
--

CREATE TABLE `ip_addresses` (
  `id` int UNSIGNED NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `city` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `country` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ip_address_histories`
--

CREATE TABLE `ip_address_histories` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int NOT NULL,
  `ip_address_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `irc_associations`
--

CREATE TABLE `irc_associations` (
  `id` int UNSIGNED NOT NULL,
  `irc_hostmask_id` int NOT NULL,
  `user_id` int NOT NULL,
  `ladder_id` int NOT NULL,
  `player_id` int NOT NULL,
  `clan_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `refreshed_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `irc_hostmasks`
--

CREATE TABLE `irc_hostmasks` (
  `id` int UNSIGNED NOT NULL,
  `value` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `irc_players`
--

CREATE TABLE `irc_players` (
  `id` int UNSIGNED NOT NULL,
  `player_id` int NOT NULL,
  `ladder_id` int NOT NULL,
  `username` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `payload` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ladders`
--

CREATE TABLE `ladders` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `abbreviation` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `game` enum('ra','ts','yr') CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `clans_allowed` tinyint(1) NOT NULL DEFAULT '0',
  `game_object_schema_id` int NOT NULL,
  `map_pool_id` int DEFAULT NULL,
  `private` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `ladder_admins`
--

CREATE TABLE `ladder_admins` (
  `id` int UNSIGNED NOT NULL,
  `ladder_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `admin` tinyint(1) NOT NULL,
  `moderator` tinyint(1) NOT NULL,
  `tester` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ladder_alerts`
--

CREATE TABLE `ladder_alerts` (
  `id` int UNSIGNED NOT NULL,
  `ladder_id` int NOT NULL,
  `message` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ladder_alert_players`
--

CREATE TABLE `ladder_alert_players` (
  `id` int UNSIGNED NOT NULL,
  `player_id` int NOT NULL,
  `ladder_alert_id` int NOT NULL,
  `show` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ladder_games`
--

CREATE TABLE `ladder_games` (
  `id` int UNSIGNED NOT NULL,
  `game_id` int UNSIGNED NOT NULL,
  `ladder_history_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `ladder_history`
--

CREATE TABLE `ladder_history` (
  `id` int UNSIGNED NOT NULL,
  `ladder_id` int NOT NULL,
  `starts` datetime NOT NULL,
  `ends` datetime NOT NULL,
  `short` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ladder_types`
--

CREATE TABLE `ladder_types` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('player','clan') CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `match` enum('1vs1','2vs2','3vs3','4vs4') CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maps`
--

CREATE TABLE `maps` (
  `id` int NOT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `ladder_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `maps_backup`
--

CREATE TABLE `maps_backup` (
  `id` int NOT NULL,
  `hash` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `map_pools`
--

CREATE TABLE `map_pools` (
  `id` int UNSIGNED NOT NULL,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ladder_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `map_side_strings`
--

CREATE TABLE `map_side_strings` (
  `id` int UNSIGNED NOT NULL,
  `value` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `migration` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `object_schema_managers`
--

CREATE TABLE `object_schema_managers` (
  `id` int UNSIGNED NOT NULL,
  `game_object_schema_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `ladder_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `card_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `player_active_handles`
--

CREATE TABLE `player_active_handles` (
  `id` int UNSIGNED NOT NULL,
  `ladder_id` int NOT NULL,
  `player_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_alerts`
--

CREATE TABLE `player_alerts` (
  `id` int UNSIGNED NOT NULL,
  `player_id` int NOT NULL,
  `message` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `seen_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_caches`
--

CREATE TABLE `player_caches` (
  `id` int UNSIGNED NOT NULL,
  `ladder_history_id` int UNSIGNED NOT NULL,
  `player_id` int UNSIGNED NOT NULL,
  `player_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `card` int DEFAULT NULL,
  `points` int NOT NULL,
  `wins` int NOT NULL,
  `games` int NOT NULL,
  `percentile` int NOT NULL,
  `side` int DEFAULT NULL,
  `fps` int NOT NULL,
  `country` int DEFAULT NULL,
  `tier` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_cache_updates`
--

CREATE TABLE `player_cache_updates` (
  `id` int UNSIGNED NOT NULL,
  `player_cache_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_data_strings`
--

CREATE TABLE `player_data_strings` (
  `id` int UNSIGNED NOT NULL,
  `value` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_games`
--

CREATE TABLE `player_games` (
  `id` int UNSIGNED NOT NULL,
  `game_id` int UNSIGNED NOT NULL,
  `player_id` int UNSIGNED NOT NULL,
  `opponent_id` int DEFAULT NULL,
  `result` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `player_game_reports`
--

CREATE TABLE `player_game_reports` (
  `id` int UNSIGNED NOT NULL,
  `game_id` int UNSIGNED NOT NULL,
  `game_report_id` int UNSIGNED NOT NULL,
  `player_id` int UNSIGNED NOT NULL,
  `local_id` int NOT NULL,
  `local_team_id` int NOT NULL,
  `points` int NOT NULL DEFAULT '0',
  `stats_id` int DEFAULT NULL,
  `disconnected` tinyint(1) NOT NULL DEFAULT '0',
  `no_completion` tinyint(1) NOT NULL DEFAULT '0',
  `quit` tinyint(1) NOT NULL DEFAULT '0',
  `won` tinyint(1) NOT NULL DEFAULT '0',
  `defeated` tinyint(1) NOT NULL DEFAULT '0',
  `draw` tinyint(1) NOT NULL DEFAULT '0',
  `spectator` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `backupPts` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_histories`
--

CREATE TABLE `player_histories` (
  `id` int UNSIGNED NOT NULL,
  `player_id` int UNSIGNED NOT NULL,
  `ladder_history_id` int UNSIGNED NOT NULL,
  `tier` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `cancels` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_points`
--

CREATE TABLE `player_points` (
  `id` int UNSIGNED NOT NULL,
  `points_awarded` int NOT NULL,
  `game_won` tinyint(1) NOT NULL,
  `game_id` int UNSIGNED NOT NULL,
  `player_id` int UNSIGNED NOT NULL,
  `ladder_history_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `player_ratings`
--

CREATE TABLE `player_ratings` (
  `id` int UNSIGNED NOT NULL,
  `player_id` int NOT NULL,
  `rating` int NOT NULL,
  `peak_rating` int NOT NULL,
  `rated_games` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qm_connection_stats`
--

CREATE TABLE `qm_connection_stats` (
  `id` int UNSIGNED NOT NULL,
  `qm_match_id` int NOT NULL,
  `player_id` int NOT NULL,
  `peer_id` int NOT NULL,
  `ip_address_id` int NOT NULL,
  `port` int NOT NULL,
  `rtt` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qm_ladder_rules`
--

CREATE TABLE `qm_ladder_rules` (
  `id` int UNSIGNED NOT NULL,
  `ladder_id` int NOT NULL,
  `player_count` int NOT NULL,
  `map_vetoes` int NOT NULL,
  `max_difference` int NOT NULL,
  `all_sides` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `allowed_sides` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `bail_time` int NOT NULL DEFAULT '60',
  `bail_fps` int NOT NULL DEFAULT '30',
  `tier2_rating` int NOT NULL DEFAULT '0',
  `rating_per_second` double NOT NULL,
  `max_points_difference` int NOT NULL,
  `points_per_second` double NOT NULL,
  `use_elo_points` tinyint(1) NOT NULL DEFAULT '1',
  `wol_k` int NOT NULL DEFAULT '64',
  `show_map_preview` tinyint(1) NOT NULL DEFAULT '1',
  `reduce_map_repeats` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qm_maps`
--

CREATE TABLE `qm_maps` (
  `id` int UNSIGNED NOT NULL,
  `ladder_id` int NOT NULL,
  `map_id` int NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `bit_idx` int NOT NULL,
  `valid` tinyint(1) NOT NULL,
  `spawn_order` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `team1_spawn_order` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `team2_spawn_order` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `allowed_sides` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `admin_description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `map_pool_id` int NOT NULL,
  `rejectable` tinyint(1) NOT NULL DEFAULT '1',
  `default_reject` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qm_maps_backup`
--

CREATE TABLE `qm_maps_backup` (
  `id` int UNSIGNED NOT NULL,
  `ladder_id` int NOT NULL,
  `map_id` int NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `bit_idx` int NOT NULL,
  `valid` tinyint(1) NOT NULL,
  `spawn_order` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `speed` int NOT NULL,
  `credits` int NOT NULL,
  `bases` tinyint(1) NOT NULL,
  `units` int NOT NULL,
  `tech` int DEFAULT NULL,
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
  `game_mode` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci DEFAULT NULL,
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
  `ai_player_count` tinyint(1) NOT NULL DEFAULT '0',
  `team1_spawn_order` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `team2_spawn_order` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `aftermath` tinyint(1) DEFAULT NULL,
  `ore_regenerates` tinyint(1) DEFAULT NULL,
  `allowed_sides` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `admin_description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `map_pool_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qm_maps_old`
--

CREATE TABLE `qm_maps_old` (
  `id` int UNSIGNED NOT NULL,
  `ladder_id` int NOT NULL,
  `map_id` int NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `bit_idx` int NOT NULL,
  `valid` tinyint(1) NOT NULL,
  `spawn_affinity` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `speed` int NOT NULL,
  `credits` int NOT NULL,
  `bases` tinyint(1) NOT NULL,
  `units` int NOT NULL,
  `tech` int NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qm_matches`
--

CREATE TABLE `qm_matches` (
  `id` bigint UNSIGNED NOT NULL,
  `ladder_id` int NOT NULL,
  `qm_map_id` int NOT NULL,
  `seed` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `game_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qm_match_players`
--

CREATE TABLE `qm_match_players` (
  `id` bigint UNSIGNED NOT NULL,
  `waiting` tinyint(1) NOT NULL,
  `player_id` int NOT NULL,
  `ladder_id` int NOT NULL,
  `map_bitfield` int NOT NULL,
  `chosen_side` int NOT NULL,
  `actual_side` int NOT NULL,
  `port` int DEFAULT NULL,
  `color` int DEFAULT NULL,
  `location` int DEFAULT NULL,
  `qm_match_id` bigint DEFAULT NULL,
  `tunnel_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ipv6_port` int DEFAULT NULL,
  `lan_port` int DEFAULT NULL,
  `ai_dat` tinyint(1) DEFAULT NULL,
  `ip_address_id` int UNSIGNED DEFAULT NULL,
  `ipv6_address_id` int UNSIGNED DEFAULT NULL,
  `lan_address_id` int UNSIGNED DEFAULT NULL,
  `version_id` int UNSIGNED DEFAULT NULL,
  `platform_id` int UNSIGNED DEFAULT NULL,
  `map_sides_id` int UNSIGNED DEFAULT NULL,
  `ddraw_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qm_match_states`
--

CREATE TABLE `qm_match_states` (
  `id` int UNSIGNED NOT NULL,
  `player_id` int UNSIGNED NOT NULL,
  `qm_match_id` int UNSIGNED NOT NULL,
  `state_type_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qm_queue_entries`
--

CREATE TABLE `qm_queue_entries` (
  `id` int UNSIGNED NOT NULL,
  `qm_match_player_id` int UNSIGNED NOT NULL,
  `ladder_history_id` int UNSIGNED NOT NULL,
  `rating` int NOT NULL,
  `points` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sides`
--

CREATE TABLE `sides` (
  `id` int UNSIGNED NOT NULL,
  `ladder_id` int NOT NULL,
  `local_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spawn_options`
--

CREATE TABLE `spawn_options` (
  `id` int UNSIGNED NOT NULL,
  `type_id` int NOT NULL,
  `name_id` int NOT NULL,
  `string1_id` int NOT NULL,
  `string2_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spawn_option_strings`
--

CREATE TABLE `spawn_option_strings` (
  `id` int UNSIGNED NOT NULL,
  `string` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spawn_option_types`
--

CREATE TABLE `spawn_option_types` (
  `id` int UNSIGNED NOT NULL,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spawn_option_values`
--

CREATE TABLE `spawn_option_values` (
  `id` int UNSIGNED NOT NULL,
  `qm_map_id` int DEFAULT NULL,
  `spawn_option_id` int NOT NULL,
  `value_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ladder_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `state_types`
--

CREATE TABLE `state_types` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stats2`
--

CREATE TABLE `stats2` (
  `id` int UNSIGNED NOT NULL,
  `sid` int DEFAULT NULL,
  `col` int DEFAULT NULL,
  `cty` int DEFAULT NULL,
  `crd` int DEFAULT NULL,
  `hrv` int DEFAULT NULL,
  `player_game_report_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tunnels`
--

CREATE TABLE `tunnels` (
  `id` int UNSIGNED NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `country` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `countrycode` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `password` int NOT NULL,
  `clients` int NOT NULL,
  `maxclients` int NOT NULL,
  `official` int NOT NULL,
  `heartbeat` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `latitude` int NOT NULL,
  `longitude` int NOT NULL,
  `version` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `group` enum('User','Moderator','Admin','God') CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'User',
  `ip_address_id` int DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT '0',
  `email_verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bans`
--
ALTER TABLE `bans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cards`
--
ALTER TABLE `cards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clans`
--
ALTER TABLE `clans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clan_invitations`
--
ALTER TABLE `clan_invitations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clan_players`
--
ALTER TABLE `clan_players`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clan_roles`
--
ALTER TABLE `clan_roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `countable_game_objects`
--
ALTER TABLE `countable_game_objects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `countable_game_objects_heap_name_name_index` (`heap_name`,`name`),
  ADD KEY `countable_game_objects_heap_id_index` (`heap_id`);

--
-- Indexes for table `countable_object_heaps`
--
ALTER TABLE `countable_object_heaps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `games_game_report_id_index` (`game_report_id`);

--
-- Indexes for table `games_backup`
--
ALTER TABLE `games_backup`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `games_raw`
--
ALTER TABLE `games_raw`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `game_object_counts`
--
ALTER TABLE `game_object_counts`
  ADD KEY `game_object_counts_stats_id_index` (`stats_id`);

--
-- Indexes for table `game_object_schemas`
--
ALTER TABLE `game_object_schemas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `game_reports`
--
ALTER TABLE `game_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_reports_game_id_index` (`game_id`);

--
-- Indexes for table `game_stats`
--
ALTER TABLE `game_stats`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `id` (`id`) USING BTREE,
  ADD KEY `gid` (`game_id`) USING BTREE;

--
-- Indexes for table `ip_addresses`
--
ALTER TABLE `ip_addresses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ip_address_histories`
--
ALTER TABLE `ip_address_histories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `irc_associations`
--
ALTER TABLE `irc_associations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `irc_hostmasks`
--
ALTER TABLE `irc_hostmasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `irc_players`
--
ALTER TABLE `irc_players`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ladders`
--
ALTER TABLE `ladders`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `ladder_admins`
--
ALTER TABLE `ladder_admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ladder_alerts`
--
ALTER TABLE `ladder_alerts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ladder_alert_players`
--
ALTER TABLE `ladder_alert_players`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ladder_games`
--
ALTER TABLE `ladder_games`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `ladder_history`
--
ALTER TABLE `ladder_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ladder_types`
--
ALTER TABLE `ladder_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maps`
--
ALTER TABLE `maps`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `map_pools`
--
ALTER TABLE `map_pools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `map_side_strings`
--
ALTER TABLE `map_side_strings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `object_schema_managers`
--
ALTER TABLE `object_schema_managers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`) USING BTREE,
  ADD KEY `password_resets_token_index` (`token`) USING BTREE;

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `player_active_handles`
--
ALTER TABLE `player_active_handles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `player_alerts`
--
ALTER TABLE `player_alerts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `player_caches`
--
ALTER TABLE `player_caches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_caches_ladder_history_id_player_id_index` (`ladder_history_id`,`player_id`),
  ADD KEY `player_caches_ladder_history_id_points_index` (`ladder_history_id`,`points`);

--
-- Indexes for table `player_cache_updates`
--
ALTER TABLE `player_cache_updates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `player_data_strings`
--
ALTER TABLE `player_data_strings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `player_games`
--
ALTER TABLE `player_games`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `player_game_reports`
--
ALTER TABLE `player_game_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_game_reports_player_id_index` (`player_id`),
  ADD KEY `player_game_reports_game_report_id_index` (`game_report_id`);

--
-- Indexes for table `player_histories`
--
ALTER TABLE `player_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_histories_player_id_index` (`player_id`);

--
-- Indexes for table `player_points`
--
ALTER TABLE `player_points`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `player_ratings`
--
ALTER TABLE `player_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_ratings_player_id_index` (`player_id`),
  ADD KEY `player_ratings_rating_index` (`rating`);

--
-- Indexes for table `qm_connection_stats`
--
ALTER TABLE `qm_connection_stats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `qm_ladder_rules`
--
ALTER TABLE `qm_ladder_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `qm_maps`
--
ALTER TABLE `qm_maps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qm_maps_map_id_index` (`map_id`);

--
-- Indexes for table `qm_maps_backup`
--
ALTER TABLE `qm_maps_backup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qm_maps_map_id_index` (`map_id`);

--
-- Indexes for table `qm_maps_old`
--
ALTER TABLE `qm_maps_old`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `qm_matches`
--
ALTER TABLE `qm_matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qm_matches_qm_map_id_index` (`qm_map_id`);

--
-- Indexes for table `qm_match_players`
--
ALTER TABLE `qm_match_players`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qm_match_players_qm_match_id_index` (`qm_match_id`);

--
-- Indexes for table `qm_match_states`
--
ALTER TABLE `qm_match_states`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `qm_queue_entries`
--
ALTER TABLE `qm_queue_entries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sides`
--
ALTER TABLE `sides`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `spawn_options`
--
ALTER TABLE `spawn_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `spawn_option_strings`
--
ALTER TABLE `spawn_option_strings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `spawn_option_types`
--
ALTER TABLE `spawn_option_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `spawn_option_values`
--
ALTER TABLE `spawn_option_values`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `state_types`
--
ALTER TABLE `state_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stats2`
--
ALTER TABLE `stats2`
  ADD KEY `stats2_id_index` (`id`),
  ADD KEY `stats2_player_game_report_id_index` (`player_game_report_id`);

--
-- Indexes for table `tunnels`
--
ALTER TABLE `tunnels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `users_email_unique` (`email`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bans`
--
ALTER TABLE `bans`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cards`
--
ALTER TABLE `cards`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clans`
--
ALTER TABLE `clans`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clan_invitations`
--
ALTER TABLE `clan_invitations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clan_players`
--
ALTER TABLE `clan_players`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clan_roles`
--
ALTER TABLE `clan_roles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `countable_game_objects`
--
ALTER TABLE `countable_game_objects`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `countable_object_heaps`
--
ALTER TABLE `countable_object_heaps`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `games_backup`
--
ALTER TABLE `games_backup`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `games_raw`
--
ALTER TABLE `games_raw`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `game_object_schemas`
--
ALTER TABLE `game_object_schemas`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `game_reports`
--
ALTER TABLE `game_reports`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `game_stats`
--
ALTER TABLE `game_stats`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ip_addresses`
--
ALTER TABLE `ip_addresses`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ip_address_histories`
--
ALTER TABLE `ip_address_histories`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `irc_associations`
--
ALTER TABLE `irc_associations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `irc_hostmasks`
--
ALTER TABLE `irc_hostmasks`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `irc_players`
--
ALTER TABLE `irc_players`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ladders`
--
ALTER TABLE `ladders`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ladder_admins`
--
ALTER TABLE `ladder_admins`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ladder_alerts`
--
ALTER TABLE `ladder_alerts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ladder_alert_players`
--
ALTER TABLE `ladder_alert_players`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ladder_games`
--
ALTER TABLE `ladder_games`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ladder_history`
--
ALTER TABLE `ladder_history`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ladder_types`
--
ALTER TABLE `ladder_types`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maps`
--
ALTER TABLE `maps`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `map_pools`
--
ALTER TABLE `map_pools`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `map_side_strings`
--
ALTER TABLE `map_side_strings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `object_schema_managers`
--
ALTER TABLE `object_schema_managers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_active_handles`
--
ALTER TABLE `player_active_handles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_alerts`
--
ALTER TABLE `player_alerts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_caches`
--
ALTER TABLE `player_caches`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_cache_updates`
--
ALTER TABLE `player_cache_updates`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_data_strings`
--
ALTER TABLE `player_data_strings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_games`
--
ALTER TABLE `player_games`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_game_reports`
--
ALTER TABLE `player_game_reports`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_histories`
--
ALTER TABLE `player_histories`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_points`
--
ALTER TABLE `player_points`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_ratings`
--
ALTER TABLE `player_ratings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qm_connection_stats`
--
ALTER TABLE `qm_connection_stats`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qm_ladder_rules`
--
ALTER TABLE `qm_ladder_rules`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qm_maps`
--
ALTER TABLE `qm_maps`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qm_maps_backup`
--
ALTER TABLE `qm_maps_backup`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qm_maps_old`
--
ALTER TABLE `qm_maps_old`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qm_matches`
--
ALTER TABLE `qm_matches`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qm_match_players`
--
ALTER TABLE `qm_match_players`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qm_match_states`
--
ALTER TABLE `qm_match_states`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qm_queue_entries`
--
ALTER TABLE `qm_queue_entries`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sides`
--
ALTER TABLE `sides`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spawn_options`
--
ALTER TABLE `spawn_options`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spawn_option_strings`
--
ALTER TABLE `spawn_option_strings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spawn_option_types`
--
ALTER TABLE `spawn_option_types`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spawn_option_values`
--
ALTER TABLE `spawn_option_values`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `state_types`
--
ALTER TABLE `state_types`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stats2`
--
ALTER TABLE `stats2`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tunnels`
--
ALTER TABLE `tunnels`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
