<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Config\Database;
use CodeIgniter\Exceptions\PageNotFoundException;

class OrtuRaporController extends BaseController
{
    // Kunci rahasia untuk enkripsi URL (Bebas diganti sesuai keinginan)
    private $secretKey = 'RaporSikumiPastiAman2026!'; 

    public function index($student_id, $semester, $tahun, $token)
    {
        // 1. VALIDASI KEAMANAN LINK (TOKEN)
        // Sistem menghitung ulang token berdasarkan ID, Semester, dan Tahun
        $expectedToken = hash('sha256', $student_id . $semester . $tahun . $this->secretKey);
        $expectedTokenShort = substr($expectedToken, 0, 16); // Ambil 16 karakter saja agar URL tidak terlalu panjang

        // Jika token di URL tidak cocok dengan hitungan sistem, tolak aksesnya!
        if ($token !== $expectedTokenShort) {
            throw PageNotFoundException::forPageNotFound('Link rapor tidak valid, telah dimodifikasi, atau kedaluwarsa.');
        }

        $db = Database::connect();

        // A. AMBIL DATA PROFIL SISWA & WALI KELAS
        $dataSiswa = $db->table('users u')
                        ->join('student_profiles sp', 'sp.user_id = u.id', 'left')
                        ->select('u.id, u.username as name, sp.nisn, sp.nis, sp.gender')
                        ->where('u.id', $student_id)
                        ->get()->getRowArray();

        if (empty($dataSiswa)) {
            throw PageNotFoundException::forPageNotFound('Data siswa tidak ditemukan.');
        }

        $kelasSiswa = $db->table('class_rombel_students crs')
                         ->join('class_rombel cr', 'cr.id = crs.rombel_id')
                         ->join('users w', 'w.id = cr.homeroom_teacher_id', 'left') 
                         ->select('cr.rombel_name, w.username as nama_wali_kelas')
                         ->where('crs.student_id', $student_id)
                         ->get()->getRowArray();
                         
        $dataSiswa['kelas'] = $kelasSiswa ? $kelasSiswa['rombel_name'] : '-';
        $dataSiswa['wali_kelas'] = ($kelasSiswa && $kelasSiswa['nama_wali_kelas']) ? $kelasSiswa['nama_wali_kelas'] : '-';

        // B. TENTUKAN BULAN YANG SUDAH DILALUI
        $arrayBulanSemester = ($semester === 'ganjil') ? ['07', '08', '09', '10', '11', '12'] : ['01', '02', '03', '04', '05', '06'];
        $tahunLaporan  = (int) $tahun;
        $tahunSekarang = (int) date('Y');
        $bulanSekarang = (int) date('m');
        $hariSekarang  = (int) date('d');
        $bulanAktif = [];

        $isLaporanLampau = false;
        if ($tahunLaporan < $tahunSekarang) {
            $isLaporanLampau = true;
        } elseif ($tahunLaporan == $tahunSekarang && $semester === 'ganjil' && $bulanSekarang < 7) {
            $isLaporanLampau = true; 
        }

        if ($isLaporanLampau) {
            $bulanAktif = $arrayBulanSemester;
        } else {
            if ($hariSekarang >= 6) {
                $batasBulan = $bulanSekarang - 1;
            } else {
                $batasBulan = $bulanSekarang - 2;
            }
            if ($batasBulan == 0) $batasBulan = 12;
            if ($batasBulan == -1) $batasBulan = 11;

            foreach ($arrayBulanSemester as $b) {
                $intB = (int)$b;
                if ($semester === 'ganjil') {
                    if ($batasBulan >= 7 && $intB <= $batasBulan) { $bulanAktif[] = $b; }
                } else {
                    if ($batasBulan >= 1 && $batasBulan <= 6 && $intB <= $batasBulan) { $bulanAktif[] = $b; }
                }
            }
        }

        if (empty($bulanAktif)) {
            $bulanAktif = ($semester === 'ganjil') ? ['07', '08', '09', '10', '11', '12'] : ['01', '02', '03', '04', '05', '06'];
        }

        // C. TARIK DATA ABSENSI
        $hariEfektifDb = $db->table('hari_efektif')->where('tahun', $tahun)->whereIn('bulan', $bulanAktif)->get()->getResultArray();
        $mapHariEfektif = [];
        foreach ($hariEfektifDb as $he) {
            $mapHariEfektif[str_pad($he['bulan'], 2, '0', STR_PAD_LEFT)] = (int)$he['jumlah_hari'];
        }

        $absenRaw = $db->table('absensi a')
                       ->join('absensi_details ad', 'a.id = ad.absensi_id')
                       ->select('
                           LPAD(MONTH(a.tanggal), 2, "0") as bulan,
                           SUM(CASE WHEN ad.status = "H" THEN 1 ELSE 0 END) as total_h,
                           SUM(CASE WHEN ad.status = "S" THEN 1 ELSE 0 END) as total_s,
                           SUM(CASE WHEN ad.status = "I" THEN 1 ELSE 0 END) as total_i,
                           SUM(CASE WHEN ad.status = "A" THEN 1 ELSE 0 END) as total_a,
                           SUM(CASE WHEN ad.keterlambatan_menit > 0 THEN 1 ELSE 0 END) as total_t,
                           SUM(ad.keterlambatan_menit) as total_menit
                       ')
                       ->where('ad.student_id', $student_id)
                       ->whereIn('LPAD(MONTH(a.tanggal), 2, "0")', $bulanAktif)
                       ->where('YEAR(a.tanggal)', $tahun)
                       ->groupBy('LPAD(MONTH(a.tanggal), 2, "0")') 
                       ->get()->getResultArray();

        $mapAbsenRaw = [];
        foreach ($absenRaw as $ar) { $mapAbsenRaw[$ar['bulan']] = $ar; }

        $matrixAbsen = ['H' => [], 'S' => [], 'I' => [], 'A' => [], 'T' => [], 'M' => []];
        foreach ($bulanAktif as $b) {
            foreach (['H', 'S', 'I', 'A', 'T', 'M'] as $kode) { $matrixAbsen[$kode][$b] = '-'; }
        }

        $totalMentah = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, 'T' => 0, 'M' => 0, 'HariEfektif' => 0];

        foreach ($bulanAktif as $b) {
            $hariEfektif = $mapHariEfektif[$b] ?? 0;
            $totalMentah['HariEfektif'] += $hariEfektif;
            $ar = $mapAbsenRaw[$b] ?? ['total_h' => 0, 'total_s' => 0, 'total_i' => 0, 'total_a' => 0, 'total_t' => 0, 'total_menit' => 0];

            if ($hariEfektif > 0) {
                $matrixAbsen['H'][$b] = min(100, round(($ar['total_h'] / $hariEfektif) * 100)) . '%';
                $matrixAbsen['S'][$b] = min(100, round(($ar['total_s'] / $hariEfektif) * 100)) . '%';
                $matrixAbsen['I'][$b] = min(100, round(($ar['total_i'] / $hariEfektif) * 100)) . '%';
                $matrixAbsen['A'][$b] = min(100, round(($ar['total_a'] / $hariEfektif) * 100)) . '%';
                $matrixAbsen['T'][$b] = min(100, round(($ar['total_t'] / $hariEfektif) * 100)) . '%';
            }
            if ($ar['total_menit'] > 0) { $matrixAbsen['M'][$b] = $ar['total_menit'] . ' mnt'; }

            $totalMentah['H'] += $ar['total_h']; $totalMentah['S'] += $ar['total_s'];
            $totalMentah['I'] += $ar['total_i']; $totalMentah['A'] += $ar['total_a'];
            $totalMentah['T'] += $ar['total_t']; $totalMentah['M'] += $ar['total_menit'];
        }

        $totalAbsen = ['H' => '-', 'S' => '-', 'I' => '-', 'A' => '-', 'T' => '-', 'M' => '-'];
        if ($totalMentah['HariEfektif'] > 0) {
            $th = $totalMentah['HariEfektif'];
            $totalAbsen['H'] = min(100, round(($totalMentah['H'] / $th) * 100)) . '%';
            $totalAbsen['S'] = min(100, round(($totalMentah['S'] / $th) * 100)) . '%';
            $totalAbsen['I'] = min(100, round(($totalMentah['I'] / $th) * 100)) . '%';
            $totalAbsen['A'] = min(100, round(($totalMentah['A'] / $th) * 100)) . '%';
            $totalAbsen['T'] = min(100, round(($totalMentah['T'] / $th) * 100)) . '%';
        }
        if ($totalMentah['M'] > 0) { $totalAbsen['M'] = $totalMentah['M'] . ' mnt'; }

        // D. TARIK DATA KEPATUHAN & KARAKTER
        $currentYear  = (int)date('Y');
        $currentMonth = (int)date('m');
        $namaBulanLokal = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];

