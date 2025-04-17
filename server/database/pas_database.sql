-- 创建数据库（如果需要）
-- CREATE DATABASE IF NOT EXISTS pas_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE pas_db;

-- 账户表
CREATE TABLE IF NOT EXISTS `account` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `current_balance` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '当前余额',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='账户表';

-- 预设交易表
CREATE TABLE IF NOT EXISTS `preset_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `transaction_type` tinyint(1) NOT NULL COMMENT '交易类型：1-收入，2-支出',
  `amount` decimal(12,2) NOT NULL COMMENT '金额',
  `execution_date` date NOT NULL COMMENT '执行日期',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1-待执行，2-执行中，3-已执行，4-终止',
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否周期性：0-否，1-是',
  `recurrence_type` tinyint(1) DEFAULT NULL COMMENT '周期类型：NULL/0-无，1-每日，2-每周，3-每月，4-每年',
  `recurrence_interval` int(11) DEFAULT NULL COMMENT '周期频率：如1（每单位周期），2（每2个单位周期）',
  `recurrence_end_date` date DEFAULT NULL COMMENT '周期结束日期：为NULL表示无限期',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_execution_date_status` (`execution_date`,`status`) COMMENT '执行日期和状态复合索引',
  KEY `idx_status` (`status`) COMMENT '状态索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='预设交易表';

-- 余额变更历史表
CREATE TABLE IF NOT EXISTS `balance_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `timestamp` datetime NOT NULL COMMENT '变更时间',
  `change_type` tinyint(1) NOT NULL COMMENT '变更类型：1-初始设置，2-收入执行，3-支出执行，4-手动编辑',
  `related_transaction_id` int(11) DEFAULT NULL COMMENT '关联交易ID',
  `amount_change` decimal(12,2) NOT NULL COMMENT '变更金额（可正可负）',
  `balance_after` decimal(12,2) NOT NULL COMMENT '变更后余额',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_timestamp` (`timestamp`) COMMENT '时间索引',
  KEY `idx_related_transaction_id` (`related_transaction_id`) COMMENT '关联交易索引',
  CONSTRAINT `fk_history_transaction` FOREIGN KEY (`related_transaction_id`) REFERENCES `preset_transaction` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='余额变更历史表';

-- 初始数据（可选）
-- INSERT INTO `account` (`current_balance`, `created_at`, `updated_at`) VALUES (0.00, NOW(), NOW());
