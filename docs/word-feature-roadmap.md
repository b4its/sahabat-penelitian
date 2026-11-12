# Peta fitur Microsoft Word → VelScribe Wordcraft

Audit 12 November 2026. Referensi: [perbandingan Word desktop/web](https://support.microsoft.com/en-us/word/word-features-comparison-word-for-the-web-vs-desktop), [review dan komentar](https://support.microsoft.com/en-us/word/track-changes-and-view-add-or-edit-comments), dan [menerima/menolak revisi](https://support.microsoft.com/en-us/word/accept-or-reject-tracked-changes-in-word). Microsoft sendiri membedakan fitur desktop dan web; tidak ada satu daftar statis yang mencakup semua varian, langganan, dan integrasi.

| Kelompok | Yang ada di aplikasi (perlu pengujian kesetaraan sebelum mengklaim paritas) | Tahap berikutnya / keterbatasan |
| --- | --- | --- |
| Penulisan dan tata letak | Format teks dan paragraf, tab stop, halaman, margin, header/footer, tabel, gambar, heading, outline, undo/redo | Uji fidelity tata letak dan gaya dokumen besar lintas browser |
| Referensi | Daftar isi, sitasi dan daftar pustaka | Catatan kaki/akhir, indeks, cross-reference, kompatibilitas field DOCX |
| Review | **Komentar lokal tertaut teks**: tambah, lihat, selesaikan, buka kembali, hapus; tersimpan sebagai anotasi HTML dalam draf | Pelacakan perubahan (termasuk penghapusan dan format), terima/tolak per revisi, perbandingan dokumen, threading, penulis, @mention |
| Berkas | Impor DOCX/format lokal lain, ekspor DOCX/PDF/HTML/Markdown/TXT | Round-trip DOCX lengkap (komentar/revisi/field), template, perlindungan dokumen |
| Kolaborasi | Penyimpanan draf lokal | Akun, izin berbagi, sinkronisasi, penyuntingan bersama real-time, riwayat versi server |
| Fitur khusus desktop | Tidak tersedia | Mail merge, add-in, VBA/makro, integrasi Microsoft 365; memerlukan arsitektur dan model keamanan tersendiri |

Tahapan berikutnya harus mendefinisikan kontrak data dan tes round-trip terlebih dahulu, khususnya sebelum mengklaim bahwa anotasi lokal setara dengan komentar DOCX atau kolaborasi Word. Setiap modul diterima hanya setelah uji unit, browser, integrasi, dan satu commit tersendiri.
