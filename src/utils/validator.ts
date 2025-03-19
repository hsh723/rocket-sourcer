export const validator = {
  isValidKeyword(keyword: string): boolean {
    return keyword.length >= 2 && keyword.length <= 50;
  },

  isValidPrice(price: number): boolean {
    return price > 0 && price <= 100000000;
  },

  isValidDate(date: Date): boolean {
    const now = new Date();
    return date <= now && date.getFullYear() >= 2000;
  }
};
