<?php

namespace App\Data;

final readonly class RequirementLine
{
    public function __construct(
        public int $productId,
        public int $level,
        public string $grossRequirement,
        public string $quantityOnHand,
        public string $quantityOnOrder,
        public string $netRequirement,
    ) {
    }
}
