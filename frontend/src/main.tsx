import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import { PaginaProductos } from "./PaginaProductos";

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <PaginaProductos />
  </StrictMode>,
);
