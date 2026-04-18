<?php

namespace App\Imports;

use App\Models\Person;
use Maatwebsite\Excel\Concerns\ToModel;

class PeopleImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Person([
            'document'     => $row[0],
            'names'     => $row[1],
            'last_names'    => $row[2],
            'gender_id'    => $row[3],
            'birth_date'    => $row[4],
            'blood_type_id'    => $row[5],
            'address'    => $row[6],
            'phone'    => $row[7],
        ]);
    }
}
