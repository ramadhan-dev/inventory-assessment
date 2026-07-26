<?php

namespace App\Enums;

enum ProductCategory: string
{
    case RawMaterial = 'raw_material';
    case FinishedGoods = 'finished_goods';
    case Packaging = 'packaging';
    case SparePart = 'spare_part';

    public function label(): string
    {
        return match ($this) {
            self::RawMaterial => 'Raw Material',
            self::FinishedGoods => 'Finished Goods',
            self::Packaging => 'Packaging',
            self::SparePart => 'Spare Part',
        };
    }
}
