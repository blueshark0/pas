# 个人收支管理系统(PAS)数据库设计文档

## 1. 文档概述
### 1.1 文档目的
本文档描述个人收支管理系统(PAS)的数据库设计，包括表结构、字段定义、关系以及索引设计，为系统实现提供数据层面的技术指导。

### 1.2 参考文档
- 《个人收支管理系统需求规格说明书》(PRD_v1.0.md)
- 《个人收支管理系统系统设计文档》(SDD_v1.0.md)

## 2. 数据库总体设计

### 2.1 数据库选择
系统采用SQLite作为数据库管理系统，具有以下优势：
- 零配置，无需服务器支持
- 单文件数据库，便于备份和迁移
- 轻量级，资源占用少
- 适合个人应用场景

### 2.2 实体关系图
![个人收支管理系统ER图](../assets/img/pas_er_diagram.png)

## 3. 表结构设计

### 3.1 收入分类表（income_categories）
存储收入的分类信息。

| 字段名 | 数据类型 | 长度 | 允许NULL | 默认值 | 主键 | 说明 |
|-------|---------|------|----------|--------|------|------|
| id | INTEGER | - | 否 | - | 是 | 分类ID，自增 |
| name | VARCHAR | 50 | 否 | - | 否 | 分类名称 |
| icon | VARCHAR | 100 | 是 | NULL | 否 | 分类图标 |
| color | VARCHAR | 20 | 是 | '#1890ff' | 否 | 分类颜色 |
| create_time | TIMESTAMP | - | 否 | CURRENT_TIMESTAMP | 否 | 创建时间 |
| update_time | TIMESTAMP | - | 否 | CURRENT_TIMESTAMP | 否 | 更新时间 |

索引：
- PRIMARY KEY (`id`)
- UNIQUE KEY `uk_name` (`name`)

### 3.2 支出分类表（expense_categories）
存储支出的分类信息。

| 字段名 | 数据类型 | 长度 | 允许NULL | 默认值 | 主键 | 说明 |
|-------|---------|------|----------|--------|------|------|
| id | INTEGER | - | 否 | - | 是 | 分类ID，自增 |
| name | VARCHAR | 50 | 否 | - | 否 | 分类名称 |
| icon | VARCHAR | 100 | 是 | NULL | 否 | 分类图标 |
| color | VARCHAR | 20 | 是 | '#f5222d' | 否 | 分类颜色 |
| create_time | TIMESTAMP | - | 否 | CURRENT_TIMESTAMP | 否 | 创建时间 |
| update_time | TIMESTAMP | - | 否 | CURRENT_TIMESTAMP | 否 | 更新时间 |

索引：
- PRIMARY KEY (`id`)
- UNIQUE KEY `uk_name` (`name`)

### 3.3 收入记录表（incomes）
存储收入记录的详细信息。

| 字段名 | 数据类型 | 长度 | 允许NULL | 默认值 | 主键 | 说明 |
|-------|---------|------|----------|--------|------|------|
| id | INTEGER | - | 否 | - | 是 | 收入记录ID，自增 |
| amount | DECIMAL | (10,2) | 否 | 0.00 | 否 | 收入金额 |
| category_id | INTEGER | - | 否 | - | 否 | 收入分类ID，关联income_categories表 |
| income_date | DATE | - | 否 | CURRENT_DATE | 否 | 收入日期 |
| description | VARCHAR | 200 | 是 | NULL | 否 | 收入描述 |
| source | VARCHAR | 100 | 是 | NULL | 否 | 收入来源 |
| attachment | VARCHAR | 255 | 是 | NULL | 否 | 附件路径 |
| create_time | TIMESTAMP | - | 否 | CURRENT_TIMESTAMP | 否 | 创建时间 |
| update_time | TIMESTAMP | - | 否 | CURRENT_TIMESTAMP | 否 | 更新时间 |

