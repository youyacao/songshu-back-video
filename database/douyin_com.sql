/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50644
Source Host           : localhost:3306
Source Database       : douyin_com

Target Server Type    : MYSQL
Target Server Version : 50644
File Encoding         : 65001

Date: 2019-10-09 17:51:58
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
  `create_time` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_collection
-- ----------------------------
INSERT INTO `tp_collection` VALUES ('1', '1', '15', '2019-10-09 12:57:41');
INSERT INTO `tp_collection` VALUES ('2', '1', '16', '2019-10-09 12:57:41');
INSERT INTO `tp_collection` VALUES ('3', '3', '15', '2019-10-09 13:42:07');
INSERT INTO `tp_collection` VALUES ('4', '1', '17', '2019-10-09 14:29:04');
INSERT INTO `tp_collection` VALUES ('5', '1', '18', '2019-10-09 14:29:28');
INSERT INTO `tp_collection` VALUES ('6', '1', '19', '2019-10-09 14:29:33');

-- ----------------------------
-- Table structure for tp_comment
-- ----------------------------
DROP TABLE IF EXISTS `tp_comment`;
CREATE TABLE `tp_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL COMMENT '用户ID',
  `content` varchar(1000) DEFAULT NULL COMMENT '评论内容',
  `type` varchar(255) DEFAULT NULL,
  `vid` int(11) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `pid` int(11) DEFAULT NULL,
  `state` int(11) DEFAULT '0' COMMENT '评论状态：0 正常,1 删除',
  PRIMARY KEY (`id`),
  KEY `index_cuvid` (`uid`,`vid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_comment
-- ----------------------------
INSERT INTO `tp_comment` VALUES ('1', '2', '123', '1', '15', '2019-10-08 15:05:15', '0', '0');
INSERT INTO `tp_comment` VALUES ('2', '2', '123', '2', '15', '2019-10-08 15:17:59', '1', '0');
INSERT INTO `tp_comment` VALUES ('3', '2', '123', '2', '15', '2019-10-08 15:31:45', '2', '0');
INSERT INTO `tp_comment` VALUES ('4', '2', '123', '2', '15', '2019-10-08 15:34:15', '2', '0');
INSERT INTO `tp_comment` VALUES ('5', '2', '123', '2', '15', '2019-10-08 16:08:43', '2', '0');
INSERT INTO `tp_comment` VALUES ('6', '2', '123', '2', '15', '2019-10-08 16:12:40', '2', '0');
INSERT INTO `tp_comment` VALUES ('7', '2', '123', '2', '15', '2019-10-08 16:13:01', '2', '0');
INSERT INTO `tp_comment` VALUES ('8', '2', '123', '2', '15', '2019-10-08 16:14:41', '2', '0');
INSERT INTO `tp_comment` VALUES ('9', '2', '123', '2', '15', '2019-10-08 16:15:13', '2', '0');
INSERT INTO `tp_comment` VALUES ('10', '2', '123', '2', '15', '2019-10-08 16:15:27', '2', '0');
INSERT INTO `tp_comment` VALUES ('11', '2', '123', '2', '15', '2019-10-08 16:15:57', '2', '0');
INSERT INTO `tp_comment` VALUES ('12', '2', '123', '2', '15', '2019-10-08 16:16:21', '2', '0');
INSERT INTO `tp_comment` VALUES ('13', '2', '123', '2', '15', '2019-10-08 16:16:28', '2', '0');
INSERT INTO `tp_comment` VALUES ('14', '2', '123', '2', '15', '2019-10-08 16:16:41', '2', '0');
INSERT INTO `tp_comment` VALUES ('15', '2', '123', '2', '15', '2019-10-08 16:17:10', '2', '0');
INSERT INTO `tp_comment` VALUES ('16', '2', '123', '2', '15', '2019-10-08 16:17:27', '2', '0');
INSERT INTO `tp_comment` VALUES ('17', '2', '123', '1', '15', '2019-10-08 15:05:15', '0', '0');
INSERT INTO `tp_comment` VALUES ('18', '2', '123', '1', '15', '2019-10-08 15:05:15', '0', '0');

