# 个人收支管理系统(PAS)系统设计文档

## 1. 文档概述
### 1.1 文档目的
本文档描述个人收支管理系统(PAS)的整体架构设计、技术选型及各模块详细设计，为系统实现提供技术指导。

### 1.2 参考文档
- 《个人收支管理系统需求规格说明书》(PRD_v1.0.md)

## 2. 系统架构设计

### 2.1 整体架构
个人收支管理系统采用简化的MVC架构:
- 视图层：用户界面及交互
- 控制层：业务逻辑处理
- 模型层：数据存储与检索

### 2.2 技术栈选择
#### 2.2.1 前端技术
- 框架：Vue.js
- UI组件库：Ant Design
- 数据可视化：ECharts
- 状态管理：pinia

#### 2.2.2 后端技术
- 开发语言：PHP
- Web框架：ThinkPHP
- 数据存储：SQLite (简化部署)

### 2.3 系统部署架构
- 单机部署，无需复杂的服务器配置
- 可选择打包为桌面应用（使用Electron）

## 3. 模块设计

### 3.1 收入管理模块
#### 3.1.1 模块职责
负责个人收入记录的添加、编辑、删除和查询。

#### 3.1.2 核心类设计
- IncomeController：处理收入相关请求
- IncomeService：处理收入业务逻辑
- Income：收入实体类
- IncomeCategory：收入分类实体类

#### 3.1.3 接口设计
- POST /api/incomes：添加收入记录
- PUT /api/incomes/{id}：更新收入记录
- DELETE /api/incomes/{id}：删除收入记录
- GET /api/incomes：查询收入记录
- GET /api/incomes/categories：获取收入分类
- POST /api/incomes/categories：添加收入分类

### 3.2 支出管理模块
#### 3.2.1 模块职责
负责个人支出记录的添加、编辑、删除和查询。

#### 3.2.2 核心类设计
- ExpenseController：处理支出相关请求
- ExpenseService：处理支出业务逻辑
- Expense：支出实体类
- ExpenseCategory：支出分类实体类

#### 3.2.3 接口设计
- POST /api/expenses：添加支出记录
- PUT /api/expenses/{id}：更新支出记录
- DELETE /api/expenses/{id}：删除支出记录
- GET /api/expenses：查询支出记录
- GET /api/expenses/categories：获取支出分类
- POST /api/expenses/categories：添加支出分类

### 3.3 预算管理模块
#### 3.3.1 模块职责
负责个人预算的设置和监控。

#### 3.3.2 核心类设计
- BudgetController：处理预算相关请求
- BudgetService：处理预算业务逻辑
- Budget：预算实体类

#### 3.3.3 接口设计
- POST /api/budgets：创建预算
- PUT /api/budgets/{id}：更新预算
- DELETE /api/budgets/{id}：删除预算
- GET /api/budgets：查询预算
- GET /api/budgets/status：查询预算执行状态

### 3.4 数据统计分析模块
#### 3.4.1 模块职责
负责个人收支数据的统计、分析和可视化。

#### 3.4.2 核心类设计
- StatisticsController：处理统计相关请求
- StatisticsService：处理统计业务逻辑
- ChartDTO：图表数据传输对象

#### 3.4.3 接口设计
- GET /api/statistics/overview：获取收支概览
- GET /api/statistics/trend：获取收支趋势
- GET /api/statistics/category：获取分类统计
- GET /api/statistics/monthly：获取月度报表

### 3.5 数据导入导出模块
#### 3.5.1 模块职责
负责收支数据的导入、导出和备份。

#### 3.5.2 核心类设计
- ExportController：处理导出相关请求
- ImportController：处理导入相关请求
- BackupService：处理备份业务逻辑

#### 3.5.3 接口设计
- POST /api/export：导出数据到CSV/Excel
- POST /api/import：从CSV/Excel导入数据
- POST /api/backup：创建数据备份
- GET /api/backup：获取备份列表
- POST /api/backup/restore：恢复备份

## 4. 数据库设计
### 4.1 主要实体
- Income：收入记录
- Expense：支出记录
- Category：分类信息
- Budget：预算设置
- Backup：备份记录

### 4.2 实体关系图
简化的实体关系模型，无需用户表和账户表，聚焦于收支记录和分析功能。

## 5. 安全设计
### 5.1 数据安全
- 本地数据加密存储
- 自动备份机制

## 6. 性能优化设计
### 6.1 前端优化
- 本地数据缓存
- 懒加载图表组件

### 6.2 后端优化
- 索引优化
- 查询优化

## 7. 扩展性考虑
- 可扩展为云同步版本
- 导出为多种格式

## 8. 附录
### 8.1 关键算法
- 预算执行率计算
- 收支趋势分析算法
