const guaranies = new Intl.NumberFormat("es-PY", { maximumFractionDigits: 0 });

/** "890000.00" → "Gs. 890.000". Trabaja con texto para no perder precisión. */
export function formatearGuaranies(importe: string | null): string {
  if (importe === null || importe === "") return "—";
  const [entero] = importe.split(".");
  return `Gs. ${guaranies.format(BigInt(entero))}`;
}

export const STOCK_BAJO = 5;

export function esStockBajo(stock: number): boolean {
  return stock <= STOCK_BAJO;
}