        $kepatuhanRaw = $db->table('kepatuhan')
            ->select('LPAD(MONTH(tanggal), 2, "0") as bulan, SUM(seragam) as seragam, SUM(atribut) as atribut, SUM(bersih_diri) as bersih_diri, SUM(terlambat) as terlambat, SUM(aturan_kelas) as aturan_kelas, SUM(masjid) as masjid, GROUP_CONCAT(NULLIF(keterangan, "") SEPARATOR " | ") as gabungan_keterangan')
            ->where('student_id', $student_id)
            ->whereIn('LPAD(MONTH(tanggal), 2, "0")', $bulanAktif)
            ->where('YEAR(tanggal)', $tahun)
            ->groupBy('MONTH(tanggal)')
            ->get()->getResultArray();

        $kepatuhanKolom = ['seragam', 'atribut', 'bersih_diri', 'terlambat', 'aturan_kelas', 'masjid'];
        $kepatuhan = ['matrix' => [], 'totals' => []];
        
        foreach ($kepatuhanKolom as $kolom) {
            $kepatuhan['totals'][$kolom] = 0;
            foreach ($bulanAktif as $b) {
                $isBerjalan = ($tahun < $currentYear) || ($tahun == $currentYear && (int)$b < $currentMonth);
                $kepatuhan['matrix'][$kolom][$b] = $isBerjalan ? 0 : '-';
            }
        }

