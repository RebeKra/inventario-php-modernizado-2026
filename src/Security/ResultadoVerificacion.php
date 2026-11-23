<?php

namespace App\Security;

final class ResultadoVerificacion
{
    public function __construct(
        public readonly bool $valida,
        public readonly bool $requiereActualizar,
    ) {
    }
}