-- ----------------------------
-- Table structure for tp_follow
-- ----------------------------
DROP TABLE IF EXISTS `tp_follow`;
CREATE TABLE `tp_follow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `follow_id` int(11) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_follow
-- ----------------------------
INSERT INTO `tp_follow` VALUES ('2', '2', '2', '2019-10-09 15:58:35');
INSERT INTO `tp_follow` VALUES ('3', '2', '1', '2019-10-09 17:29:03');

-- ----------------------------
-- Table structure for tp_seach_history
-- ----------------------------
DROP TABLE IF EXISTS `tp_seach_history`;
CREATE TABLE `tp_seach_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `key` varchar(255) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_seach_history
-- ----------------------------
INSERT INTO `tp_seach_history` VALUES ('1', '2', '视频', '2019-10-09 17:29:30', 'video');
INSERT INTO `tp_seach_history` VALUES ('2', '2', '8', '2019-10-09 17:31:27', 'video');

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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_skr
-- ----------------------------
INSERT INTO `tp_skr` VALUES ('2', '2', '15', '1', '2019-10-09 10:58:21', null);
INSERT INTO `tp_skr` VALUES ('5', '1', '15', '1', '2019-10-09 13:36:24', null);
INSERT INTO `tp_skr` VALUES ('7', '1', '16', '1', '2019-10-09 13:37:45', null);
INSERT INTO `tp_skr` VALUES ('8', '3', '15', '1', '2019-10-09 13:41:55', null);
INSERT INTO `tp_skr` VALUES ('9', '2', '16', '1', '2019-10-09 14:58:25', null);

-- ----------------------------
-- Table structure for tp_skr_comment
-- ----------------------------
DROP TABLE IF EXISTS `tp_skr_comment`;
CREATE TABLE `tp_skr_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `vid` int(11) DEFAULT NULL,
  `cid` int(11) DEFAULT NULL,
  `skr` int(11) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_skr_comment
-- ----------------------------
INSERT INTO `tp_skr_comment` VALUES ('2', '1', '15', '1', '1', '2019-10-09 11:43:10', null);
INSERT INTO `tp_skr_comment` VALUES ('3', '1', '15', '2', '1', '2019-10-09 11:43:28', null);
INSERT INTO `tp_skr_comment` VALUES ('4', '1', '15', '3', '1', '2019-10-09 11:55:06', null);
INSERT INTO `tp_skr_comment` VALUES ('5', '1', '16', '3', '1', '2019-10-09 13:37:04', null);
INSERT INTO `tp_skr_comment` VALUES ('6', '2', '16', '3', '1', '2019-10-09 15:30:37', null);

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_user
-- ----------------------------
INSERT INTO `tp_user` VALUES ('1', null, null, '15198094779', null, null, '2019-09-30 11:43:27', null);
INSERT INTO `tp_user` VALUES ('2', 'test', null, '15198094778', null, null, '2019-09-30 12:40:16', null);
INSERT INTO `tp_user` VALUES ('3', null, null, '15198094777', null, null, '2019-10-09 13:41:35', null);

-- ----------------------------
-- Table structure for tp_user_log
-- ----------------------------
DROP TABLE IF EXISTS `tp_user_log`;
CREATE TABLE `tp_user_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `ua` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `header` varchar(1000) DEFAULT NULL,
  `request_data` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_user_log