        $rincianSemester = [];
        foreach ($kepatuhanRaw as $kr) {
            $b = $kr['bulan'];
            foreach ($kepatuhanKolom as $kolom) {
                if ($kr[$kolom] > 0) {
                    $kepatuhan['matrix'][$kolom][$b] = $kr[$kolom];
                    $kepatuhan['totals'][$kolom] += $kr[$kolom];
                }
            }
            if (!empty(trim($kr['gabungan_keterangan']))) {
                $catatanArray = array_filter(array_map('trim', explode('|', $kr['gabungan_keterangan'])));
                $hitungCatatan = [];
                foreach ($catatanArray as $catatan) {
                    if (empty($catatan)) continue;
                    $kunci = strtolower($catatan);
                    if (!isset($hitungCatatan[$kunci])) { $hitungCatatan[$kunci] = ['teks' => ucfirst($kunci), 'jumlah' => 0]; }
                    $hitungCatatan[$kunci]['jumlah']++;
                }
                $catatanFinal = [];
                foreach ($hitungCatatan as $item) {
                    $catatanFinal[] = $item['teks'] . ' (' . $item['jumlah'] . 'x)';
                }
                $rincianSemester[] = '<b>' . ($namaBulanLokal[$b] ?? $b) . '</b>: ' . implode(' | ', $catatanFinal);
            }
        }
        $keteranganPelanggaran = count($rincianSemester) > 0 ? implode('<br>', $rincianSemester) : '-';

