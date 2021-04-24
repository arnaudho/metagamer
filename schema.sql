-- Hôte : 127.0.0.1:3308
-- Version du serveur :  8.0.18
-- Version de PHP :  7.3.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Base de données :  `metagamer`
--
CREATE DATABASE IF NOT EXISTS `metagamer` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `metagamer`;

-- --------------------------------------------------------

--
-- Structure de la table `archetypes`
--

DROP TABLE IF EXISTS `archetypes`;
CREATE TABLE IF NOT EXISTS `archetypes` (
  `id_archetype` int(11) NOT NULL AUTO_INCREMENT,
  `name_archetype` varchar(63) NOT NULL,
  `image_archetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_archetype`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
ALTER TABLE `archetypes` ADD `id_type_format` INT(1) NOT NULL AFTER `name_archetype`;

-- --------------------------------------------------------

--
-- Structure de la table `cards`
--

DROP TABLE IF EXISTS `cards`;
CREATE TABLE IF NOT EXISTS `cards` (
  `id_card` int(11) NOT NULL AUTO_INCREMENT,
  `name_card` varchar(63) NOT NULL,
  `mana_cost_card` varchar(63) DEFAULT NULL,
  `cmc_card` int(11) DEFAULT NULL,
  `type_card` varchar(63) DEFAULT NULL,
  `color_card` varchar(7) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `set_card` varchar(7) DEFAULT NULL,
  `image_card` varchar(255) DEFAULT NULL,
  `produced_mana_card` varchar(7) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id_card`),
  UNIQUE KEY `IX_name_card` (`name_card`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `formats`
--

DROP TABLE IF EXISTS `formats`;
CREATE TABLE IF NOT EXISTS `formats` (
  `id_format` int(11) NOT NULL AUTO_INCREMENT,
  `name_format` varchar(255) NOT NULL,
  PRIMARY KEY (`id_format`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `formats` ADD `id_type_format` INT(1) NOT NULL AFTER `name_format`;

-- --------------------------------------------------------

--
-- Structure de la table `main_users`
--

DROP TABLE IF EXISTS `main_users`;
CREATE TABLE IF NOT EXISTS `main_users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `login_user` varchar(63) NOT NULL,
  `password_user` varchar(63) NOT NULL,
  `permissions_user` int(11) NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `matches`
--

DROP TABLE IF EXISTS `matches`;
CREATE TABLE IF NOT EXISTS `matches` (
  `id_player` int(11) NOT NULL,
  `opponent_id_player` int(11) NOT NULL,
  `round_number` int(4) NOT NULL,
  `result_match` tinyint(1) NOT NULL,
  PRIMARY KEY (`id_player`,`opponent_id_player`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
ALTER TABLE `matches` ADD `score_match` VARCHAR(5) AFTER `result_match`;

-- --------------------------------------------------------

--
-- Structure de la table `people`
--

DROP TABLE IF EXISTS `people`;
CREATE TABLE IF NOT EXISTS `people` (
  `id_people` int(11) NOT NULL AUTO_INCREMENT,
  `arena_id` varchar(63) NOT NULL,
  `discord_id` varchar(63) NOT NULL,
  PRIMARY KEY (`id_people`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `players`
--

DROP TABLE IF EXISTS `players`;
CREATE TABLE IF NOT EXISTS `players` (
  `id_player` int(11) NOT NULL AUTO_INCREMENT,
  `id_people` int(11) NOT NULL,
  `id_tournament` int(11) NOT NULL,
  `id_archetype` int(11) DEFAULT NULL,
  `name_deck` varchar(63) NOT NULL DEFAULT '',
  `decklist_player` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_player`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `player_card`
--

DROP TABLE IF EXISTS `player_card`;
CREATE TABLE IF NOT EXISTS `player_card` (
  `id_player` int(11) NOT NULL,
  `id_card` int(11) NOT NULL,
  `count_main` tinyint(4) NOT NULL DEFAULT '0',
  `count_side` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_player`,`id_card`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `player_tag`
--

DROP TABLE IF EXISTS `player_tag`;
CREATE TABLE IF NOT EXISTS `player_tag` (
  `id_people` int(11) NOT NULL,
  `tag_player` varchar(63) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id_people`,`tag_player`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `tournaments`
--

DROP TABLE IF EXISTS `tournaments`;
CREATE TABLE IF NOT EXISTS `tournaments` (
  `id_tournament` int(11) NOT NULL AUTO_INCREMENT,
  `name_tournament` varchar(255) NOT NULL,
  `date_tournament` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_format` int(11) NOT NULL,
  PRIMARY KEY (`id_tournament`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `sets`
--

DROP TABLE IF EXISTS `sets`;
CREATE TABLE IF NOT EXISTS `sets` (
  `id_set` int(11) NOT NULL AUTO_INCREMENT,
  `name_set` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `code_set` varchar(7) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `type_set` VARCHAR(31) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `released_date_set` date NOT NULL,
  PRIMARY KEY (`id_set`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Structure de la table `type_format`
--

DROP TABLE IF EXISTS `type_format`;
CREATE TABLE IF NOT EXISTS `type_format` (
  `id_type_format` int(1) NOT NULL AUTO_INCREMENT,
  `name_type_format` varchar(63) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id_type_format`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `type_format`
--

INSERT INTO `type_format` (`id_type_format`, `name_type_format`) VALUES
  (1, 'Standard'),
  (2, 'Historic'),
  (3, 'Limited');

COMMIT;
