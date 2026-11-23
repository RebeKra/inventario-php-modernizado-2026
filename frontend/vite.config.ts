/// <reference types="vitest/config" />
import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

// La interfaz nueva se publica en /productos/ (nginx la enruta en la Fase 5).
// En desarrollo, las llamadas a /api se redirigen a la API de FastAPI.
export default defineConfig({
  base: "/productos/",
  plugins: [react(), tailwindcss()],
  server: {
    proxy: { "/api": "http://localhost:8000" },
  },
  test: {
    environment: "jsdom",
    setupFiles: ["./src/test/setup.ts"],
    css: false,
  },
});