        $buildMatrix = function($tabel, $kolomArray) use ($db, $student_id, $bulanAktif, $tahun, $currentYear, $currentMonth, $namaBulanLokal) {
            $select = "LPAD(MONTH(tanggal), 2, '0') as bulan";
            foreach ($kolomArray as $kolom) { $select .= ", SUM($kolom) as $kolom"; }
            $select .= ", GROUP_CONCAT(NULLIF(keterangan, '') SEPARATOR ' | ') as keterangan";

            $dataRaw = [];
            if ($db->tableExists($tabel)) {
                $dataRaw = $db->table($tabel)
                              ->select($select)
                              ->where('student_id', $student_id)
                              ->whereIn('LPAD(MONTH(tanggal), 2, "0")', $bulanAktif)
                              ->where('YEAR(tanggal)', $tahun)
                              ->groupBy('MONTH(tanggal)')
                              ->get()->getResultArray();
            }

            $getPredikat = function($nilai) {
                if ($nilai == 0) return 'A';
                if ($nilai >= 1 && $nilai <= 2) return 'B';
                if ($nilai >= 3 && $nilai <= 4) return 'C';
                return 'D';
            };

            $matrix = []; $totals = []; $keterangan = []; $rincianList = [];
            foreach ($kolomArray as $kolom) {
                $totals[$kolom] = 0;
                foreach ($bulanAktif as $b) {
                    $isSudahLewat = ($tahun < $currentYear) || ($tahun == $currentYear && (int)$b < $currentMonth);
                    $matrix[$kolom][$b] = $isSudahLewat ? 'A' : '-'; 
                }
            }
            foreach ($bulanAktif as $b) { $keterangan[$b] = '-'; }

            foreach ($dataRaw as $dr) {
                $b = $dr['bulan'];
                $isSudahLewat = ($tahun < $currentYear) || ($tahun == $currentYear && (int)$b < $currentMonth);
                if ($isSudahLewat) {
                    foreach ($kolomArray as $kolom) {
                        if (isset($dr[$kolom])) {
                            $nilai = (int) $dr[$kolom];
                            $totals[$kolom] += $nilai;
                            $matrix[$kolom][$b] = $getPredikat($nilai);
                        }
                    }
                    if (!empty(trim($dr['keterangan'] ?? ''))) {
                        $keterangan[$b] = $dr['keterangan'];
                        $catatanArray = preg_split('/[|,]/', $dr['keterangan']);
                        $catatanArray = array_filter(array_map('trim', $catatanArray));
                        $hitungCatatan = [];
                        foreach ($catatanArray as $catatan) {
                            if (empty($catatan)) continue;
                            $kunci = strtolower(trim($catatan));
                            if (!isset($hitungCatatan[$kunci])) { $hitungCatatan[$kunci] = ['teks' => ucfirst($kunci), 'jumlah' => 0]; }
                            $hitungCatatan[$kunci]['jumlah']++;
                        }
                        $catatanFinal = [];
                        foreach ($hitungCatatan as $item) {
                            $catatanFinal[] = $item['jumlah'] > 1 ? $item['teks'] . ' (' . $item['jumlah'] . 'x)' : $item['teks'];
                        }
                        $teksCatatan = implode(', ', $catatanFinal);
                        if (!empty($teksCatatan) && isset($namaBulanLokal[$b])) {
                            $rincianList[] = '<b>' . $namaBulanLokal[$b] . '</b>: ' . esc($teksCatatan);
                        }
                    }
                }
            }

            $totalsPredikat = [];
            foreach ($totals as $kolom => $nilaiTotal) { $totalsPredikat[$kolom] = $getPredikat($nilaiTotal); }

            return [
                'matrix'             => $matrix, 
                'totals_raw'         => $totals, 
                'totals_predikat'    => $totalsPredikat, 
                'keterangan'         => $keterangan,
                'keterangan_rincian' => count($rincianList) > 0 ? implode('<br>', $rincianList) : '-'
            ];
        };

        $spiritual = $buildMatrix('aspek_spiritual', ['berdoa', 'kalimat_thoyibah', 'shalat', 'salam', 'syukur', 'lingkungan', 'toleransi']);
        $sosial    = $buildMatrix('aspek_sosial', ['disiplin', 'jujur', 'percaya_diri', 'santun', 'kerjasama', 'tanggung_jawab', 'adil']);
        
        // E. TARIK NILAI SUMATIF
        $tabelMapel = $db->tableExists('master_subjects') ? 'master_subjects' : ($db->tableExists('subjects') ? 'subjects' : 'mata_pelajaran');
        $kolomNamaMapel = in_array('subject_name', $db->getFieldNames($tabelMapel)) ? 'subject_name' : (in_array('nama_mapel', $db->getFieldNames($tabelMapel)) ? 'nama_mapel' : 'name');
        
        $sumatifRaw = $db->table('nilai_sumatif')->select("LPAD(bulan, 2, '0') as bulan, mapel_id, nilai_angka")->where('student_id', $student_id)->whereIn('bulan', $bulanAktif)->get()->getResultArray();