索引：
- PRIMARY KEY (`id`)
- INDEX `idx_category` (`category_id`)
- INDEX `idx_income_date` (`income_date`)

### 3.4 支出记录表（expenses）
存储支出记录的详细信息。

| 字段名 | 数据类型 | 长度 | 允许NULL | 默认值 | 主键 | 说明 |
|-------|---------|------|----------|--------|------|------|
| id | INTEGER | - | 否 | - | 是 | 支出记录ID，自增 |
| amount | DECIMAL | (10,2) | 否 | 0.00 | 否 | 支出金额 |
| category_id | INTEGER | - | 否 | - | 否 | 支出分类ID，关联expense_categories表 |
| expense_date | DATE | - | 否 | CURRENT_DATE | 否 | 支出日期 |
| description | VARCHAR | 200 | 是 | NULL | 否 | 支出描述 |
| merchant | VARCHAR | 100 | 是 | NULL | 否 | 商家信息 |
| payment_method | VARCHAR | 50 | 是 | '现金' | 否 | 支付方式 |
| attachment | VARCHAR | 255 | 是 | NULL | 否 | 附件路径 |
| create_time | TIMESTAMP | - | 否 | CURRENT_TIMESTAMP | 否 | 创建时间 |
| update_time | TIMESTAMP | - | 否 | CURRENT_TIMESTAMP | 否 | 更新时间 |

索引：
- PRIMARY KEY (`id`)
- INDEX `idx_category` (`category_id`)
- INDEX `idx_expense_date` (`expense_date`)

### 3.5 预算表（budgets）
存储预算设置信息。

| 字段名 | 数据类型 | 长度 | 允许NULL | 默认值 | 主键 | 说明 |
|-------|---------|------|----------|--------|------|------|
| id | INTEGER | - | 否 | - | 是 | 预算ID，自增 |
| category_id | INTEGER | - | 是 | NULL | 否 | 支出分类ID，关联expense_categories表，NULL表示总预算 |
| amount | DECIMAL | (10,2) | 否 | 0.00 | 否 | 预算金额 |
| start_date | DATE | - | 否 | - | 否 | 预算开始日期 |
| end_date | DATE | - | 否 | - | 否 | 预算结束日期 |
| notification_threshold | INTEGER | - | 是 | 80 | 否 | 提醒阈值（百分比） |
| description | VARCHAR | 200 | 是 | NULL | 否 | 预算描述 |
| create_time | TIMESTAMP | - | 否 | CURRENT_TIMESTAMP | 否 | 创建时间 |
| update_time | TIMESTAMP | - | 否 | CURRENT_TIMESTAMP | 否 | 更新时间 |

索引：
- PRIMARY KEY (`id`)
- INDEX `idx_category` (`category_id`)
- INDEX `idx_date_range` (`start_date`, `end_date`)

### 3.6 备份记录表（backups）
存储系统备份信息。

| 字段名 | 数据类型 | 长度 | 允许NULL | 默认值 | 主键 | 说明 |
|-------|---------|------|----------|--------|------|------|
| id | INTEGER | - | 否 | - | 是 | 备份ID，自增 |
| file_name | VARCHAR | 255 | 否 | - | 否 | 备份文件名 |
| file_path | VARCHAR | 255 | 否 | - | 否 | 备份文件路径 |
| file_size | INTEGER | - | 否 | 0 | 否 | 备份文件大小(字节) |
| description | VARCHAR | 200 | 是 | NULL | 否 | 备份描述 |
| backup_time | TIMESTAMP | - | 否 | CURRENT_TIMESTAMP | 否 | 备份时间 |

索引：
- PRIMARY KEY (`id`)
- INDEX `idx_backup_time` (`backup_time`)

## 4. 数据库初始化

### 4.1 预置数据
系统初始化时，将预置以下数据：

#### 4.1.1 收入分类预置数据
- 工资收入
- 投资收益
- 奖金
- 礼金
- 报销
- 其他收入