-- ----------------------------
INSERT INTO `tp_user_log` VALUES ('7', '2', 'info', '用户test(2)发送评论(<script>halou</scritp>)成功', '2019-10-08 16:17:27', '127.0.0.1', 'PostmanRuntime/7.17.1', 'http://douyin.com/comment/data', '{\"Connection\":\"keep-alive\",\"Content-Length\":\"391\",\"Cookie\":\"PHPSESSID=61vq2vnkv9j24n75cthne40qr2\",\"Accept-Encoding\":\"gzip, deflate\",\"Content-Type\":\"multipart\\/form-data; boundary=--------------------------573325696815973312197631\",\"Host\":\"douyin.com\",\"Postman-Token\":\"10a1f179-5254-4d3d-b2f8-c126f18b2be7\",\"Cache-Control\":\"no-cache\",\"Accept\":\"*\\/*\",\"User-Agent\":\"PostmanRuntime\\/7.17.1\"}', '{\"vid\":\"15\",\"content\":\"<script>halou<\\/scritp>\",\"pid\":\"2\",\"action\":\"data\"}');
INSERT INTO `tp_user_log` VALUES ('8', '2', 'info', '用户test(2)发布视频成功', '2019-10-08 17:38:05', '127.0.0.1', 'PostmanRuntime/7.17.1', 'http://douyin.com/video/data', '{\"Connection\":\"keep-alive\",\"Content-Length\":\"412\",\"Cookie\":\"PHPSESSID=61vq2vnkv9j24n75cthne40qr2\",\"Accept-Encoding\":\"gzip, deflate\",\"Content-Type\":\"multipart\\/form-data; boundary=--------------------------093235138922669139527785\",\"Host\":\"douyin.com\",\"Postman-Token\":\"cddc7f1c-6682-4017-87dc-b7cda6510650\",\"Cache-Control\":\"no-cache\",\"Accept\":\"*\\/*\",\"User-Agent\":\"PostmanRuntime\\/7.17.1\"}', '{\"title\":\"\\u7b2c8\\u4e2a\\u89c6\\u9891\",\"url\":\"uploads\\/video\\/5d9c2d8a83002.mp4\",\"type\":\"131\",\"action\":\"data\"}');
INSERT INTO `tp_user_log` VALUES ('9', '1', 'info', '手机用户15198094777注册成功', '2019-10-09 13:41:35', '127.0.0.1', 'PostmanRuntime/7.17.1', 'http://douyin.com/user/login?phone=15198094777&type=phone', '{\"Connection\":\"keep-alive\",\"Cookie\":\"PHPSESSID=evjsd88eme0p4m6hcaneihh6g4\",\"Accept-Encoding\":\"gzip, deflate\",\"Host\":\"douyin.com\",\"Postman-Token\":\"f9027200-51a4-4653-939c-71b8be51aa31\",\"Cache-Control\":\"no-cache\",\"Accept\":\"*\\/*\",\"User-Agent\":\"PostmanRuntime\\/7.17.1\",\"Content-Length\":\"\",\"Content-Type\":\"\"}', '{\"phone\":\"15198094777\",\"type\":\"phone\",\"action\":\"login\"}');
INSERT INTO `tp_user_log` VALUES ('10', '2', 'info', '用户test(2)获取用户最新信息', '2019-10-09 15:30:22', '127.0.0.1', 'PostmanRuntime/7.17.1', 'http://douyin.com/user/userinfo', '{\"Connection\":\"keep-alive\",\"Cookie\":\"PHPSESSID=evjsd88eme0p4m6hcaneihh6g4\",\"Accept-Encoding\":\"gzip, deflate\",\"Host\":\"douyin.com\",\"Postman-Token\":\"c6632d37-7d0f-4ca9-b8a3-57488b7819e1\",\"Cache-Control\":\"no-cache\",\"Accept\":\"*\\/*\",\"User-Agent\":\"PostmanRuntime\\/7.17.1\",\"Content-Length\":\"\",\"Content-Type\":\"\"}', '{\"action\":\"userinfo\"}');
INSERT INTO `tp_user_log` VALUES ('11', '2', 'info', '用户test(2)查看收藏列表', '2019-10-09 15:30:28', '127.0.0.1', 'PostmanRuntime/7.17.1', 'http://douyin.com/collect/list', '{\"Connection\":\"keep-alive\",\"Cookie\":\"PHPSESSID=evjsd88eme0p4m6hcaneihh6g4\",\"Accept-Encoding\":\"gzip, deflate\",\"Host\":\"douyin.com\",\"Postman-Token\":\"5dec1575-55be-4352-8072-ebf165a27f4b\",\"Cache-Control\":\"no-cache\",\"Accept\":\"*\\/*\",\"User-Agent\":\"PostmanRuntime\\/7.17.1\",\"Content-Length\":\"\",\"Content-Type\":\"\"}', '{\"action\":\"list\"}');
INSERT INTO `tp_user_log` VALUES ('12', '2', 'info', '用户test(2)点赞第8个视频(16)的评论成功', '2019-10-09 15:30:37', '127.0.0.1', 'PostmanRuntime/7.17.1', 'http://douyin.com/skr_comment/like?type=1&vid=16&cid=3', '{\"Connection\":\"keep-alive\",\"Cookie\":\"PHPSESSID=evjsd88eme0p4m6hcaneihh6g4\",\"Accept-Encoding\":\"gzip, deflate\",\"Host\":\"douyin.com\",\"Postman-Token\":\"090dece3-5b11-4f70-9a02-bbd6c2c925e7\",\"Cache-Control\":\"no-cache\",\"Accept\":\"*\\/*\",\"User-Agent\":\"PostmanRuntime\\/7.17.1\",\"Content-Length\":\"\",\"Content-Type\":\"\"}', '{\"type\":\"1\",\"vid\":\"16\",\"cid\":\"3\",\"action\":\"like \"}');
INSERT INTO `tp_user_log` VALUES ('13', '2', 'info', '用户test(2)点赞<第8个视频(16)>的评论“123”成功', '2019-10-09 15:33:47', '127.0.0.1', 'PostmanRuntime/7.17.1', 'http://douyin.com/skr_comment/like?type=1&vid=16&cid=3', '{\"Connection\":\"keep-alive\",\"Cookie\":\"PHPSESSID=evjsd88eme0p4m6hcaneihh6g4\",\"Accept-Encoding\":\"gzip, deflate\",\"Host\":\"douyin.com\",\"Postman-Token\":\"d1fa7995-3006-4116-9f00-ddbd849eff2e\",\"Cache-Control\":\"no-cache\",\"Accept\":\"*\\/*\",\"User-Agent\":\"PostmanRuntime\\/7.17.1\",\"Content-Length\":\"\",\"Content-Type\":\"\"}', '{\"type\":\"1\",\"vid\":\"16\",\"cid\":\"3\",\"action\":\"like \"}');
INSERT INTO `tp_user_log` VALUES ('14', '2', 'info', '用户test(2)查看第1页作品列表', '2019-10-09 16:36:10', '127.0.0.1', 'PostmanRuntime/7.17.1', 'http://douyin.com/video/data?type=user', '{\"Connection\":\"keep-alive\",\"Cookie\":\"PHPSESSID=evjsd88eme0p4m6hcaneihh6g4\",\"Accept-Encoding\":\"gzip, deflate\",\"Host\":\"douyin.com\",\"Postman-Token\":\"507412e3-3116-472c-b098-6b4e3953aa64\",\"Cache-Control\":\"no-cache\",\"Accept\":\"*\\/*\",\"User-Agent\":\"PostmanRuntime\\/7.17.1\",\"Content-Length\":\"\",\"Content-Type\":\"\"}', '{\"type\":\"user\",\"action\":\"data\"}');
INSERT INTO `tp_user_log` VALUES ('15', '2', 'info', '用户test(2)查看第1页喜欢列表', '2019-10-09 17:16:05', '127.0.0.1', 'PostmanRuntime/7.17.1', 'http://douyin.com/video/data?type=likes', '{\"Connection\":\"keep-alive\",\"Cookie\":\"PHPSESSID=evjsd88eme0p4m6hcaneihh6g4\",\"Accept-Encoding\":\"gzip, deflate\",\"Host\":\"douyin.com\",\"Postman-Token\":\"111e2d31-b4cd-4431-b70d-32dabef97ccf\",\"Cache-Control\":\"no-cache\",\"Accept\":\"*\\/*\",\"User-Agent\":\"PostmanRuntime\\/7.17.1\",\"Content-Length\":\"\",\"Content-Type\":\"\"}', '{\"type\":\"likes\",\"action\":\"data\"}');
INSERT INTO `tp_user_log` VALUES ('16', '2', 'info', '用户test(2)查看第1页喜欢列表', '2019-10-09 17:16:34', '127.0.0.1', 'PostmanRuntime/7.17.1', 'http://douyin.com/video/data?type=likes', '{\"Connection\":\"keep-alive\",\"Cookie\":\"PHPSESSID=evjsd88eme0p4m6hcaneihh6g4\",\"Accept-Encoding\":\"gzip, deflate\",\"Host\":\"douyin.com\",\"Postman-Token\":\"37ab8b72-a227-45c6-9f63-c4e573711103\",\"Cache-Control\":\"no-cache\",\"Accept\":\"*\\/*\",\"User-Agent\":\"PostmanRuntime\\/7.17.1\",\"Content-Length\":\"\",\"Content-Type\":\"\"}', '{\"type\":\"likes\",\"action\":\"data\"}');
INSERT INTO `tp_user_log` VALUES ('17', '2', 'info', '用户test(2)查看第1页喜欢列表', '2019-10-09 17:34:17', '127.0.0.1', 'PostmanRuntime/7.17.1', 'http://douyin.com/video/data?type=likes', '{\"Connection\":\"keep-alive\",\"Cookie\":\"PHPSESSID=evjsd88eme0p4m6hcaneihh6g4\",\"Accept-Encoding\":\"gzip, deflate\",\"Host\":\"douyin.com\",\"Postman-Token\":\"7ef05491-cd6c-4597-bf33-55df33d12180\",\"Cache-Control\":\"no-cache\",\"Accept\":\"*\\/*\",\"User-Agent\":\"PostmanRuntime\\/7.17.1\",\"Content-Length\":\"\",\"Content-Type\":\"\"}', '{\"type\":\"likes\",\"action\":\"data\"}');

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
  `create_time` datetime(6) DEFAULT NULL,
  `state` int(11) unsigned DEFAULT '0' COMMENT '是否删除，0：未删除，1：已删除',
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_vid` (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_video
-- ----------------------------
INSERT INTO `tp_video` VALUES ('15', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:28.000000', '0');
INSERT INTO `tp_video` VALUES ('16', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:30.000000', '0');
INSERT INTO `tp_video` VALUES ('17', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:40.000000', '0');
INSERT INTO `tp_video` VALUES ('18', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:41.000000', '0');
INSERT INTO `tp_video` VALUES ('19', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:42.000000', '0');
INSERT INTO `tp_video` VALUES ('20', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:43.000000', '0');
INSERT INTO `tp_video` VALUES ('21', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:45.000000', '0');
INSERT INTO `tp_video` VALUES ('22', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:46.000000', '0');
INSERT INTO `tp_video` VALUES ('23', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:53:47.000000', '0');
INSERT INTO `tp_video` VALUES ('24', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:54:24.000000', '0');
INSERT INTO `tp_video` VALUES ('25', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:54:39.000000', '0');
INSERT INTO `tp_video` VALUES ('26', '第8个视频', '131', 'uploads/video/5d919661a1ce5.mp4', '2c56702dcd7ca400d2c5d30dd0c8adc7.png', '2', '2019-09-30 14:54:44.000000', '0');
INSERT INTO `tp_video` VALUES ('27', '第8个视频', '131', 'uploads/video/5d9c2d8a83002.mp4', '163aa6b721b5b3d037e76b8d83f0eeeb.png', '2', '2019-10-08 14:33:48.000000', '0');
INSERT INTO `tp_video` VALUES ('28', '第8个视频', '131', 'uploads/video/5d9c2d8a83002.mp4', '163aa6b721b5b3d037e76b8d83f0eeeb.png', '2', '2019-10-08 16:58:49.000000', '0');
INSERT INTO `tp_video` VALUES ('29', '第8个视频', '142', 'uploads/video/5d9c2d8a83002.mp4', '163aa6b721b5b3d037e76b8d83f0eeeb.png', '2', '2019-10-08 16:59:24.000000', '0');
INSERT INTO `tp_video` VALUES ('30', '第8个视频', '142', 'uploads/video/5d9c2d8a83002.mp4', 'uploads/img/163aa6b721b5b3d037e76b8d83f0eeeb.png', '2', '2019-10-08 17:32:46.000000', '0');
INSERT INTO `tp_video` VALUES ('31', '第8个视频', '142', 'uploads/video/5d9c2d8a83002.mp4', 'uploads/img/163aa6b721b5b3d037e76b8d83f0eeeb.png', '2', '2019-10-08 17:38:04.000000', '0');

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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tp_view_history
-- ----------------------------