        $refMapel = [];
        if ($db->tableExists($tabelMapel)) {
            $mapelDb = $db->table($tabelMapel)->select("id, {$kolomNamaMapel} as nama_mapel")->get()->getResultArray();
            foreach ($mapelDb as $m) { $refMapel['S_' . $m['id']] = $m['nama_mapel']; $refMapel[$m['id']] = $m['nama_mapel']; }
        }
        if ($db->tableExists('schedule_combined_subjects')) {
            $gabunganDb = $db->table('schedule_combined_subjects')->select('id, combined_name')->get()->getResultArray();
            foreach ($gabunganDb as $g) { $refMapel['C_' . $g['id']] = $g['combined_name']; }
        }

        $matrixSumatif = [];
        $mapelSembunyi = ['Seni dan Budaya', 'Bahasa Sunda', 'Bimbingan Konseling'];
        
        $mapelAnakGabungan = [];
        if ($db->tableExists('schedule_combined_details') && $db->tableExists('master_subjects')) {
            $anakDb = $db->table('schedule_combined_details scd')
                         ->select('ms.subject_name')
                         ->join('master_subjects ms', 'ms.id = scd.master_subject_id', 'left')
                         ->get()->getResultArray();
            foreach ($anakDb as $anak) {
                if (!empty($anak['subject_name'])) { $mapelAnakGabungan[] = $anak['subject_name']; }
            }
        }
        
        $semuaMapelDihide = array_merge($mapelSembunyi, $mapelAnakGabungan);
        $namaMapelUnik = []; 

        foreach ($refMapel as $key => $namaMapel) {
            if (stripos($namaMapel, 'Pendidikan Jasmani') !== false && stripos($namaMapel, 'Olahraga') !== false) { $namaMapel = 'PJOK'; }
            $isSembunyi = in_array($namaMapel, $semuaMapelDihide);
            $isDouble = in_array($namaMapel, $namaMapelUnik);

            if ((strpos($key, 'S_') === 0 || strpos($key, 'C_') === 0) && !$isSembunyi && !$isDouble) {
                $matrixSumatif[$key] = ['nama_mapel' => $namaMapel, 'nilai' => [], 'total' => 0, 'count' => 0];
                foreach ($bulanAktif as $b) { $matrixSumatif[$key]['nilai'][$b] = null; }
                $namaMapelUnik[] = $namaMapel; 
            }
        }

        foreach ($sumatifRaw as $sr) {
            $mId = $sr['mapel_id']; $bln = $sr['bulan'];
            $rawName = isset($refMapel[$mId]) ? $refMapel[$mId] : '';
            if (stripos($rawName, 'Pendidikan Jasmani') !== false && stripos($rawName, 'Olahraga') !== false) { $rawName = 'PJOK'; }
            if (in_array($rawName, $semuaMapelDihide)) { continue; }

            $targetKey = $mId;
            if (!isset($matrixSumatif[$mId])) {
                 $foundKey = null;
                 foreach ($matrixSumatif as $k => $v) { if ($v['nama_mapel'] === $rawName) { $foundKey = $k; break; } }
                 if ($foundKey) { $targetKey = $foundKey; }
            }

            if (isset($matrixSumatif[$targetKey]) && is_numeric($sr['nilai_angka'])) {
                $matrixSumatif[$targetKey]['nilai'][$bln] = (float)$sr['nilai_angka'];
                $matrixSumatif[$targetKey]['total'] += (float)$sr['nilai_angka'];
                $matrixSumatif[$targetKey]['count']++;
            }
        }

        uasort($matrixSumatif, function($a, $b) { return strcmp($a['nama_mapel'], $b['nama_mapel']); });
        
        // F. AMBIL CATATAN ANEKDOT & PRESTASI
        $anekdot = $db->table('catatan_anekdot')->select('tanggal, kejadian')->where('student_id', $student_id)->whereIn('MONTH(tanggal)', $bulanAktif)->where('YEAR(tanggal)', $tahun)->orderBy('tanggal', 'ASC')->get()->getResultArray();
        $prestasi = $db->table('catatan_prestasi')->select('nama_prestasi, keterangan, created_at')->where('student_id', $student_id)->whereIn('MONTH(created_at)', $bulanAktif)->where('YEAR(created_at)', $tahun)->orderBy('created_at', 'ASC')->get()->getResultArray();