#### 4.1.2 支出分类预置数据
- 餐饮
- 交通
- 购物
- 住房
- 娱乐
- 医疗
- 教育
- 旅行
- 日用品
- 通讯
- 其他支出

### 4.2 数据库初始化脚本
见附录A。

## 5. 数据维护

### 5.1 数据备份
系统提供以下备份策略：
- 手动备份：用户可随时创建数据备份
- 自动备份：系统每周自动创建一次备份
- 导出备份：用户可将数据导出为CSV或Excel格式

### 5.2 数据恢复
提供从备份文件恢复数据的功能，支持选择恢复点。

### 5.3 数据清理
提供按时间范围清理历史数据的功能，防止数据库过大影响性能。

## 6. 索引与性能优化

### 6.1 索引策略
系统针对常用查询场景设计了以下索引：
- 日期索引：优化时间范围查询
- 分类索引：优化按分类查询
- 复合索引：优化多条件查询

### 6.2 查询优化建议
- 尽量使用预先定义的索引字段作为查询条件
- 避免使用`SELECT *`，只查询需要的字段
- 大数据量查询时使用分页

## 附录A：数据库初始化脚本

```sql
-- 创建收入分类表
CREATE TABLE income_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(100),
    color VARCHAR(20) DEFAULT '#1890ff',
    create_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 创建支出分类表
CREATE TABLE expense_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(100),
    color VARCHAR(20) DEFAULT '#f5222d',
    create_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 创建收入记录表
CREATE TABLE incomes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    category_id INTEGER NOT NULL,
    income_date DATE NOT NULL DEFAULT CURRENT_DATE,
    description VARCHAR(200),
    source VARCHAR(100),
    attachment VARCHAR(255),
    create_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES income_categories(id)
);

-- 创建支出记录表
CREATE TABLE expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    category_id INTEGER NOT NULL,
    expense_date DATE NOT NULL DEFAULT CURRENT_DATE,
    description VARCHAR(200),
    merchant VARCHAR(100),
    payment_method VARCHAR(50) DEFAULT '现金',
    attachment VARCHAR(255),
    create_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id)
);

-- 创建预算表
CREATE TABLE budgets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    notification_threshold INTEGER DEFAULT 80,
    description VARCHAR(200),
    create_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id)
);

-- 创建备份记录表
CREATE TABLE backups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INTEGER NOT NULL DEFAULT 0,
    description VARCHAR(200),
    backup_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 创建索引
CREATE INDEX idx_incomes_category ON incomes(category_id);
CREATE INDEX idx_incomes_date ON incomes(income_date);
CREATE INDEX idx_expenses_category ON expenses(category_id);
CREATE INDEX idx_expenses_date ON expenses(expense_date);
CREATE INDEX idx_budgets_category ON budgets(category_id);
CREATE INDEX idx_budgets_date_range ON budgets(start_date, end_date);
CREATE INDEX idx_backups_time ON backups(backup_time);

-- 插入默认收入分类
INSERT INTO income_categories (name, icon, color) VALUES 
('工资收入', 'wallet', '#1890ff'),
('投资收益', 'fund', '#52c41a'),
('奖金', 'trophy', '#faad14'),
('礼金', 'gift', '#eb2f96'),
('报销', 'account-book', '#722ed1'),
('其他收入', 'more', '#bfbfbf');

-- 插入默认支出分类
INSERT INTO expense_categories (name, icon, color) VALUES 
('餐饮', 'coffee', '#f5222d'),
('交通', 'car', '#fa8c16'),
('购物', 'shopping', '#faad14'),
('住房', 'home', '#52c41a'),
('娱乐', 'play-circle', '#2f54eb'),
('医疗', 'medicine-box', '#eb2f96'),
('教育', 'read', '#722ed1'),
('旅行', 'compass', '#fa541c'),
('日用品', 'shopping-cart', '#13c2c2'),
('通讯', 'phone', '#1890ff'),
('其他支出', 'more', '#8c8c8c');
