-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- ホスト: localhost:3306
-- 生成日時: 2026-10-22 02:54:19
-- サーバのバージョン： 5.7.24
-- PHP のバージョン: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `free_address`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `people`
--

CREATE TABLE `people` (
  `people_id` int(11) NOT NULL,
  `people_num` varchar(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `mail` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `seat`
--

CREATE TABLE `seat` (
  `seat_id` int(11) NOT NULL,
  `floor` varchar(2) NOT NULL,
  `seat_num` varchar(11) NOT NULL,
  `seat_x` int(11) NOT NULL,
  `seat_y` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `sit`
--

CREATE TABLE `sit` (
  `sit_id` int(11) NOT NULL,
  `seat_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `history_num` varchar(11) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `people`
--
ALTER TABLE `people`
  ADD PRIMARY KEY (`people_id`);

--
-- テーブルのインデックス `seat`
--
ALTER TABLE `seat`
  ADD PRIMARY KEY (`seat_id`);

--
-- テーブルのインデックス `sit`
--
ALTER TABLE `sit`
  ADD PRIMARY KEY (`sit_id`),
  ADD KEY `people_id` (`people_id`),
  ADD KEY `seat_id` (`seat_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `people`
--
ALTER TABLE `people`
  MODIFY `people_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `seat`
--
ALTER TABLE `seat`
  MODIFY `seat_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `sit`
--
ALTER TABLE `sit`
  MODIFY `sit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `sit`
--
ALTER TABLE `sit`
  ADD CONSTRAINT `sit_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`people_id`),
  ADD CONSTRAINT `sit_ibfk_2` FOREIGN KEY (`seat_id`) REFERENCES `seat` (`seat_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
