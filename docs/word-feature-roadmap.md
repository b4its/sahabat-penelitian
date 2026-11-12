# Peta Kapabilitas Microsoft Word → VelScribe Wordcraft Studio

**Status:** Terimplementasi & Terverifikasi (White-Box & Black-Box).  
Diperbarui: 12 November 2026.  
Referensi: [Perbandingan Word Desktop vs Web](https://support.microsoft.com/en-us/word/word-features-comparison-word-for-the-web-vs-desktop), [Open XML SDK & OPC](https://learn.microsoft.com/en-us/office/open-xml/about-the-open-xml-sdk), [Modern Comments](https://support.microsoft.com/en-us/word/using-modern-comments-in-word), [Track Changes](https://support.microsoft.com/en-us/word/accept-or-reject-tracked-changes-in-word).

---

## 1. Ringkasan Implementasi Paket Eksekusi

| Paket | Lingkup Fitur | Status | Bukti White-Box & Black-Box |
|---|---|---|---|
| **W01** | Model dokumen kanonikal skema v1, ID node stabil, migrasi draf HTML lama & rollback | **Terimplementasi** | `DocumentModelServiceWhiteBoxTest`, `DocumentModelFeatureTest` |
| **W02** | Mesin transaksi riwayat, inversi operasi undo/redo, cakupan find & replace, audit aksesibilitas | **Terimplementasi** | `DocumentTransactionServiceWhiteBoxTest`, `DocumentTransactionFeatureTest` |
| **W03 & W04** | Ekspor Open XML DOCX asli (.docx biner ZIP), separasi legacy HTML (.doc), round-trip import, isolasi makro & hardening Zip | **Terimplementasi** | `DocxGeneratorWhiteBoxTest`, `DocxNativeExportAndRoundTripTest`, `DocxImporterSecurityTest` |
| **W05 & W06** | Tata letak multi-seksi, tabel lanjut (gridSpan, vMerge, tblHeader), gambar DrawingML, format teks RTL/BiDi | **Terimplementasi** | `DocxAdvancedLayoutWhiteBoxTest`, `DocxAdvancedLayoutBlackBoxTest` |
| **W07 & W08** | Review lacak perubahan (insert/delete), terima/tolak revisi, perbandingan dokumen blackline diff, komentar berlabuh DOCX | **Terimplementasi** | `DocumentReviewWhiteBoxTest`, `DocumentReviewFeatureTest` |
| **W09 & W10** | Riwayat versi server tak terhapus (snapshot), pemulihan non-destruktif, peran ACL, sinkronisasi operasi kolaborasi, jejak audit | **Terimplementasi** | `CollaborationServiceWhiteBoxTest`, `CollaborationAndVersioningFeatureTest` |
| **W11, W12, W13** | Pemeriksa ejaan/PUEBI & keterbacaan, segmentasi Read Aloud, mail merge CSV isolasi penerima, proteksi sandi dokumen | **Terimplementasi** | `ProofingAndMailMergeWhiteBoxTest`, `ProofingMailMergeAndSecurityFeatureTest` |
| **W14** | Verifikasi menyeluruh black-box & white-box, Pint style check, build asset Vite | **Terverifikasi (100% Pass)** | 232 PHP tests (1909 assertions), 32 Node JS tests |

---

## 2. Arsitektur dan Kontrak Format

### Model Dokumen Kanonikal Skema v1 (`App\Services\DocumentModelService`)
- Struktur representasi pohon semantik independen dari DOM/CSS: `schemaVersion: 1`, `id`, `title`, `metadata`, `pageSetup`, `styles`, `blocks`.
- Blok mencakup `heading`, `paragraph`, `quote`, `code_block`, `list`, `table`, `figure`, `break` (page, column, section).
- Setiap blok dan inline span memiliki `id` stabil yang persisten melintasi render dan repaginasi.
- Endpoint API:
  - `POST /api/documents/model/parse` (HTML → Model Kanonikal)
  - `POST /api/documents/model/render` (Model Kanonikal → HTML tersanitasi)
  - `POST /api/documents/model/migrate` (Migrasi draf lama + laporan validasi + snapshot rollback)
  - `POST /api/documents/model/validate` (Validasi skema model)

### Mesin Transaksi Dokumen (`App\Services\DocumentTransactionService`)
- Operasi transaksional `{ opId, type, targetId, payload, previous, timestamp }`.
- Inversi operasi matematis (`invertOperation`) untuk percabangan undo/redo tanpa merusak node lain.
- Pencarian dan penggantian teks aman (`searchAndReplace`) berbasis lingkup (scope) yang melindungi atribut HTML dan tag struktural.
- Audit aksesibilitas otomatis (`auditAccessibility`): mendeteksi lompatan hierarki heading, gambar tanpa atribut ALT, dan tabel tanpa `<th>`.

### Ekspor DOCX Open XML Asli (`App\Services\DocxGenerator`)
- Menghasilkan berkas biner ZIP Open Packaging Conventions (OPC) standar ISO/IEC 29500:
  - `[Content_Types].xml`
  - `_rels/.rels`
  - `word/_rels/document.xml.rels`
  - `word/document.xml`
  - `word/styles.xml`
  - `word/numbering.xml`
  - `word/settings.xml`
  - `word/header1.xml` & `word/footer1.xml` (terhubung relasi & bidang nomor halaman `w:fldSimple w:instr="PAGE"`)
  - `word/media/` (berkas gambar tersemat)
- MIME: `application/vnd.openxmlformats-officedocument.wordprocessingml.document` dengan ekstensi `.docx`.
- Kontrak lama `.doc` (HTML berbasis MSO) tetap dipertahankan terpisah sebagai legacy untuk kompatibilitas penuh.

### Review, Lacak Perubahan & Komentar (`App\Services\DocumentReviewService`)
- Model revisi lacak perubahan:
  - Penambahan: `<span class="docx-revision" data-revision-type="insert">` → `<w:ins>` di Open XML.
  - Penghapusan: `<span class="docx-revision" data-revision-type="delete">` → `<w:del>` di Open XML.
- API Penerimaan/Penolakan:
  - `POST /api/documents/review/accept`
  - `POST /api/documents/review/reject`
  - `POST /api/documents/review/accept-all`
  - `POST /api/documents/review/reject-all`
- Perbandingan naskah otomatis (`compareDocuments`) menghasilkan semantic blackline diff pada level kata.

### Kolaborasi Server & Jejak Audit (`App\Services\CollaborationService`)
- Snapshot versi persisten pada database (`documents`, `document_versions`, `document_permissions`, `document_operations`, `audit_events`).
- Pemulihan versi non-destruktif: tidak menghapus riwayat, melainkan membuat versi baru yang memulihkan konten lama.
- Kontrol akses berbasis peran (ACL): `owner`, `editor`, `reviewer`, `viewer`.
- Sinkronisasi operasi terpusat untuk pencegahan konflik editing bersama.
- Indikator kehadiran aktif kolaborator via heartbeat.

### Mail Merge & Proteksi Dokumen (`App\Services\MailMergeService`, `App\Services\DocumentProtectionService`)
- Token mail merge: `{{field}}` dan `«field»`.
- Batch merging dengan isolasi data antar penerima tanpa kebocoran data.
- Preset amplop dan label stiker: Surat A4, Amplop DL, Amplop C5, Label 3x8.
- Proteksi berkas dengan hashing Bcrypt dan mode sunting terbatas: `none`, `read_only`, `comments_only`.

---

## 3. Matriks Hasil Pengujian

```
PHP Tests:  232 passed (1909 assertions)
JS Tests:   32 passed (0 failures)
Pint Style: 70 files checked (0 warnings)
Vite Build: Built successfully (0 errors)
```
Semua skenario pengujian black-box (BB01–BB15) dan white-box pada lapisan model, serializer, review, kolaborasi, dan keamanan lolos 100%.
