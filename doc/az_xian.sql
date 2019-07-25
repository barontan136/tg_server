/*
 Navicat Premium Data Transfer

 Source Server         : meter
 Source Server Type    : MySQL
 Source Server Version : 50723
 Source Host           : 106.14.193.73
 Source Database       : az_xian

 Target Server Type    : MySQL
 Target Server Version : 50723
 File Encoding         : utf-8

 Date: 09/24/2018 07:20:36 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `e_center_control`
-- ----------------------------
DROP TABLE IF EXISTS `e_center_control`;
CREATE TABLE `e_center_control` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `group_id` int(10) NOT NULL COMMENT '所属分组ID',
  `group_name` varchar(100) NOT NULL COMMENT '所属分组名称',
  `number` int(10) DEFAULT '0' COMMENT '主控编号',
  `name` varchar(100) NOT NULL COMMENT '主控名称',
  `address` varchar(200) DEFAULT NULL COMMENT '主控大楼详细地址\r\n',
  `ip` varchar(50) DEFAULT NULL COMMENT '主控中心IP地址',
  `port` varchar(20) DEFAULT NULL COMMENT '主控中心端口号',
  `config` text COMMENT '主控中心配置 json',
  `link_config` text COMMENT '通信服务参数配置 json',
  `play_config` text COMMENT '播放服务参数配置 json',
  `ctrl_config` text COMMENT '设备控制服务参数配置 json',
  `create_dt` datetime DEFAULT NULL,
  `update_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
--  Table structure for `e_center_group`
-- ----------------------------
DROP TABLE IF EXISTS `e_center_group`;
CREATE TABLE `e_center_group` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `number` int(10) DEFAULT '0' COMMENT '分组编号',
  `name` varchar(100) NOT NULL COMMENT '分组名称',
  `desc` varchar(255) DEFAULT '' COMMENT '分组描述',
  `act_list` text COMMENT '节目单列表 json',
  `create_dt` datetime DEFAULT NULL,
  `update_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `e_energy_day`
-- ----------------------------
DROP TABLE IF EXISTS `e_energy_day`;
CREATE TABLE `e_energy_day` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `meter_id` int(10) NOT NULL COMMENT '电表ID',
  `meter_NO` varchar(50) NOT NULL COMMENT '表地址',
  `merter_name` varchar(100) DEFAULT NULL COMMENT '电表住户名称',
  `ctrl_id` int(10) NOT NULL COMMENT '所属主控ID',
  `ctrl_name` varchar(100) NOT NULL COMMENT '所属主控名称',
  `power_pos_total` int(8) DEFAULT NULL COMMENT '正向有功总,单位kwh*100',
  `power_date` date DEFAULT NULL COMMENT '电量日期',
  `create_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `e_energy_month`
-- ----------------------------
DROP TABLE IF EXISTS `e_energy_month`;
CREATE TABLE `e_energy_month` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `meter_id` int(10) NOT NULL COMMENT '电表ID',
  `meter_NO` varchar(50) NOT NULL COMMENT '表地址',
  `merter_name` varchar(100) DEFAULT NULL COMMENT '电表住户名称',
  `ctrl_id` int(10) NOT NULL COMMENT '所属主控ID',
  `ctrl_name` varchar(100) NOT NULL COMMENT '所属主控名称',
  `power_pos_total` int(8) DEFAULT NULL COMMENT '正向有功总,单位kwh*100',
  `power_date` date DEFAULT NULL COMMENT '电量日期',
  `create_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
--  Table structure for `e_energy_real`
-- ----------------------------
DROP TABLE IF EXISTS `e_energy_real`;
CREATE TABLE `e_energy_real` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `meter_id` int(10) NOT NULL COMMENT '电表ID',
  `meter_NO` varchar(50) NOT NULL COMMENT '表地址',
  `merter_name` varchar(100) DEFAULT NULL COMMENT '电表住户名称',
  `ctrl_id` int(10) NOT NULL COMMENT '所属主控ID',
  `ctrl_name` varchar(100) NOT NULL COMMENT '所属主控名称',
  `power_pos_total` int(8) DEFAULT NULL COMMENT '正向有功总,单位kwh*100',
  `power_vol` int(5) DEFAULT NULL COMMENT '电压，V*100',
  `power_current` int(8) DEFAULT NULL COMMENT '电流，mA',
  `power_statubit` int(8) DEFAULT NULL COMMENT '状态字',
  `power_factor` int(8) DEFAULT NULL COMMENT '功率因数',
  `create_dt` datetime DEFAULT NULL,
  `update_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
--  Table structure for `e_meters`
-- ----------------------------
DROP TABLE IF EXISTS `e_meters`;
CREATE TABLE `e_meters` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `ctrl_id` int(10) NOT NULL COMMENT '所属主控ID',
  `ctrl_name` varchar(100) NOT NULL COMMENT '所属主控名称',
  `name` varchar(100) DEFAULT NULL COMMENT '电表住户名称',
  `address` varchar(200) DEFAULT NULL COMMENT '电表住户地址',
  `meter_NO` varchar(50) NOT NULL COMMENT '表地址',
  `baudrate` varchar(10) DEFAULT NULL COMMENT '波特率',
  `stopbit` tinyint(3) DEFAULT NULL COMMENT '停止位',
  `verifybit` int(3) DEFAULT NULL COMMENT '校验位',
  `create_dt` datetime DEFAULT NULL,
  `update_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `e_system_config`
-- ----------------------------
DROP TABLE IF EXISTS `e_system_config`;
CREATE TABLE `e_system_config` (
  `id` int(10) NOT NULL,
  `name` varchar(50) NOT NULL COMMENT '配置项名称',
  `value` varchar(200) NOT NULL COMMENT '配置项内容',
  `desc` varchar(500) DEFAULT NULL,
  `type` tinyint(3) DEFAULT '0',
  `create_dt` datetime DEFAULT NULL,
  `update_dt` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
