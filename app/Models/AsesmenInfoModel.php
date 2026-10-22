<?php
namespace App\Models;
use CodeIgniter\Model;

class AsesmenInfoModel extends Model
{
    protected $table      = 'asesmen_info';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'gambar_jadwal_tulis', 'gambar_jadwal_praktik',
        'link_kisi_tulis_7', 'link_kisi_praktik_7',
        'link_kisi_tulis_8', 'link_kisi_praktik_8',
        'link_kisi_tulis_9', 'link_kisi_praktik_9'
    ];
}