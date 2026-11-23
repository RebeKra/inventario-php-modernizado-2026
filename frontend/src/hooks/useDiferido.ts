import { useEffect, useState } from "react";

/** Devuelve el valor solo cuando deja de cambiar durante `ms` milisegundos. */
export function useDiferido<T>(valor: T, ms = 300): T {
  const [diferido, setDiferido] = useState(valor);

  useEffect(() => {
    const temporizador = setTimeout(() => setDiferido(valor), ms);
    return () => clearTimeout(temporizador);
  }, [valor, ms]);

  return diferido;
}