        // G. TARIK DATA NILAI AL-QUR'AN
        $matrixQuran = [
            'Tahsin'  => ['nilai' => [], 'total' => 0, 'count' => 0],
            'Tahfidz' => ['nilai' => [], 'total' => 0, 'count' => 0],
            'Kitabah' => ['nilai' => [], 'total' => 0, 'count' => 0],
        ];

        foreach (['Tahsin', 'Tahfidz', 'Kitabah'] as $aspek) { foreach ($bulanAktif as $b) { $matrixQuran[$aspek]['nilai'][$b] = null; } }

        $quranRaw = [];
        if ($db->tableExists('quran_penilaian')) { 
            $quranRaw = $db->table('quran_penilaian')->select('bulan, tahsin_nilai, tahfidz_nilai, kitabah_nilai')->where('student_id', $student_id)->whereIn('bulan', $bulanAktif)->where('tahun', $tahun)->get()->getResultArray();
        }

        $tempNilai = ['Tahsin' => [], 'Tahfidz' => [], 'Kitabah' => []];
        foreach ($bulanAktif as $b) { $tempNilai['Tahsin'][$b] = []; $tempNilai['Tahfidz'][$b] = []; $tempNilai['Kitabah'][$b] = []; }

        foreach ($quranRaw as $qr) {
            $bln = $qr['bulan'];
            if (!empty(trim($qr['tahsin_nilai']))) { $tempNilai['Tahsin'][$bln][] = (float)str_replace(',', '.', $qr['tahsin_nilai']); }
            if (!empty(trim($qr['tahfidz_nilai']))) { $tempNilai['Tahfidz'][$bln][] = (float)str_replace(',', '.', $qr['tahfidz_nilai']); }
            if (!empty(trim($qr['kitabah_nilai']))) { $tempNilai['Kitabah'][$bln][] = (float)str_replace(',', '.', $qr['kitabah_nilai']); }
        }

        foreach (['Tahsin', 'Tahfidz', 'Kitabah'] as $aspek) {
            foreach ($bulanAktif as $b) {
                $kumpulanNilai = $tempNilai[$aspek][$b];
                if (count($kumpulanNilai) > 0) {
                    $rataBulan = array_sum($kumpulanNilai) / count($kumpulanNilai);
                    $matrixQuran[$aspek]['nilai'][$b] = round($rataBulan, 1); 
                    $matrixQuran[$aspek]['total'] += $rataBulan;
                    $matrixQuran[$aspek]['count']++;
                }
            }
        }

        // H. TARIK DATA EKSTRAKURIKULER, PRAMUKA & PEMINATAN
        $rombelName = $dataSiswa['rombel_name'] ?? $dataSiswa['nama_kelas'] ?? $dataSiswa['kelas'] ?? '';
        $labelPeminatan = 'Peminatan';
        if (preg_match('/(7|VII)/i', $rombelName)) { $labelPeminatan = 'Peminatan (IT)'; } 
        elseif (preg_match('/(8|VIII)/i', $rombelName)) { $labelPeminatan = 'Peminatan (English)'; } 
        elseif (preg_match('/(9|IX)/i', $rombelName)) { $labelPeminatan = 'Peminatan (TKA)'; }

        $getPredikatEskul = function($nilai) {
            if ($nilai === null || $nilai === '' || $nilai === '-') return '-';
            if (is_numeric($nilai)) {
                $n = (float) $nilai;
                if ($n >= 90) return 'A'; if ($n >= 80) return 'B'; if ($n >= 70) return 'C'; return 'D';
            }
            return strtoupper(trim($nilai));
        };

        $getNumericFromPredikat = function($pred) {
            switch (strtoupper(trim($pred))) { case 'A': return 95; case 'B': return 85; case 'C': return 75; case 'D': return 65; default: return null; }
        };

