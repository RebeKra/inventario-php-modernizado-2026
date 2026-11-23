import { describe, expect, it } from "vitest";
import { esStockBajo, formatearGuaranies } from "./formato";

describe("formatearGuaranies", () => {
  it("usa punto como separador de miles y sin decimales", () => {
    expect(formatearGuaranies("890000.00")).toBe("Gs. 890.000");
    expect(formatearGuaranies("1250000")).toBe("Gs. 1.250.000");
  });

  it("no pierde precisión con importes muy grandes", () => {
    expect(formatearGuaranies("9007199254740993.00")).toBe("Gs. 9.007.199.254.740.993");
  });

  it("muestra un guion cuando no hay importe", () => {
    expect(formatearGuaranies(null)).toBe("—");
  });
});

describe("esStockBajo", () => {
  it("marca 5 unidades o menos", () => {
    expect(esStockBajo(5)).toBe(true);
    expect(esStockBajo(6)).toBe(false);
  });
});
