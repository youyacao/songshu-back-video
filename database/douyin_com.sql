/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50644
Source Host           : localhost:3306
Source Database       : douyin_com

Target Server Type    : MYSQL
Target Server Version : 50644
File Encoding         : 65001

Date: 2019-09-30 15:10:42
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for tp_admin
-- ----------------------------
DROP TABLE IF EXISTS `tp_admin`;
CREATE TABLE `tp_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `create_time` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_admin
-- ----------------------------

-- ----------------------------
-- Table structure for tp_collection
-- ----------------------------
DROP TABLE IF EXISTS `tp_collection`;
CREATE TABLE `tp_collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `vid` int(11) DEFAULT NULL,
  `v_img` varchar(255) DEFAULT NULL,
  `create_time` varchar(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_collection
-- ----------------------------

-- ----------------------------
-- Table structure for tp_comment
-- ----------------------------
DROP TABLE IF EXISTS `tp_comment`;
CREATE TABLE `tp_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL COMMENT '用户ID',
  `content` varchar(255) DEFAULT NULL COMMENT '评论内容',
  `type` varchar(255) DEFAULT NULL,
  `vid` int(11) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `pid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_comment
-- ----------------------------

-- ----------------------------
-- Table structure for tp_skr
-- ----------------------------
DROP TABLE IF EXISTS `tp_skr`;
CREATE TABLE `tp_skr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `vid` int(11) DEFAULT NULL,
  `skr` int(11) DEFAULT NULL,
  `create_time` varchar(255) DEFAULT NULL,
  `update_time` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_uid` (`uid`) USING BTREE,
  UNIQUE KEY `index_vid` (`vid`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_skr
-- ----------------------------

-- ----------------------------
-- Table structure for tp_type
-- ----------------------------
DROP TABLE IF EXISTS `tp_type`;
CREATE TABLE `tp_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `sort_id` int(11) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `level` int(11) DEFAULT '1',
  `pid` int(11) DEFAULT NULL,
  `enable` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_type
-- ----------------------------
INSERT INTO `tp_type` VALUES ('3', '主分类1', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:56:14', '1', '1', '1');
INSERT INTO `tp_type` VALUES ('4', '主分类2', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:57:53', '1', '1', '1');
INSERT INTO `tp_type` VALUES ('5', '主分类3', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:57:55', '1', '1', '1');
INSERT INTO `tp_type` VALUES ('6', '主分类4', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:57:57', '1', '1', '1');
INSERT INTO `tp_type` VALUES ('127', '子分类1', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:58:22', '2', '3', '1');
INSERT INTO `tp_type` VALUES ('128', '子分类2', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:58:25', '2', '3', '1');
INSERT INTO `tp_type` VALUES ('129', '子分类3', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:58:28', '2', '3', '1');
INSERT INTO `tp_type` VALUES ('130', '子分类4', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:58:30', '2', '3', '1');
INSERT INTO `tp_type` VALUES ('131', '子分类5', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:58:34', '2', '3', '1');
INSERT INTO `tp_type` VALUES ('132', '子分类1', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:58:39', '2', '4', '1');
INSERT INTO `tp_type` VALUES ('133', '子分类2', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:58:41', '2', '4', '1');
INSERT INTO `tp_type` VALUES ('134', '子分类3', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:58:44', '2', '4', '1');
INSERT INTO `tp_type` VALUES ('135', '子分类4', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:58:47', '2', '4', '1');
INSERT INTO `tp_type` VALUES ('136', '子分类5', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:58:49', '2', '4', '1');
INSERT INTO `tp_type` VALUES ('137', '5子分类1', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:58:56', '2', '5', '1');
INSERT INTO `tp_type` VALUES ('138', '5子分类2', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:58:58', '2', '5', '1');
INSERT INTO `tp_type` VALUES ('139', '5子分类3', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:59:01', '2', '5', '1');
INSERT INTO `tp_type` VALUES ('140', '5子分类4', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:59:03', '2', '5', '1');
INSERT INTO `tp_type` VALUES ('141', '5子分类5', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:59:06', '2', '5', '1');
INSERT INTO `tp_type` VALUES ('142', '6子分类1', '/uploads/img/5d9162211748f.png', '999', '2019-09-30 10:59:14', '2', '6', '1');

-- ----------------------------
-- Table structure for tp_user
-- ----------------------------
DROP TABLE IF EXISTS `tp_user`;
CREATE TABLE `tp_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `mail` varchar(255) DEFAULT NULL,
  `qq` varchar(255) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `head_img` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_user
-- ----------------------------
INSERT INTO `tp_user` VALUES ('1', null, null, '15198094779', null, null, '2019-09-30 11:43:27', null);
INSERT INTO `tp_user` VALUES ('2', 'test', null, '15198094778', null, null, '2019-09-30 12:40:16', null);

-- ----------------------------
-- Table structure for tp_user_log
-- ----------------------------
DROP TABLE IF EXISTS `tp_user_log`;
CREATE TABLE `tp_user_log` (
  `id` varchar(255) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `ua` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_user_log
-- ----------------------------
INSERT INTO `tp_user_log` VALUES (null, '1', 'info', '手机用户15198094778注册成功', '2019-09-30 12:40:16', '127.0.0.1', 'PostmanRuntime/7.17.1', 'http://douyin.com/user/login?phone=15198094778&type=phone');

-- ----------------------------
-- Table structure for tp_video
-- ----------------------------
DROP TABLE IF EXISTS `tp_video`;
CREATE TABLE `tp_video` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_video
-- ----------------------------
INSERT INTO `tp_video` VALUES ('15', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:28');
INSERT INTO `tp_video` VALUES ('16', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:30');
INSERT INTO `tp_video` VALUES ('17', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:40');
INSERT INTO `tp_video` VALUES ('18', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:41');
INSERT INTO `tp_video` VALUES ('19', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:42');
INSERT INTO `tp_video` VALUES ('20', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:43');
INSERT INTO `tp_video` VALUES ('21', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:45');
INSERT INTO `tp_video` VALUES ('22', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:46');
INSERT INTO `tp_video` VALUES ('23', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:47');
INSERT INTO `tp_video` VALUES ('24', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:54:24');
INSERT INTO `tp_video` VALUES ('25', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:54:39');
INSERT INTO `tp_video` VALUES ('26', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:54:44');

-- ----------------------------
-- Table structure for tp_view_history
-- ----------------------------
DROP TABLE IF EXISTS `tp_view_history`;
CREATE TABLE `tp_view_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(255) DEFAULT NULL,
  `vid` int(11) DEFAULT NULL,
  `time` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_view_history
-- ----------------------------
INSERT INTO `tp_view_history` VALUES ('6', '7ac99beb15d38b2df3e196c09523f458', '1', '1569810587');
INSERT INTO `tp_view_history` VALUES ('7', '7ac99beb15d38b2df3e196c09523f458', null, '1569811964');
INSERT INTO `tp_view_history` VALUES ('8', '2', '1', '1569825194');
INSERT INTO `tp_view_history` VALUES ('9', '2', '6', '1569825205');
INSERT INTO `tp_view_history` VALUES ('10', '2', '7', '1569825643');
INSERT INTO `tp_view_history` VALUES ('11', '2', '15', '1569826520');
INSERT INTO `tp_view_history` VALUES ('12', '18a8127269b2d4e45f98aec6c0c29746', '15', '1569826632');
INSERT INTO `tp_view_history` VALUES ('13', '18a8127269b2d4e45f98aec6c0c29746', '16', '1569826644');
INSERT INTO `tp_view_history` VALUES ('14', '18a8127269b2d4e45f98aec6c0c29746', '17', '1569826648');
INSERT INTO `tp_view_history` VALUES ('15', '18a8127269b2d4e45f98aec6c0c29746', '18', '1569826662');