        $assignScore = function(&$matrixRow, &$rawList, $dbBulan, $nilai) use ($bulanAktif, $getPredikatEskul, $getNumericFromPredikat) {
            $pred = $getPredikatEskul($nilai);
            if ($pred === '-') return;
            foreach ($bulanAktif as $b) {
                if ((int)$b === (int)$dbBulan) {
                    $matrixRow['bulan'][$b] = $pred;
                    $num = is_numeric($nilai) ? (float)$nilai : $getNumericFromPredikat($pred);
                    if ($num !== null) { $rawList[] = $num; }
                    break;
                }
            }
        };

        $namaEskulSiswa = '';
        if ($db->tableExists('eskul_grades') && $db->tableExists('eskul_groups')) {
            $eskulNamaRow = $db->table('eskul_grades eg')->join('eskul_groups grp', 'grp.id = eg.group_id', 'left')->select('grp.nama_kelompok')->where('eg.student_id', $student_id)->get()->getRowArray();
            if ($eskulNamaRow && !empty($eskulNamaRow['nama_kelompok'])) { $namaEskulSiswa = trim($eskulNamaRow['nama_kelompok']); }
        }
        $labelEskul = !empty($namaEskulSiswa) ? "Ekstrakurikuler ({$namaEskulSiswa})" : "Ekstrakurikuler";

        $matrixEskul = [
            'Pramuka'         => ['label' => 'Pramuka', 'bulan' => [], 'predikat_akhir' => '-'],
            'Ekstrakurikuler' => ['label' => $labelEskul, 'bulan' => [], 'predikat_akhir' => '-'],
            'Peminatan'       => ['label' => $labelPeminatan, 'bulan' => [], 'predikat_akhir' => '-']
        ];
        $rawScores = ['Pramuka' => [], 'Ekstrakurikuler' => [], 'Peminatan' => []];

        foreach ($bulanAktif as $b) { $matrixEskul['Pramuka']['bulan'][$b] = '-'; $matrixEskul['Ekstrakurikuler']['bulan'][$b] = '-'; $matrixEskul['Peminatan']['bulan'][$b] = '-'; }

        if ($db->tableExists('pramuka_grades')) {
            $pramukaRaw = $db->table('pramuka_grades')->select('bulan, nilai')->where('student_id', $student_id)->whereIn('bulan', $bulanAktif)->get()->getResultArray();
            foreach ($pramukaRaw as $pr) { $assignScore($matrixEskul['Pramuka'], $rawScores['Pramuka'], $pr['bulan'], $pr['nilai']); }
        }
        if ($db->tableExists('peminatan_grades')) {
            $peminatanRaw = $db->table('peminatan_grades')->select('bulan, nilai')->where('student_id', $student_id)->whereIn('bulan', $bulanAktif)->get()->getResultArray();
            foreach ($peminatanRaw as $pm) { $assignScore($matrixEskul['Peminatan'], $rawScores['Peminatan'], $pm['bulan'], $pm['nilai']); }
        }
        if ($db->tableExists('eskul_grades')) {
            $eskulRaw = $db->table('eskul_grades')->select('bulan, nilai')->where('student_id', $student_id)->whereIn('bulan', $bulanAktif)->get()->getResultArray();
            foreach ($eskulRaw as $er) { $assignScore($matrixEskul['Ekstrakurikuler'], $rawScores['Ekstrakurikuler'], $er['bulan'], $er['nilai']); }
        }
        foreach (['Pramuka', 'Ekstrakurikuler', 'Peminatan'] as $key) {
            if (!empty($rawScores[$key])) {
                $avg = array_sum($rawScores[$key]) / count($rawScores[$key]);
                $matrixEskul[$key]['predikat_akhir'] = $getPredikatEskul($avg);
            }
        }

        // I. TARIK DATA YAUMIYAH
        $hariEfektifBulanan = [];
        $cekHari = $db->table('hari_efektif')->where('tahun', $tahun)->whereIn('LPAD(bulan, 2, "0")', $bulanAktif)->get()->getResultArray();
        foreach ($cekHari as $ch) { $hariEfektifBulanan[str_pad($ch['bulan'], 2, '0', STR_PAD_LEFT)] = (int)$ch['jumlah_hari']; }

