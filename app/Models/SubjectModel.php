<?php

namespace App\Models;
use CodeIgniter\Model;

class SubjectModel extends Model
{
    protected $table = 'master_subjects';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
}