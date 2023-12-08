<?php

namespace Fleetbase\Pallet\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(Collection $rows)
    {
        return $rows;
    }
}