        $yaumiyahRaw = $db->table('yaumiyah')
                        ->select('LPAD(MONTH(tanggal), 2, "0") as bulan, SUM(dzuhur) as t_dz, SUM(ashar) as t_as, SUM(bakdiah_dzuhur) as t_bd, SUM(duha) as t_dh, SUM(tahajud) as t_th, SUM(tilawah) as t_tl, SUM(infaq) as t_if, SUM(shaum) as t_sh, SUM(literasi) as t_lt')
                        ->where('student_id', $student_id)
                        ->whereIn('LPAD(MONTH(tanggal), 2, "0")', $bulanAktif)
                        ->where('YEAR(tanggal)', $tahun)
                        ->where('DAYOFWEEK(tanggal) !=', 1)->where('DAYOFWEEK(tanggal) !=', 7) 
                        ->groupBy('MONTH(tanggal)')->get()->getResultArray();

        $matrixYaumiyah = [
            'p_dzuhur' => [], 'p_ashar' => [], 'p_bakdiah' => [], 'p_duha' => [], 'p_tahajud' => [], 
            'p_tilawah' => [], 'p_infaq' => [], 'p_shaum' => [], 'p_literasi' => []
        ];
        foreach (array_keys($matrixYaumiyah) as $k) { foreach ($bulanAktif as $b) { $matrixYaumiyah[$k][$b] = 0; } }

        $calcP = function($total, $target) {
            if ($target == 0) return 0;
            $p = ($total / $target) * 100;
            return $p > 100 ? 100 : $p;
        };

        foreach ($yaumiyahRaw as $yr) {
            $b = $yr['bulan'];
            $hEfektif = $hariEfektifBulanan[$b] ?? 20; 
            
            $targetHarian = $hEfektif; $targetMingguan = ceil($hEfektif / 5); $targetShaum = ($hEfektif <= 15) ? 1 : 2;

            $matrixYaumiyah['p_dzuhur'][$b]   = $calcP((int)($yr['t_dz'] ?? 0), $targetHarian);
            $matrixYaumiyah['p_ashar'][$b]    = $calcP((int)($yr['t_as'] ?? 0), $targetHarian);
            $matrixYaumiyah['p_bakdiah'][$b]  = $calcP((int)($yr['t_bd'] ?? 0), $targetHarian);
            $matrixYaumiyah['p_duha'][$b]     = $calcP((int)($yr['t_dh'] ?? 0), $targetHarian);
            $matrixYaumiyah['p_tahajud'][$b]  = $calcP((int)($yr['t_th'] ?? 0), $targetMingguan);
            $matrixYaumiyah['p_tilawah'][$b]  = $calcP((int)($yr['t_tl'] ?? 0), $targetHarian);
            $matrixYaumiyah['p_infaq'][$b]    = $calcP((int)($yr['t_if'] ?? 0), $targetMingguan);
            $matrixYaumiyah['p_shaum'][$b]    = $calcP((int)($yr['t_sh'] ?? 0), $targetShaum);
            $matrixYaumiyah['p_literasi'][$b] = $calcP((int)($yr['t_lt'] ?? 0), $targetHarian);
        }

        // =========================================================
        // KUMPULKAN DATA UNTUK VIEW
        // =========================================================
        $data = [
            'dataSiswa'             => $dataSiswa,
            'bulanAktif'            => $bulanAktif,
            'semester'              => $semester,
            'tahun'                 => $tahun,
            'namaBulanIndo'         => ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'],
            'matrixAbsen'           => $matrixAbsen,
            'totalAbsen'            => $totalAbsen,
            'kepatuhan'             => $kepatuhan,
            'keteranganPelanggaran' => $keteranganPelanggaran,
            'spiritual'             => $spiritual,
            'sosial'                => $sosial,
            'matrixSumatif'         => $matrixSumatif,
            'anekdot'               => $anekdot,
            'prestasi'              => $prestasi,
            'matrixQuran'           => $matrixQuran,
            'matrixEskul'           => $matrixEskul,
            'matrixYaumiyah'        => $matrixYaumiyah 
        ];

        // Gunakan view milik Rapor Siswa yang sebelumnya kita buat,
        // Karena view tersebut sudah bersih dari form pencarian, hanya butuh disesuaikan navigasi atasnya saja.
        return view('siswa/rapor_ortu_index', $data);
    }
}