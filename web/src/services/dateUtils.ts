/**
 * 日期工具函数
 */

/**
 * 格式化日期为 YYYY-MM-DD 格式
 * @param date 日期对象或日期字符串
 */
export function formatDate(date: Date | string | number): string {
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

/**
 * 格式化日期时间为 YYYY-MM-DD HH:MM:SS 格式
 * @param date 日期对象或日期字符串
 */
export function formatDateTime(date: Date | string | number): string {
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  const hours = String(d.getHours()).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');
  const seconds = String(d.getSeconds()).padStart(2, '0');
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

/**
 * 根据周期类型获取下一个执行日期
 * @param currentDate 当前日期
 * @param recurrenceType 周期类型（1-每天, 2-每周, 3-每月, 4-每年）
 * @param interval 间隔
 */
export function getNextExecutionDate(
  currentDate: Date | string,
  recurrenceType: number,
  interval: number = 1
): Date {
  const date = new Date(currentDate);
  
  switch (recurrenceType) {
    case 1: // 每日
      date.setDate(date.getDate() + interval);
      break;
    case 2: // 每周
      date.setDate(date.getDate() + interval * 7);
      break;
    case 3: // 每月
      date.setMonth(date.getMonth() + interval);
      break;
    case 4: // 每年
      date.setFullYear(date.getFullYear() + interval);
      break;
    default:
      break;
  }
  
  return date;
}

/**
 * 获取周期类型的文本描述
 * @param recurrenceType 周期类型（1-每天, 2-每周, 3-每月, 4-每年）
 * @param interval 间隔
 */
export function getRecurrenceText(recurrenceType: number, interval: number = 1): string {
  switch (recurrenceType) {
    case 1:
      return interval === 1 ? '每天' : `每${interval}天`;
    case 2:
      return interval === 1 ? '每周' : `每${interval}周`;
    case 3:
      return interval === 1 ? '每月' : `每${interval}个月`;
    case 4:
      return interval === 1 ? '每年' : `每${interval}年`;
    default:
      return '无周期';
  }
}

/**
 * 格式化金额显示，添加千位分隔符
 * @param amount 金额
 * @param decimals 小数位数
 */
export function formatAmount(amount: number, decimals: number = 2): string {
  return amount.toLocaleString('zh-CN', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  });
}

export default {
  formatDate,
  formatDateTime,
  getNextExecutionDate,
  getRecurrenceText,
  formatAmount
};
